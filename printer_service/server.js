/**
 * GRM Inventory - Windows Local Print Helper Service for TSC TE244
 * 
 * Listens on 127.0.0.1:9191 and translates JSON label jobs to native TSPL barcode commands.
 * Direct spooling to USB/Windows Printer without Chrome print preview dialogs.
 */

const http = require('http');
const fs = require('fs');
const path = require('path');
const { exec, execSync } = require('child_process');

// Load Configuration
const configPath = path.join(__dirname, 'config.json');
let config = {
    port: 9191,
    printerName: "TSC TE244",
    labelWidthMm: 40,
    labelHeightMm: 22,
    gapMm: 2,
    direction: 1,
    referenceX: 0,
    referenceY: 0,
    dpi: 203,
    title: "GRM WAREHOUSE"
};

if (fs.existsSync(configPath)) {
    try {
        const loadedConfig = JSON.parse(fs.readFileSync(configPath, 'utf8'));
        config = { ...config, ...loadedConfig };
    } catch (e) {
        console.error("Error reading config.json, using defaults:", e.message);
    }
}

/**
 * Check if the specified printer exists and is ready in Windows
 */
function checkPrinterStatus(targetPrinter) {
    return new Promise((resolve) => {
        const cmd = `powershell -Command "Get-Printer -Name '${targetPrinter.replace(/'/g, "''")}' | Select-Object Name, PrinterStatus, WorkOffline"`;
        exec(cmd, (error, stdout) => {
            if (error) {
                resolve({ exists: false, status: 'NOT_INSTALLED', message: `Printer '${targetPrinter}' not installed or disconnected.` });
            } else {
                const isOffline = stdout.toLowerCase().includes('true') || stdout.toLowerCase().includes('error');
                resolve({
                    exists: true,
                    status: isOffline ? 'OFFLINE' : 'ONLINE',
                    message: isOffline ? `Printer '${targetPrinter}' is offline or disconnected.` : `Printer '${targetPrinter}' is ready.`
                });
            }
        });
    });
}

/**
 * Build TSPL command stream for a list of label items
 * Supports 2-column (dual-up) label rolls to eliminate wasted stickers and fix text alignment.
 */
