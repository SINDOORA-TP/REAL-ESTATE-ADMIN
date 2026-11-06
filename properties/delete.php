<?php
/**
 * Magaram Real Estate - Delete Property Handler
 */
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Security session invalid.');
        redirect(BASE_URL . '/properties/index.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $db = get_db();

    if ($db && $id > 0) {
        try {
            // Delete associated images on disk
            $imgs = $db->prepare("SELECT image_path FROM property_images WHERE property_id = :id");
            $imgs->execute(['id' => $id]);
            while ($row = $imgs->fetch()) {
                $file = ROOT_PATH . DIRECTORY_SEPARATOR . $row['image_path'];
                if (file_exists($file)) {
                    @unlink($file);
                }
            }

            // Delete property (cascading deletes images and amenities links)
            $stmt = $db->prepare("DELETE FROM properties WHERE id = :id");
            $stmt->execute(['id' => $id]);

            set_flash('success', 'Property successfully removed from portfolio.');
        } catch (Exception $e) {
            set_flash('danger', 'Error deleting property: ' . $e->getMessage());
        }
    }
}

redirect(BASE_URL . '/properties/index.php');

