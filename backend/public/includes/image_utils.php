<?php
// Image processing functions for contacts - Simple base64 encoding

function processContactImage($uploadedFile) {
    if (!isset($uploadedFile) || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = $uploadedFile['type'];
    
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Tipo de arquivo não suportado. Use JPEG, PNG, GIF ou WebP.');
    }
    
    // Validate file size (max 2MB)
    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($uploadedFile['size'] > $maxSize) {
        throw new Exception('A imagem deve ter no máximo 2MB.');
    }
    
    // Read file content and convert to base64
    $imageData = file_get_contents($uploadedFile['tmp_name']);
    
    if ($imageData === false) {
        throw new Exception('Não foi possível processar a imagem.');
    }
    
    // Return base64 data URL
    $base64 = base64_encode($imageData);
    return "data:{$fileType};base64,{$base64}";
}

function validateImageData($base64Data) {
    // Check if it's a valid data URL
    if (!preg_match('/^data:image\/(jpeg|png|gif|webp);base64,/', $base64Data)) {
        return false;
    }
    
    // Extract and validate base64 data
    $base64String = preg_replace('/^data:image\/[a-z]+;base64,/', '', $base64Data);
    $imageData = base64_decode($base64String, true);
    
    if ($imageData === false) {
        return false;
    }
    
    return true;
}
?>