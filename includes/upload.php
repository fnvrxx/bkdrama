<?php
/**
 * Upload Helper Functions
 * Fungsi untuk handle file upload (video, image, dll)
 */

/**
 * Upload file dengan validasi
 * 
 * @param array $file - $_FILES['field_name']
 * @param string $targetDir - Folder tujuan (tanpa trailing slash)
 * @param array $allowedTypes - MIME types yang diizinkan
 * @param int $maxSize - Ukuran maksimal dalam bytes (default 500MB)
 * @return array - ['success' => bool, 'message' => string, 'filename' => string]
 */
function uploadFile($file, $targetDir, $allowedTypes = [], $maxSize = 524288000)
{
    // 524288000 bytes = 500MB default

    // Validasi: cek apakah ada file yang diupload
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'message' => 'Tidak ada file yang diupload'];
    }

    // Validasi: cek error upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error saat upload file: ' . $file['error']];
    }

    // Validasi: cek ukuran file
    if ($file['size'] > $maxSize) {
        $maxSizeMB = round($maxSize / 1048576, 2);
        return ['success' => false, 'message' => "Ukuran file terlalu besar. Maksimal {$maxSizeMB}MB"];
    }

    // Validasi: cek tipe file
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . '/' . $filename;

    // Pastikan folder exists
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [
            'success' => true,
            'message' => 'File berhasil diupload',
            'filename' => $filename,
            'path' => $targetPath
        ];
    } else {
        return ['success' => false, 'message' => 'Gagal memindahkan file'];
    }
}

/**
 * Upload video file
 * Support: mp4, webm, ogg, avi, mkv, mov
 */
function uploadVideo($file, $targetDir = 'uploads/videos')
{
    $allowedTypes = [
        'video/mp4',
        'video/webm',
        'video/ogg',
        'video/x-msvideo', // avi
        'video/x-matroska', // mkv
        'video/quicktime' // mov
    ];

    return uploadFile($file, $targetDir, $allowedTypes, 524288000); // 500MB
}

/**
 * Upload image file (thumbnail, poster)
 * Support: jpg, jpeg, png, gif, webp
 */
function uploadImage($file, $targetDir = 'uploads/thumbnails')
{
    $allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    return uploadFile($file, $targetDir, $allowedTypes, 5242880); // 5MB
}

/**
 * Delete file dari server
 */
function deleteFile($filePath)
{
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

/**
 * Get file size in readable format
 */
function formatFileSize($bytes)
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Validate video duration (optional - requires FFmpeg)
 */
function getVideoDuration($filePath)
{
    // Requires FFmpeg installed
    if (!file_exists($filePath)) {
        return 0;
    }

    // Simple method using getID3 library or FFmpeg
    // For now, return 0 (implement if needed)
    return 0;
}
?>