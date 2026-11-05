<?php
/**
 * MailService — Enterprise Transactional Email & Attachment Dispatcher
 * Supports sending emails with file attachments via SMTP sockets or native mail().
 */

class MailService {
    private static ?string $lastError = null;

    public static function getLastError(): ?string {
        return self::$lastError;
    }

    /**
     * Send an email with a file attachment.
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject line
     * @param string $htmlBody HTML content
     * @param string $attachmentPath Absolute filepath of attachment
     * @param string|null $attachmentName Display name for the attachment
     * @return bool
     */
    public static function sendWithAttachment(
        string $to,
        string $subject,
        string $htmlBody,
        string $attachmentPath,
        ?string $attachmentName = null
    ): bool {
        self::$lastError = null;

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = "Invalid recipient email address: {$to}";
            return false;
        }

        if (!file_exists($attachmentPath) || !is_readable($attachmentPath)) {
            self::$lastError = "Attachment file not found or unreadable: {$attachmentPath}";
            return false;
        }

        $smtpHost = env('SMTP_HOST');
        $smtpUser = trim((string)env('SMTP_USER', ''));
        $fromEmail = env('MAIL_FROM_ADDRESS', 'backup@grminventory.com');
        // Gmail requires the From address to be the authenticated account (or an explicitly
        // authorized alias). Use the SMTP login by default to avoid sender rejection.
        if (!empty($smtpHost) && filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
            $fromEmail = $smtpUser;
        }
        $fromName  = env('MAIL_FROM_NAME', 'GRM Inventory Cloud');
        $filename  = $attachmentName ?: basename($attachmentPath);

        // Read and base64-encode attachment content
        $fileContent = @file_get_contents($attachmentPath);
        if ($fileContent === false) {
            self::$lastError = "Failed to read attachment file content: {$attachmentPath}";
            return false;
        }

        $encodedAttachment = chunk_split(base64_encode($fileContent));
        $mimeType = function_exists('mime_content_type') ? (@mime_content_type($attachmentPath) ?: 'application/octet-stream') : 'application/octet-stream';

        $boundary = "==GRM_MULTIPART_BOUNDARY_" . md5(uniqid((string)time(), true));

        // Construct MIME Headers
        $headers = [];
        $headers[] = "From: \"{$fromName}\" <{$fromEmail}>";
        $headers[] = "Reply-To: {$fromEmail}";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";

        // Construct Multipart Body
        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $htmlBody . "\r\n\r\n";

        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: {$mimeType}; name=\"{$filename}\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
        $body .= $encodedAttachment . "\r\n\r\n";
        $body .= "--{$boundary}--";

        // Check if SMTP configuration is present in .env
        if (!empty($smtpHost)) {
            return self::sendViaSmtp($to, $subject, $body, $headers);
        }

        // Native mail() fallback
        $headerStr = implode("\r\n", $headers);
        $sent = @mail($to, $subject, $body, $headerStr);
        if (!$sent) {
            self::$lastError = "Native mail() failed. On local environments (XAMPP/Windows), SMTP settings (SMTP_HOST, SMTP_USER, SMTP_PASS) must be configured in .env to deliver emails to external inboxes like Gmail.";
            error_log("[MailService] " . self::$lastError);
        }