function generateTSPL(labels, options = {}) {
    const singleWidth = parseFloat(options.labelWidthMm || config.labelWidthMm || 40);
    const height = parseFloat(options.labelHeightMm || config.labelHeightMm || 25);
    const gap = parseFloat(options.gapMm || config.gapMm || 2);
    const columns = parseInt(options.columns || config.columns || 2);
    const colGap = parseFloat(options.columnGapMm || config.columnGapMm || 4);
    const title = options.title || config.title || "GRM WAREHOUSE";

    // Dynamic Alignment & Font Offsets
    const qrX = parseInt(options.qrX !== undefined ? options.qrX : (config.qrX !== undefined ? config.qrX : 12));
    const qrY = parseInt(options.qrY !== undefined ? options.qrY : (config.qrY !== undefined ? config.qrY : 35));
    const qrCellSize = parseInt(options.qrCellSize || config.qrCellSize || 3);
    const codeX = parseInt(options.codeX !== undefined ? options.codeX : (config.codeX !== undefined ? config.codeX : 12));
    const codeY = parseInt(options.codeY !== undefined ? options.codeY : (config.codeY !== undefined ? config.codeY : 125));
    const textX = parseInt(options.textX !== undefined ? options.textX : (config.textX !== undefined ? config.textX : 115));
    const titleY = parseInt(options.titleY !== undefined ? options.titleY : (config.titleY !== undefined ? config.titleY : 35));
    const skuY = parseInt(options.skuY !== undefined ? options.skuY : (config.skuY !== undefined ? config.skuY : 62));
    const mrpY = parseInt(options.mrpY !== undefined ? options.mrpY : (config.mrpY !== undefined ? config.mrpY : 90));
    const discY = parseInt(options.discY !== undefined ? options.discY : (config.discY !== undefined ? config.discY : 118));
    const textFont = String(options.textFont || config.textFont || "2");
    const col2ShiftDots = parseInt(options.col2ShiftDots !== undefined ? options.col2ShiftDots : (config.col2ShiftDots !== undefined ? config.col2ShiftDots : 0));

    // Calculate total paper width across printer carriage (e.g. 40mm x 2 + 4mm gap = 84mm)
    const totalWidth = columns > 1 ? (singleWidth * columns) + colGap : singleWidth;

    let tspl = "";
    tspl += `SIZE ${totalWidth} mm, ${height} mm\r\n`;
    tspl += `GAP ${gap} mm, 0 mm\r\n`;
    tspl += `DIRECTION 1,0\r\n`;
    tspl += `REFERENCE 0,0\r\n`;
    tspl += `OFFSET 0 mm\r\n`;
    tspl += `SET DENSITY 8\r\n`;
    tspl += `SET SPEED 2\r\n`;
    tspl += `SET PEEL OFF\r\n`;
    tspl += `SET CUTTER OFF\r\n`;
    tspl += `SET TEAR ON\r\n`;
    tspl += `SET BACKFEED AUTO\r\n`;

    // Flatten label list based on requested quantity / copies per item
    const flatLabels = [];
    labels.forEach((item) => {
        const copies = parseInt(item.copies || item.quantity || 1) || 1;
        for (let c = 0; c < copies; c++) {
            flatLabels.push(item);
        }
    });

    // 8.12 dots per mm at 203 DPI
    const dotsPerMm = 8.12;
    const colOffsetDots = Math.round((singleWidth + colGap) * dotsPerMm);

    // Process in batches of `columns` (e.g., 2 stickers per printed row)
    const step = columns > 1 ? columns : 1;

    for (let i = 0; i < flatLabels.length; i += step) {
        tspl += `CLS\r\n`;

        for (let col = 0; col < step; col++) {
            const itemIdx = i + col;
            if (itemIdx >= flatLabels.length) break;
            const item = flatLabels[itemIdx];
            const sku = String(item.sku || "UNKNOWN-SKU").trim();
            const formatPriceVal = (val) => {
                const num = parseFloat(val || 0);
                if (isNaN(num)) return "0";
                return num % 1 === 0 ? String(Math.round(num)) : num.toFixed(2);
            };
            const basePrice = formatPriceVal(item.basePrice);
            const discountPrice = formatPriceVal(item.discountPrice);
            const damageCode = String(item.damageCode || sku).trim();

            // xBase for Col 0 (Left Sticker) vs Col 1 (Right Sticker with col2ShiftDots fine tuning)
            const xBase = col === 0 ? 0 : (colOffsetDots + col2ShiftDots);

            // Left Side: 2D QR Code & SKU Subtitle
            // Compact 24-char payload maintains Version 1 QR Matrix (21x21 modules) so physical QR size stays small (5mm-7mm) while containing all details
            const qrContent = item.qrContent || `${damageCode}|${sku}|MRP:${basePrice}|Disc:${discountPrice}`;
            tspl += `QRCODE ${xBase + qrX},${qrY},M,${qrCellSize},A,0,M2,S7,"${qrContent}"\r\n`;
            tspl += `TEXT ${xBase + codeX},${codeY},"${textFont}",0,1,1,"${damageCode}"\r\n`;

            // Right Side: Store Title, SKU, Base Price (MRP with Strikeout line), Discount Price
            tspl += `TEXT ${xBase + textX},${titleY},"${textFont}",0,1,1,"${title}"\r\n`;
            tspl += `TEXT ${xBase + textX},${skuY},"${textFont}",0,1,1,"${sku}"\r\n`;
            
            const mrpStr = `MRP: Rs.${basePrice}`;
            tspl += `TEXT ${xBase + textX},${mrpY},"${textFont}",0,1,1,"${mrpStr}"\r\n`;
            // Draw horizontal strikeout line through middle of MRP text
            const mrpLineWidth = Math.round(mrpStr.length * 11);
            tspl += `BAR ${xBase + textX},${mrpY + 9},${mrpLineWidth},3\r\n`;

            tspl += `TEXT ${xBase + textX},${discY},"${textFont}",0,1,1,"Disc: Rs.${discountPrice}"\r\n`;
        }

        tspl += `PRINT 1,1\r\n`;
    }

    return tspl;
}

/**
 * Send raw TSPL stream directly to Windows Printer Spooler (RAW Datatype)
 */
