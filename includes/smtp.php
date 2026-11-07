<?php
/**
 * Magaram Real Estate - Native Lightweight SMTP Mailer
 * Standards-compliant (RFC 5321 / RFC 5322) socket-based SMTP client with TLS support.
 */

class LuxuryMailer {
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;
    private string $secure; // 'tls', 'ssl', or ''
    private int $timeout = 15;

    public function __construct(array $config = []) {
        $this->host = $config['host'] ?? (defined('SMTP_HOST') ? SMTP_HOST : 'localhost');
        $this->port = (int)($config['port'] ?? (defined('SMTP_PORT') ? SMTP_PORT : 587));
        $this->username = $config['username'] ?? (defined('SMTP_USER') ? SMTP_USER : '');
        $this->password = $config['password'] ?? (defined('SMTP_PASS') ? SMTP_PASS : '');
        $this->fromEmail = $config['from_email'] ?? (defined('SMTP_USER') ? SMTP_USER : 'concierge@magaramrealestate.com');
        $this->fromName = $config['from_name'] ?? (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Magaram Real Estate');
        $this->secure = strtolower($config['secure'] ?? 'tls');
    }

    /**
     * Send an HTML email via SMTP
     */
    public function send(string $to, string $subject, string $htmlBody, string $replyTo = ''): bool {
        // Fallback to native mail() if no SMTP host configured or example host
        if (empty($this->host) || strpos($this->host, 'example.com') !== false) {
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=utf-8',
                'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
                'Reply-To: ' . ($replyTo ?: $this->fromEmail),
                'X-Mailer: MagaramLuxuryMailer/1.0'
            ];
            return @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
        }

        $connectionHost = ($this->secure === 'ssl' ? 'ssl://' : '') . $this->host;
        $socket = @fsockopen($connectionHost, $this->port, $errno, $errstr, $this->timeout);

        if (!$socket) {
            error_log("SMTP Connection Error ($errno): $errstr");
            return false;
        }

        stream_set_timeout($socket, $this->timeout);

        if (!$this->expectCode($socket, [220])) {
            fclose($socket);
            return false;
        }

        // Send EHLO
        $this->sendCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        if (!$this->expectCode($socket, [250])) {
            fclose($socket);
            return false;
        }

        // STARTTLS if required
        if ($this->secure === 'tls') {
            $this->sendCommand($socket, "STARTTLS");
            if (!$this->expectCode($socket, [220])) {
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

            if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                error_log("SMTP TLS Handshake failed.");
                fclose($socket);
                return false;
            }

            // Re-send EHLO over TLS
            $this->sendCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            if (!$this->expectCode($socket, [250])) {
                fclose($socket);
                return false;
            }
        }

        // Authenticate if credentials are provided
        if (!empty($this->username) && !empty($this->password)) {
            $this->sendCommand($socket, "AUTH LOGIN");
            if (!$this->expectCode($socket, [334])) {
                fclose($socket);
                return false;
            }

            $this->sendCommand($socket, base64_encode($this->username));
            if (!$this->expectCode($socket, [334])) {
                fclose($socket);
                return false;
            }

            $this->sendCommand($socket, base64_encode($this->password));
            if (!$this->expectCode($socket, [235])) {
                error_log("SMTP Authentication failed.");
                fclose($socket);
                return false;
            }
        }

        // Mail From
        $this->sendCommand($socket, "MAIL FROM:<{$this->fromEmail}>");
        if (!$this->expectCode($socket, [250])) {
            fclose($socket);
            return false;
        }

        // Recipient
        $this->sendCommand($socket, "RCPT TO:<{$to}>");
        if (!$this->expectCode($socket, [250, 251])) {
            fclose($socket);
            return false;
        }

        // Data payload
        $this->sendCommand($socket, "DATA");
        if (!$this->expectCode($socket, [354])) {
            fclose($socket);
            return false;
        }

        $headers = [];
        $headers[] = "Date: " . date('r');
        $headers[] = "To: <{$to}>";
        $headers[] = "From: {$this->fromName} <{$this->fromEmail}>";
        if (!empty($replyTo)) {
            $headers[] = "Reply-To: <{$replyTo}>";
        }
        $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: base64";

        $message = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($htmlBody)) . "\r\n.";
        $this->sendCommand($socket, $message);

        $success = $this->expectCode($socket, [250]);

        // Quit
        $this->sendCommand($socket, "QUIT");
        fclose($socket);

        return $success;
    }

    private function sendCommand($socket, string $cmd): void {
        fwrite($socket, $cmd . "\r\n");
    }

    private function expectCode($socket, array $validCodes): bool {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $code = (int)substr($response, 0, 3);
        return in_array($code, $validCodes, true);
    }
}
