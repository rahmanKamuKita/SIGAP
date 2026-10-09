<?php
// ============================================================
// SIGAP — includes/wa_sender.php
// Fonnte WhatsApp Gateway API Wrapper
// Dokumentasi Fonnte: https://fonnte.com/docs
// ============================================================

require_once dirname(__DIR__) . '/config.php';

class WASender
{
    private string $token;
    private string $apiUrl;
    private string $countryCode;
    private int $timeout;

    public function __construct(
        string $token = FONNTE_TOKEN,
        string $apiUrl = FONNTE_API_URL,
        string $countryCode = FONNTE_COUNTRY_CODE,
        int $timeout = 15
    ) {
        $this->token = $token;
        $this->apiUrl = $apiUrl;
        // Ambil digit saja ("+62" / "62" → "62"), fallback 62
        $this->countryCode = preg_replace('/\D/', '', $countryCode) ?: '62';
        $this->timeout = $timeout;
    }

    // ── Public: kirim pesan WA ────────────────────────────
    /**
     * Kirim pesan WhatsApp ke satu nomor.
     *
     * @param  string $number  — nomor tujuan (berbagai format diterima)
     * @param  string $message — isi pesan (mendukung *bold*, _italic_)
     * @return array {
     *   success: bool,
     *   response: array,   — parsed JSON dari Fonnte
     *   raw: string,       — raw response body
     *   error: string|null — pesan error jika gagal
     * }
     */
    public function send(string $number, string $message): array
    {
        // Validasi token
        if (empty($this->token) || $this->token === 'GANTI_DENGAN_TOKEN_FONNTE_ANDA') {
            appLog('WASender: Token Fonnte belum dikonfigurasi.', 'WARNING');
            return [
                'success' => false,
                'response' => [],
                'raw' => '',
                'error' => 'Token WhatsApp Gateway belum dikonfigurasi. ' .
                    'Isi FONNTE_TOKEN di file .env.',
            ];
        }

        $formattedNumber = $this->formatNumber($number);

        if (empty($formattedNumber)) {
            return [
                'success' => false,
                'response' => [],
                'raw' => '',
                'error' => 'Nomor WhatsApp tidak valid: ' . $number,
            ];
        }

        // Siapkan payload
        $postFields = http_build_query([
            'target' => $formattedNumber,
            'message' => $message,
            'countryCode' => $this->countryCode,
        ]);

        // Kirim via cURL
        // Deteksi environment lokal (Laragon / XAMPP)
        // SSL verify dimatikan di lokal karena cacert.pem sering bermasalah
        // Cek juga IP privat (akses via 192.168.x.x / 10.x.x.x) dan CLI
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
        $isPrivateIp = $serverAddr !== '' && filter_var(
            $serverAddr,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
        $isLocal = in_array($serverName, ['localhost', '127.0.0.1', '::1'])
            || str_ends_with($serverName, '.test')
            || $isPrivateIp
            || PHP_SAPI === 'cli';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->token,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_SSL_VERIFYPEER => !$isLocal,
            CURLOPT_SSL_VERIFYHOST => $isLocal ? 0 : 2,
        ]);

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // cURL error (jaringan, timeout, dsb.)
        if ($rawResponse === false || !empty($curlError)) {
            $errMsg = 'cURL error: ' . $curlError;
            appLog("WASender send() gagal ke {$formattedNumber}: {$errMsg}", 'ERROR');
            return [
                'success' => false,
                'response' => [],
                'raw' => '',
                'error' => $errMsg,
            ];
        }

        // Parse JSON response dari Fonnte
        $parsed = json_decode($rawResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            appLog("WASender: Response bukan JSON valid. Raw: {$rawResponse}", 'ERROR');
            return [
                'success' => false,
                'response' => [],
                'raw' => $rawResponse,
                'error' => 'Response dari server tidak valid (bukan JSON).',
            ];
        }

        // Fonnte mengembalikan {"status":true} jika sukses
        $success = isset($parsed['status']) && $parsed['status'] === true;

        if ($success) {
            appLog("WASender: Pesan terkirim ke {$formattedNumber}.", 'INFO');
        } else {
            $reason = $parsed['reason'] ?? $parsed['message'] ?? 'unknown';
            appLog("WASender: Pengiriman gagal ke {$formattedNumber} (HTTP {$httpCode}). Reason: {$reason}. Raw: {$rawResponse}", 'WARNING');
        }

        return [
            'success' => $success,
            'response' => $parsed,
            'raw' => $rawResponse,
            'error' => $success ? null : ($parsed['reason'] ?? $parsed['message'] ?? 'Pengiriman gagal.'),
        ];
    }

    // ── Public: format nomor ──────────────────────────────
    /**
     * Normalisasi nomor WhatsApp ke format internasional tanpa "+".
     *
     * Contoh konversi:
     *   08123456789   → 628123456789
     *   +628123456789 → 628123456789
     *   628123456789  → 628123456789
     *   8123456789    → 628123456789
     *
     * @param  string $number
     * @return string — nomor bersih, atau string kosong jika tidak valid
     */
    public function formatNumber(string $number): string
    {
        // Hapus semua karakter non-digit kecuali "+"
        $clean = preg_replace('/[^0-9+]/', '', trim($number));

        if (empty($clean))
            return '';

        // Hapus "+" jika ada di depan
        $clean = ltrim($clean, '+');

        // Sudah diawali kode negara 62
        if (str_starts_with($clean, '62')) {
            return $clean;
        }

        // Diawali 0 → ganti dengan kode negara
        if (str_starts_with($clean, '0')) {
            return $this->countryCode . substr($clean, 1);
        }

        // Nomor tanpa awalan (misal: 8123456789)
        if (str_starts_with($clean, '8')) {
            return $this->countryCode . $clean;
        }

        // Format tidak dikenali
        return $clean;
    }

    // ── Public: validasi nomor ────────────────────────────
    /**
     * Periksa apakah nomor WA valid (minimal 10 digit setelah format).
     *
     * @param  string $number
     * @return bool
     */
    public function isValidNumber(string $number): bool
    {
        $formatted = $this->formatNumber($number);
        return strlen($formatted) >= 10 && ctype_digit($formatted);
    }
}