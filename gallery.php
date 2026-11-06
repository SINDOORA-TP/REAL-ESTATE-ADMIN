<?php
/**
 * Magaram Real Estate - Gallery Management
 */
require_once __DIR__ . '/includes/auth.php';

$activeTab = 'gallery';
$adminTitle = 'Architectural Gallery Management';
$csrfToken = generate_csrf_token();
$error = '';

$db = get_db();

if (isset($_GET['delete']) && $db) {
    $delId = (int)$_GET['delete'];
    $imgRow = $db->prepare("SELECT image_path FROM gallery WHERE id = :id");
    $imgRow->execute(['id' => $delId]);
    $row = $imgRow->fetch();
    if ($row) {
        $abs = ROOT_PATH . DIRECTORY_SEPARATOR . $row['image_path'];
        if (file_exists($abs)) @unlink($abs);
        $db->prepare("DELETE FROM gallery WHERE id = :id")->execute(['id' => $delId]);
        set_flash('success', 'Gallery item deleted.');
    }
    redirect(BASE_URL . '/gallery.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired.';
    } else {
        $title = clean_string($_POST['title'] ?? '');
        $category = clean_string($_POST['category'] ?? 'Exterior');

        if (empty($title)) {
            $error = 'Please provide a title for the photograph.';
        } elseif (!empty($_FILES['image']['name'])) {
            $uploadRes = safe_upload_image($_FILES['image'], PROPERTY_UPLOAD_DIR);
            if ($uploadRes['success']) {
                $relPath = 'uploads/properties/' . $uploadRes['filename'];
                $ins = $db->prepare("INSERT INTO gallery (title, category, image_path, created_at) VALUES (:title, :cat, :path, NOW())");
                $ins->execute([
                    'title' => $title,
                    'cat'   => $category,
                    'path'  => $relPath
                ]);
                set_flash('success', 'New gallery photograph uploaded successfully.');
                redirect(BASE_URL . '/gallery.php');
            } else {
                $error = $uploadRes['error'];
            }
        } else {
            $error = 'Please select an image file to upload.';
        }
    }
}

$items = [];
if ($db) {
    try {
        $items = $db->query("SELECT * FROM gallery ORDER BY created_at DESC")->fetchAll();
    } catch (Exception $e) {}
}

if (empty($items)) {
    $items = [
        ['id' => 1, 'title' => 'Penthouse Sky Deck', 'category' => 'Exterior', 'image_path' => 'assets/img/property-1.svg'],
        ['id' => 2, 'title' => 'Contemporary Stone Villa', 'category' => 'Architecture', 'image_path' => 'assets/img/property-2.svg'],
        ['id' => 3, 'title' => 'Commercial Glass Spire', 'category' => 'Commercial', 'image_path' => 'assets/img/property-3.svg'],
        ['id' => 4, 'title' => 'Boutique Residence Atrium', 'category' => 'Interior', 'image_path' => 'assets/img/property-4.svg'],
        ['id' => 5, 'title' => 'Green Valley Grounds', 'category' => 'Estate', 'image_path' => 'assets/img/property-5.svg'],
        ['id' => 6, 'title' => 'Opus Executive Complex', 'category' => 'Commercial', 'image_path' => 'assets/img/property-6.svg']
    ];
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
    <!-- Upload New Form -->
    <div class="admin-card">
        <h3 style="font-size: 1.15rem; margin-bottom: 16px; color: var(--text-cream);">Upload Gallery Photo</h3>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= e($error); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="luxury-form">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">

            <div class="form-group">
                <label for="g-title">Photo Title *</label>
                <input type="text" id="g-title" name="title" class="form-control" placeholder="e.g. Italian Marble Living Hall" required>
            </div>

            <div class="form-group">
                <label for="g-cat">Category *</label>
                <select id="g-cat" name="category" class="form-control">
                    <option value="Exterior">Exterior Architecture</option>
                    <option value="Interior">Interior Living</option>
                    <option value="Amenities">Signature Amenities</option>
                    <option value="Landscape">Gardens & Estates</option>
                </select>
            </div>

            <div class="form-group">
                <label for="g-file">Image File (JPEG, PNG, WebP) *</label>
                <input type="file" id="g-file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                Upload to Gallery
            </button>
        </form>
    </div>

    <!-- Gallery Grid List -->
    <div class="admin-card">
        <h3 style="font-size: 1.15rem; margin-bottom: 20px; color: var(--text-cream);">Current Gallery Collection (<?= count($items); ?>)</h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px;">
            <?php foreach ($items as $item): ?>
                <div style="background: rgba(0,0,0,0.4); border: 1px solid var(--admin-border); border-radius: 6px; overflow: hidden;">
                    <div style="aspect-ratio: 16/10; overflow: hidden;">
                        <img src="<?= BASE_URL . '/' . e($item['image_path']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="padding: 10px;">
                        <strong style="font-size: 0.85rem; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($item['title']); ?></strong>
                        <div style="font-size: 0.72rem; color: var(--gold-bright); margin-top: 2px;"><?= e($item['category']); ?></div>
                        <div style="margin-top: 8px; text-align: right;">
                            <a href="?delete=<?= $item['id']; ?>" style="color: #f87171; font-size: 0.75rem;" onclick="return confirm('Remove photo?');">Remove</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
