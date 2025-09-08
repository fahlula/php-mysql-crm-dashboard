<?php
// Temporary fix for image column issue
// This file temporarily disables image functionality until migration is run

// Override the contact queries to exclude image column temporarily
function getContactsWithoutImage($pdo, $current_user_id) {
    $stmt = $pdo->prepare("SELECT id, name, email, phone, company, address, notes FROM contacts WHERE user_id = ? ORDER BY name");
    $stmt->execute([$current_user_id]);
    return $stmt->fetchAll();
}

function addContactWithoutImage($pdo, $current_user_id, $data) {
    $stmt = $pdo->prepare("INSERT INTO contacts (user_id, name, email, phone, company, address, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $current_user_id,
        $data['name'],
        $data['email'] ?: null,
        $data['phone'] ?: null,
        $data['company'] ?: null,
        $data['address'] ?: null,
        $data['notes'] ?: null
    ]);
}

function updateContactWithoutImage($pdo, $contact_id, $current_user_id, $data) {
    $stmt = $pdo->prepare("UPDATE contacts SET name = ?, email = ?, phone = ?, company = ?, address = ?, notes = ? WHERE id = ? AND user_id = ?");
    return $stmt->execute([
        $data['name'],
        $data['email'] ?: null,
        $data['phone'] ?: null,
        $data['company'] ?: null,
        $data['address'] ?: null,
        $data['notes'] ?: null,
        $contact_id,
        $current_user_id
    ]);
}
?>