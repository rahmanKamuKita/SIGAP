<?php
// ============================================================
// SIGAP — includes/response.php
// Helper untuk mengirim JSON response yang konsisten
// ============================================================

/**
 * Kirim JSON response sukses dan hentikan eksekusi.
 *
 * @param mixed       $data    — payload data (array, object, atau null)
 * @param string      $message — pesan sukses
 * @param int         $code    — HTTP status code (default 200)
 * @param array       $meta    — metadata tambahan (pagination, dsb.)
 */
function jsonSuccess(
    mixed $data = null,
    string $message = 'Berhasil.',
    int $code = 200,
    array $meta = []
): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');

    $body = [
        'success' => true,
        'message' => $message,
        'data' => $data,
    ];

    if (!empty($meta)) {
        $body['meta'] = $meta;
    }

    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Kirim JSON response error dan hentikan eksekusi.
 *
 * @param string $message — pesan error yang ditampilkan ke user
 * @param int    $code    — HTTP status code (default 400)
 * @param array  $errors  — detail error per field (untuk validasi form)
 */
function jsonError(
    string $message = 'Terjadi kesalahan.',
    int $code = 400,
    array $errors = []
): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');

    $body = [
        'success' => false,
        'message' => $message,
        'data' => null,
    ];

    if (!empty($errors)) {
        $body['errors'] = $errors;
    }

    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Kirim JSON response untuk data paginated.
 *
 * @param array  $items       — array data halaman saat ini
 * @param int    $total       — total seluruh record
 * @param int    $page        — halaman saat ini
 * @param int    $perPage     — jumlah item per halaman
 * @param string $message
 */
function jsonPaginated(
    array $items,
    int $total,
    int $page,
    int $perPage,
    string $message = 'Berhasil.'
): never {
    $totalPages = (int) ceil($total / max($perPage, 1));

    jsonSuccess(
        $items,
        $message,
        200,
        [
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'from' => $total > 0 ? ($page - 1) * $perPage + 1 : 0,
                'to' => min($page * $perPage, $total),
            ],
        ]
    );
}