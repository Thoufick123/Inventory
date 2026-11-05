<?php
/**
 * Migration: Persist scanned QR tags on each warehouse return item.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $db = Database::getInstance();
    $columns = array_column(Database::fetchAll("DESCRIBE `warehouse_return_items`"), 'Field');
    if (!in_array('scanned_qr_codes', $columns, true)) {
        $db->exec("ALTER TABLE `warehouse_return_items` ADD COLUMN `scanned_qr_codes` LONGTEXT NULL AFTER `classification`");
    }

    $returns = Database::fetchAll(
        "SELECT id, source_type, notes
         FROM warehouse_returns
         WHERE notes IS NOT NULL AND notes != ''
           AND source_type IN ('WEBSITE', 'WHATSAPP', 'CHANNEL')"
    );
    foreach ($returns as $return) {
        $notes = json_decode((string)$return['notes'], true);
        $itemQrCodes = is_array($notes) && is_array($notes['item_qr_codes'] ?? null)
            ? $notes['item_qr_codes']
            : [];
        foreach ($itemQrCodes as $itemId => $qrCodes) {
            if (!is_array($qrCodes)) {
                continue;
            }
            Database::execute(
                "UPDATE warehouse_return_items
                 SET scanned_qr_codes = ?
                 WHERE id = ? AND warehouse_return_id = ?
                   AND (scanned_qr_codes IS NULL OR scanned_qr_codes = '')",
                [json_encode(array_values($qrCodes)), (int)$itemId, (int)$return['id']]
            );
        }
        if (strtoupper((string)$return['source_type']) === 'WHATSAPP' && is_array($notes['scanned_tags'] ?? null)) {
            foreach ($notes['scanned_tags'] as $scan) {
                $variantId = (int)($scan['variant_id'] ?? 0);
                $qrCodes = $scan['tag_codes'] ?? (!empty($scan['tag_code']) ? [$scan['tag_code']] : []);
                if ($variantId <= 0 || !is_array($qrCodes)) {
                    continue;
                }
                $qrCodes = array_values(array_unique(array_filter(array_map(
                    static fn($qr): string => trim((string)$qr),
                    $qrCodes
                ), static fn(string $qr): bool => $qr !== '')));
                if (!$qrCodes) {
                    continue;
                }
                $returnItems = Database::fetchAll(
                    "SELECT id FROM warehouse_return_items
                     WHERE warehouse_return_id = ? AND variant_id = ?
                       AND (scanned_qr_codes IS NULL OR scanned_qr_codes = '')",
                    [(int)$return['id'], $variantId]
                );
                if (count($returnItems) === 1) {
                    Database::execute(
                        "UPDATE warehouse_return_items SET scanned_qr_codes = ? WHERE id = ?",
                        [json_encode($qrCodes), (int)$returnItems[0]['id']]
                    );
                }
            }
        }
    }

    $legacyItems = Database::fetchAll(
        "SELECT wri.id, wri.variant_id, wri.returned_qty, wr.source_id
         FROM warehouse_return_items wri
         JOIN warehouse_returns wr ON wr.id = wri.warehouse_return_id
         WHERE (wr.source_type IN ('WEBSITE', 'WHATSAPP', 'CHANNEL'))
           AND wri.returned_qty = 1
           AND (wri.scanned_qr_codes IS NULL OR wri.scanned_qr_codes = '')"
    );
    foreach ($legacyItems as $item) {
        $allocations = Database::fetchAll(
            "SELECT ai.selected_qr_codes, ai.received_qr_codes
             FROM allocation_items ai
             JOIN warehouse_allocations wa ON wa.id = ai.allocation_id
             WHERE ai.variant_id = ? AND (
                 (wa.destination_type = 'CHANNEL' AND wa.destination_id = ?)
                 OR (wa.destination_type = 'WEBSITE' AND wa.destination_id = ?)
             )",
            [(int)$item['variant_id'], (int)$item['source_id'], (int)$item['source_id']]
        );
        $uniqueTags = [];
        foreach ($allocations as $allocation) {
            $rawTags = !empty($allocation['received_qr_codes'])
                ? $allocation['received_qr_codes']
                : ($allocation['selected_qr_codes'] ?? '');
            $decodedTags = json_decode((string)$rawTags, true);
            $tags = is_array($decodedTags) ? $decodedTags : explode(',', (string)$rawTags);
            foreach ($tags as $tag) {
                $tag = is_array($tag)
                    ? ($tag['tag_code'] ?? ($tag['qr_code'] ?? ''))
                    : (string)$tag;
                $tag = trim($tag);
                if ($tag !== '') {
                    $uniqueTags[strtoupper($tag)] = $tag;
                }
            }
        }
        if (count($uniqueTags) === 1) {
            Database::execute(
                "UPDATE warehouse_return_items SET scanned_qr_codes = ? WHERE id = ?",
                [json_encode(array_values($uniqueTags)), (int)$item['id']]
            );
        }
    }

    echo "Warehouse return scanned QR migration completed successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Warehouse return scanned QR migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
