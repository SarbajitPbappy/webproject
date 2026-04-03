<?php
/**
 * HostelEase — File Upload Helper
 * 
 * Handles secure file uploads with MIME validation.
 */

/**
 * Upload a file securely.
 *
 * @param array  $file             $_FILES element
 * @param string $destination      Target directory (relative to UPLOAD_PATH)
 * @param array  $allowedMimeTypes Allowed MIME types
 * @param array  $allowedExtensions Allowed file extensions
 * @param int    $maxSize          Maximum file size in bytes
 * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null]
 */
function uploadFile(
    array $file,
    string $destination = 'students',
    array $allowedMimeTypes = [],
    array $allowedExtensions = [],
    int $maxSize = 0
): array {
    // Use defaults if not specified
    if (empty($allowedMimeTypes)) {
        $allowedMimeTypes = ALLOWED_IMAGE_TYPES;
    }
    if (empty($allowedExtensions)) {
        $allowedExtensions = ALLOWED_IMAGE_EXTENSIONS;
    }
    if ($maxSize === 0) {
        $maxSize = UPLOAD_MAX_SIZE;
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds the maximum upload size.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds the maximum form upload size.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload stopped by extension.',
        ];
        $errorMsg = $errorMessages[$file['error']] ?? 'Unknown upload error.';
        return ['success' => false, 'filename' => null, 'error' => $errorMsg];
    }

    // Check file size
    if ($file['size'] > $maxSize) {
        $maxMB = round($maxSize / 1024 / 1024, 1);
        return ['success' => false, 'filename' => null, 'error' => "File size exceeds {$maxMB}MB limit."];
    }

    // Validate extension
    $originalName = $file['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        return [
            'success' => false,
            'filename' => null,
            'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExtensions),
        ];
    }

    // Validate MIME type using finfo (NOT trusting $_FILES['type'])
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo->file($file['tmp_name']);
    if (!in_array($detectedMime, $allowedMimeTypes)) {
        return [
            'success' => false,
            'filename' => null,
            'error' => 'File MIME type is not allowed: ' . $detectedMime,
        ];
    }

    // Generate unique filename
    $newFilename = uniqid('upload_', true) . '.' . $extension;

    // Create destination directory if it doesn't exist
    $uploadDir = UPLOAD_PATH . $destination . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Move uploaded file
    $targetPath = $uploadDir . $newFilename;
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $newFilename, 'error' => null];
    }

    return ['success' => false, 'filename' => null, 'error' => 'Failed to move uploaded file.'];
}

/**
 * Delete an uploaded file.
 *
 * @param string $filename    The filename to delete
 * @param string $destination Subdirectory within UPLOAD_PATH
 * @return bool
 */
function deleteUploadedFile(string $filename, string $destination = 'students'): bool
{
    if (empty($filename)) {
        return false;
    }
    $filePath = UPLOAD_PATH . $destination . '/' . $filename;
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}