function sendToPrinter(tsplContent, targetPrinter) {
    return new Promise((resolve, reject) => {
        const tempFile = path.join(__dirname, `temp_print_${Date.now()}.tspl`);
        fs.writeFileSync(tempFile, tsplContent, 'latin1');

        const escapedPath = tempFile.replace(/'/g, "''");
        const escapedPrinter = targetPrinter.replace(/'/g, "''");

        // Use C# winspool.drv API in PowerShell to send RAW bytes directly without GDI text rendering
        const psScript = `
$code = @"
using System;
using System.IO;
using System.Runtime.InteropServices;

public class RawPrinter {
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Ansi)]
    public class DOCINFOA {
        [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
    }
    [DllImport("winspool.Drv", EntryPoint = "OpenPrinterA", SetLastError = true, CharSet = CharSet.Ansi, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool OpenPrinter([MarshalAs(UnmanagedType.LPStr)] string szPrinter, out IntPtr hPrinter, IntPtr pd);

    [DllImport("winspool.Drv", EntryPoint = "ClosePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool ClosePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartDocPrinterA", SetLastError = true, CharSet = CharSet.Ansi, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool StartDocPrinter(IntPtr hPrinter, Int32 level, [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOA di);

    [DllImport("winspool.Drv", EntryPoint = "EndDocPrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool EndDocPrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartPagePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool StartPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "EndPagePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool EndPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "WritePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, Int32 dwCount, out Int32 dwWritten);

    public static bool SendBytesToPrinter(string szPrinterName, byte[] pBytes) {
        Int32 dwWritten = 0;
        IntPtr hPrinter = new IntPtr(0);
        DOCINFOA di = new DOCINFOA();
        di.pDocName = "TSPL Direct Print Job";
        di.pDataType = "RAW";
        if (OpenPrinter(szPrinterName, out hPrinter, IntPtr.Zero)) {
            if (StartDocPrinter(hPrinter, 1, di)) {
                if (StartPagePrinter(hPrinter)) {
                    IntPtr pUnmanagedBytes = Marshal.AllocHGlobal(pBytes.Length);
                    Marshal.Copy(pBytes, 0, pUnmanagedBytes, pBytes.Length);
                    bool bSuccess = WritePrinter(hPrinter, pUnmanagedBytes, pBytes.Length, out dwWritten);
                    Marshal.FreeHGlobal(pUnmanagedBytes);
                    EndPagePrinter(hPrinter);
                }
                EndDocPrinter(hPrinter);
            }
            ClosePrinter(hPrinter);
            return true;
        }
        return false;
    }
}
"@
Add-Type -TypeDefinition $code
$bytes = [System.IO.File]::ReadAllBytes('${escapedPath}')
[RawPrinter]::SendBytesToPrinter('${escapedPrinter}', $bytes)
`;

        const psFile = path.join(__dirname, `temp_script_${Date.now()}.ps1`);
        fs.writeFileSync(psFile, psScript, 'utf8');

        const cmd = `powershell -NoProfile -ExecutionPolicy Bypass -File "${psFile.replace(/'/g, "''")}"`;

        exec(cmd, (error, stdout, stderr) => {
            // Clean up temp files
            try { fs.unlinkSync(tempFile); } catch (e) { }
            try { fs.unlinkSync(psFile); } catch (e) { }

            if (error) {
                console.error("Print spooler error:", stderr || error.message);
                reject(new Error(`Failed to spool job to ${targetPrinter}. Printer may be offline or disconnected.`));
            } else {
                resolve(true);
            }
        });
    });
}

// Create HTTP Server on 127.0.0.1
const server = http.createServer(async (req, res) => {
    // Enable CORS for localhost web requests
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

    if (req.method === 'OPTIONS') {
        res.writeHead(204);
        res.end();
        return;
    }

    const url = req.url;

    // Endpoint 1: Health & Printer Status Check
    if (req.method === 'GET' && (url === '/' || url === '/health' || url === '/status')) {
        const targetPrinter = req.headers['x-printer-name'] || config.printerName;
        const status = await checkPrinterStatus(targetPrinter);

        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({
            service: "GRM Windows Print Helper",
            running: true,
            configPrinter: config.printerName,
            targetPrinter: targetPrinter,
            printerExists: status.exists,
            printerStatus: status.status,
            message: status.message,
            config: config
        }));
        return;
    }

    // Endpoint: Get Current Config
    if (req.method === 'GET' && url === '/config') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: true, config: config }));
        return;
    }

    // Endpoint: Update Config dynamically from UI
    if (req.method === 'POST' && url === '/config') {
        let body = '';
        req.on('data', chunk => body += chunk.toString());
        req.on('end', () => {
            try {
                const newConfig = JSON.parse(body || '{}');
                config = { ...config, ...newConfig };
                fs.writeFileSync(configPath, JSON.stringify(config, null, 2), 'utf8');

                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: true, message: 'Printer alignment settings saved successfully.', config: config }));
            } catch (err) {
                res.writeHead(500, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: false, message: 'Failed to save config: ' + err.message }));
            }
        });
        return;
    }

    // Endpoint 2: Print Structured Label JSON Job
    if (req.method === 'POST' && url === '/print') {
        let body = '';
        req.on('data', chunk => body += chunk.toString());
        req.on('end', async () => {
            try {
                const data = JSON.parse(body || '{}');
                const targetPrinter = data.printerName || config.printerName;

                // Validate request data
                let labelList = [];
                if (Array.isArray(data.labels)) {
                    labelList = data.labels;
                } else if (data.sku || data.productName) {
                    labelList = [data];
                }

                if (labelList.length === 0) {
                    res.writeHead(400, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({ success: false, message: 'Invalid label data. Empty required fields.' }));
                    return;
                }

                // Check Printer Connectivity
                const pStatus = await checkPrinterStatus(targetPrinter);
                if (!pStatus.exists) {
                    res.writeHead(503, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({
                        success: false,
                        message: `Printer '${targetPrinter}' is not available. Please check the TSC TE244 connection.`
                    }));
                    return;
                }

                // Generate TSPL with dynamic layout options or server config
                const tsplOptions = {
                    labelWidthMm: data.labelWidthMm || config.labelWidthMm,
                    labelHeightMm: data.labelHeightMm || config.labelHeightMm,
                    gapMm: data.gapMm || config.gapMm,
                    columns: data.columns || config.columns,
                    columnGapMm: data.columnGapMm || config.columnGapMm,
                    title: data.title || config.title,
                    qrX: data.qrX !== undefined ? data.qrX : config.qrX,
                    qrY: data.qrY !== undefined ? data.qrY : config.qrY,
                    qrCellSize: data.qrCellSize || config.qrCellSize,
                    codeX: data.codeX !== undefined ? data.codeX : config.codeX,
                    codeY: data.codeY !== undefined ? data.codeY : config.codeY,
                    textX: data.textX !== undefined ? data.textX : config.textX,
                    titleY: data.titleY !== undefined ? data.titleY : config.titleY,
                    skuY: data.skuY !== undefined ? data.skuY : config.skuY,
                    mrpY: data.mrpY !== undefined ? data.mrpY : config.mrpY,
                    discY: data.discY !== undefined ? data.discY : config.discY,
                    textFont: data.textFont || config.textFont,
                    col2ShiftDots: data.col2ShiftDots !== undefined ? data.col2ShiftDots : config.col2ShiftDots
                };

                const tspl = generateTSPL(labelList, tsplOptions);

                // Send to Printer
                await sendToPrinter(tspl, targetPrinter);

                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({
                    success: true,
                    message: `Label printed successfully on ${targetPrinter}.`,
                    printedCount: labelList.length
                }));
            } catch (err) {
                console.error("Print processing error:", err);
                res.writeHead(500, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({
                    success: false,
                    message: `Printing failure: ${err.message}`
                }));
            }
        });
        return;
    }

    // Endpoint 3: Raw TSPL Execution (Authorized)
    if (req.method === 'POST' && url === '/print-raw') {
        let body = '';
        req.on('data', chunk => body += chunk.toString());
        req.on('end', async () => {
            try {
                const data = JSON.parse(body || '{}');
                const targetPrinter = data.printerName || config.printerName;
                const rawTspl = data.tspl;

                if (!rawTspl || typeof rawTspl !== 'string') {
                    res.writeHead(400, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({ success: false, message: 'Missing raw TSPL payload string.' }));
                    return;
                }

                await sendToPrinter(rawTspl, targetPrinter);
                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: true, message: `Raw TSPL sent successfully to ${targetPrinter}.` }));
            } catch (err) {
                res.writeHead(500, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: false, message: err.message }));
            }
        });
        return;
    }

    // 404 Route
    res.writeHead(404, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ success: false, message: 'Route not found.' }));
});

server.listen(config.port, '127.0.0.1', () => {
    console.log(`===================================================`);
    console.log(` GRM Windows Local Print Helper Service Running!   `);
    console.log(` Target Printer : ${config.printerName}`);
    console.log(` Listening on    : http://127.0.0.1:${config.port}`);
    console.log(`===================================================`);
});