        return $sent;
    }

    /**
     * Send email via direct SMTP socket connection.
     */
    private static function sendViaSmtp(string $to, string $subject, string $body, array $headers): bool {
        $host = env('SMTP_HOST');
        $port = (int)env('SMTP_PORT', 587);
        $user = trim((string)env('SMTP_USER', ''));
        $pass = trim(str_replace(' ', '', (string)env('SMTP_PASS', ''))); // Remove spaces from Google App Password
        $encryption = strtolower((string)env('SMTP_ENCRYPTION', 'tls'));
        
        // Keep the envelope sender consistent with the From header and SMTP login.
        $fromEmail = filter_var($user, FILTER_VALIDATE_EMAIL)
            ? $user
            : env('MAIL_FROM_ADDRESS', 'backup@grminventory.com');

        // Validate that SMTP credentials are provided when connecting to external SMTP (like Gmail)
        if (empty($user) || empty($pass)) {
            self::$lastError = "SMTP Authentication Required: SMTP_USER and SMTP_PASS are empty in your .env file. Please open .env and enter your sender email in SMTP_USER and your 16-character Google App Password in SMTP_PASS.";
            error_log("[MailService] " . self::$lastError);
            return false;
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ]);

        $remoteSocket = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $socket = @stream_socket_client($remoteSocket, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $context);
        
        if (!$socket) {
            self::$lastError = "SMTP Connection failed to {$remoteSocket}: {$errstr} (code {$errno})";
            error_log("[MailService] " . self::$lastError);
            return false;
        }

        stream_set_timeout($socket, 15);

        $getReply = function() use ($socket) {
            $reply = '';
            while ($line = fgets($socket, 512)) {
                $reply .= $line;
                // If 4th char is space or line length is 3 and ended, this is the last line of response
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
                if (strlen(trim($line)) === 3) {
                    break;
                }
            }
            return $reply;
        };

        $sendCommand = function(string $cmd) use ($socket, $getReply) {
            fputs($socket, $cmd . "\r\n");
            return $getReply();
        };

        // Read initial server banner
        $greeting = $getReply();
        if (!preg_match('/^220/i', $greeting)) {
            self::$lastError = "SMTP Server greeting error: " . trim($greeting);
            fclose($socket);
            return false;
        }

        // EHLO
        $ehloRes = $sendCommand("EHLO " . (gethostname() ?: 'localhost'));

        // STARTTLS
        if ($encryption === 'tls') {
            $tlsRes = $sendCommand("STARTTLS");
            if (!preg_match('/^220/i', $tlsRes)) {
                self::$lastError = "SMTP STARTTLS failed: " . trim($tlsRes);
                fclose($socket);
                return false;
            }

            $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }

            $cryptoOk = @stream_socket_enable_crypto($socket, true, $cryptoMethod);
            if (!$cryptoOk) {
                self::$lastError = "SMTP TLS encryption handshake failed.";
                fclose($socket);
                return false;
            }

            // Repeat EHLO after STARTTLS
            $sendCommand("EHLO " . (gethostname() ?: 'localhost'));
        }

        // AUTH LOGIN
        if (!empty($user) && !empty($pass)) {
            $authInit = $sendCommand("AUTH LOGIN");
            if (!preg_match('/^334/i', $authInit)) {
                self::$lastError = "SMTP AUTH LOGIN rejected: " . trim($authInit);
                fclose($socket);
                return false;
            }

            $userRes = $sendCommand(base64_encode($user));
            if (!preg_match('/^334/i', $userRes)) {
                self::$lastError = "SMTP Username rejected: " . trim($userRes);
                fclose($socket);
                return false;
            }

            $passRes = $sendCommand(base64_encode($pass));
            if (!preg_match('/^235/i', $passRes)) {
                self::$lastError = "SMTP Authentication failed (Invalid password or App Password required): " . trim($passRes);
                fclose($socket);
                return false;
            }
        }

        // MAIL FROM
        $mailFromRes = $sendCommand("MAIL FROM: <{$fromEmail}>");
        if (!preg_match('/^250/i', $mailFromRes)) {
            self::$lastError = "SMTP MAIL FROM failed: " . trim($mailFromRes);
            fclose($socket);
            return false;
        }

        // RCPT TO
        $rcptRes = $sendCommand("RCPT TO: <{$to}>");
        if (!preg_match('/^250/i', $rcptRes)) {
            self::$lastError = "SMTP RCPT TO failed for {$to}: " . trim($rcptRes);
            fclose($socket);
            return false;
        }

        // DATA
        $dataInit = $sendCommand("DATA");
        if (!preg_match('/^354/i', $dataInit)) {
            self::$lastError = "SMTP DATA command rejected: " . trim($dataInit);
            fclose($socket);
            return false;
        }

        $headerStr = implode("\r\n", $headers);
        $fullPayload = "To: {$to}\r\nSubject: {$subject}\r\n{$headerStr}\r\n\r\n{$body}\r\n.\r\n";
        fputs($socket, $fullPayload);
        $dataRes = $getReply();

        $sendCommand("QUIT");
        fclose($socket);

        if (!preg_match('/^250/i', $dataRes)) {
            self::$lastError = "SMTP Server rejected message payload: " . trim($dataRes);
            return false;
        }

        return true;
    }
}
