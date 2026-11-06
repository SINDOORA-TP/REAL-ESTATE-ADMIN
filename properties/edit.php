<?php
/**
 * Magaram Real Estate - Edit Property
 */
require_once __DIR__ . '/../includes/auth.php';

$activeTab = 'properties';
$adminTitle = 'Edit Landmark Property';
$csrfToken = generate_csrf_token();
$error = '';

$id = !empty($_GET['id']) ? (int)$_GET['id'] : 0;
$db = get_db();

if (!$db) {
    set_flash('danger', 'Database not connected. Please start MySQL.');
    redirect(BASE_URL . '/properties/index.php');
}

// Fetch property
$stmt = $db->prepare("SELECT * FROM properties WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $id]);
$property = $stmt->fetch();

if (!$property) {
    set_flash('danger', 'Property not found.');
    redirect(BASE_URL . '/properties/index.php');
}

// Fetch property images
$imgStmt = $db->prepare("SELECT * FROM property_images WHERE property_id = :pid ORDER BY is_cover DESC, sort_order ASC");
$imgStmt->execute(['pid' => $id]);
$images = $imgStmt->fetchAll();

// Fetch amenities
$availableAmenities = $db->query("SELECT * FROM amenities ORDER BY name ASC")->fetchAll();
$currentAmenitiesStmt = $db->prepare("SELECT amenity_id FROM property_amenities WHERE property_id = :pid");
$currentAmenitiesStmt->execute(['pid' => $id]);
$selectedAmenities = $currentAmenitiesStmt->fetchAll(PDO::FETCH_COLUMN);

// Handle Actions (Set Cover, Delete Image)
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $imgId = (int)($_GET['img_id'] ?? 0);

    if ($action === 'set_cover' && $imgId > 0) {
        $db->prepare("UPDATE property_images SET is_cover = 0 WHERE property_id = :pid")->execute(['pid' => $id]);
        $db->prepare("UPDATE property_images SET is_cover = 1 WHERE id = :img_id AND property_id = :pid")->execute(['img_id' => $imgId, 'pid' => $id]);
        set_flash('success', 'Cover photo updated.');
        redirect(BASE_URL . '/properties/edit.php?id=' . $id);
    }

    if ($action === 'delete_img' && $imgId > 0) {
        $imgRow = $db->prepare("SELECT image_path FROM property_images WHERE id = :img_id AND property_id = :pid");
        $imgRow->execute(['img_id' => $imgId, 'pid' => $id]);
        $row = $imgRow->fetch();
        if ($row) {
            $absPath = ROOT_PATH . DIRECTORY_SEPARATOR . $row['image_path'];
            if (file_exists($absPath)) {
                @unlink($absPath);
            }
            $db->prepare("DELETE FROM property_images WHERE id = :img_id")->execute(['img_id' => $imgId]);
            set_flash('success', 'Image removed.');
        }
        redirect(BASE_URL . '/properties/edit.php?id=' . $id);
    }
}

// Handle Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $title = clean_string($_POST['title'] ?? '');
        $slug = !empty($_POST['slug']) ? slugify($_POST['slug']) : slugify($title);
        $category = clean_string($_POST['category'] ?? 'Residential');
        $location = clean_string($_POST['location'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $priceDisplay = clean_string($_POST['price_display'] ?? '');
        $areaSqft = (int)($_POST['area_sqft'] ?? 0);
        $bedrooms = (int)($_POST['bedrooms'] ?? 0);
        $bathrooms = (int)($_POST['bathrooms'] ?? 0);
        $description = clean_string($_POST['description'] ?? '');
        $status = clean_string($_POST['status'] ?? 'Available');
        $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;
        $amenityInputs = $_POST['amenities'] ?? [];

        if (empty($title) || empty($location)) {
            $error = 'Title and Location are required.';
        } else {
            try {
                $upStmt = $db->prepare("
                    UPDATE properties SET
                        title = :title,
                        slug = :slug,
                        category = :cat,
                        location = :loc,
                        price = :price,
                        price_display = :price_display,
                        area_sqft = :area,
                        bedrooms = :beds,
                        bathrooms = :baths,
                        description = :desc,
                        status = :status,
                        is_featured = :featured
                    WHERE id = :id
                ");
                $upStmt->execute([
                    'title'         => $title,
                    'slug'          => $slug,
                    'cat'           => $category,
                    'loc'           => $location,
                    'price'         => $price,
                    'price_display' => $priceDisplay ?: format_inr($price),
                    'area'          => $areaSqft,
                    'beds'          => $bedrooms,
                    'baths'         => $bathrooms,
                    'desc'          => $description,
                    'status'        => $status,
                    'featured'      => $isFeatured,
                    'id'            => $id
                ]);

                // Sync amenities
                $db->prepare("DELETE FROM property_amenities WHERE property_id = :pid")->execute(['pid' => $id]);
                if (!empty($amenityInputs) && is_array($amenityInputs)) {
                    $insAm = $db->prepare("INSERT INTO property_amenities (property_id, amenity_id) VALUES (:pid, :aid)");
                    foreach ($amenityInputs as $aid) {
                        $insAm->execute(['pid' => $id, 'aid' => (int)$aid]);
                    }
                }

                // Additional images
                if (!empty($_FILES['images']['name'][0])) {
                    $hasCover = count($images) > 0;
                    foreach ($_FILES['images']['name'] as $idx => $name) {
                        if ($_FILES['images']['error'][$idx] === UPLOAD_ERR_OK) {
                            $fileData = [
                                'name'     => $_FILES['images']['name'][$idx],
                                'type'     => $_FILES['images']['type'][$idx],
                                'tmp_name' => $_FILES['images']['tmp_name'][$idx],
                                'error'    => $_FILES['images']['error'][$idx],
                                'size'     => $_FILES['images']['size'][$idx]
                            ];
                            $uploadRes = safe_upload_image($fileData, PROPERTY_UPLOAD_DIR);
                            if ($uploadRes['success']) {
                                $relPath = 'uploads/properties/' . $uploadRes['filename'];
                                $imgStmt = $db->prepare("INSERT INTO property_images (property_id, image_path, is_cover, sort_order) VALUES (:pid, :path, :cover, :sort)");
                                $imgStmt->execute([
                                    'pid'   => $id,
                                    'path'  => $relPath,
                                    'cover' => !$hasCover ? 1 : 0,
                                    'sort'  => $idx
                                ]);
                                $hasCover = true;
                            }
                        }
                    }
                }

                set_flash('success', "Landmark property updated successfully.");
                redirect(BASE_URL . '/properties/edit.php?id=' . $id);
            } catch (Exception $e) {
                $error = 'Error updating property: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-card" style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h3 style="font-size: 1.25rem; margin: 0; color: var(--text-cream);">Edit: <?= e($property['title']); ?></h3>
        </div>
        <a href="<?= BASE_URL; ?>/properties/index.php" class="btn btn-outline btn-sm">&larr; Back to Listings</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px;"><?= e($error); ?></div>
    <?php endif; ?>

    <!-- Existing Gallery Images -->
    <?php if (!empty($images)): ?>
        <div style="margin-bottom: 30px; background: rgba(0,0,0,0.3); padding: 18px; border-radius: 6px; border: 1px solid var(--admin-border);">
            <label style="display: block; font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--gold-bright); margin-bottom: 12px;">Existing Gallery Photos</label>
            <div style="display: flex; gap: 14px; flex-wrap: wrap;">
                <?php foreach ($images as $img): ?>
                    <div style="position: relative; width: 140px; border-radius: 4px; overflow: hidden; border: <?= $img['is_cover'] ? '2px solid var(--gold-bright)' : '1px solid var(--admin-border)'; ?>;">
                        <img src="<?= BASE_URL . '/' . e($img['image_path']); ?>" alt="" style="width: 100%; height: 90px; object-fit: cover;">
                        <div style="padding: 6px; background: #141414; display: flex; justify-content: space-between; font-size: 0.72rem;">
                            <?php if ($img['is_cover']): ?>
                                <span style="color: var(--gold-bright); font-weight: 600;">Cover</span>
                            <?php else: ?>
                                <a href="?id=<?= $id; ?>&action=set_cover&img_id=<?= $img['id']; ?>" style="color: #ccc;">Make Cover</a>
                            <?php endif; ?>
                            <a href="?id=<?= $id; ?>&action=delete_img&img_id=<?= $img['id']; ?>" style="color: #f87171;" onclick="return confirm('Remove image?');">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="luxury-form">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">

        <div class="form-grid-2">
            <div class="form-group">
                <label for="p-title">Property Title *</label>
                <input type="text" id="p-title" name="title" class="form-control" value="<?= e($property['title']); ?>" required>
            </div>
            <div class="form-group">
                <label for="p-slug">URL Slug</label>
                <input type="text" id="p-slug" name="slug" class="form-control" value="<?= e($property['slug']); ?>">
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="p-category">Category *</label>
                <select id="p-category" name="category" class="form-control">
                    <option value="Apartment" <?= $property['category'] === 'Apartment' ? 'selected' : ''; ?>>Apartment / Penthouse</option>
                    <option value="Villa" <?= $property['category'] === 'Villa' ? 'selected' : ''; ?>>Villa / Private Estate</option>
                    <option value="Commercial" <?= $property['category'] === 'Commercial' ? 'selected' : ''; ?>>Commercial / Office Tower</option>
                    <option value="Residential" <?= $property['category'] === 'Residential' ? 'selected' : ''; ?>>Residential</option>
                    <option value="Plot" <?= $property['category'] === 'Plot' ? 'selected' : ''; ?>>Land Parcel / Plot</option>
                </select>
            </div>
            <div class="form-group">
                <label for="p-location">Prime Location *</label>
                <input type="text" id="p-location" name="location" class="form-control" value="<?= e($property['location']); ?>" required>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="p-price">Valuation Amount (INR) *</label>
                <input type="number" step="100000" id="p-price" name="price" class="form-control" value="<?= $property['price']; ?>" required>
            </div>
            <div class="form-group">
                <label for="p-price-display">Display Price Text</label>
                <input type="text" id="p-price-display" name="price_display" class="form-control" value="<?= e($property['price_display']); ?>">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
            <div class="form-group">
                <label for="p-area">Super Built Area (sq.ft) *</label>
                <input type="number" id="p-area" name="area_sqft" class="form-control" value="<?= $property['area_sqft']; ?>" required>
            </div>
            <div class="form-group">
                <label for="p-bedrooms">Bedrooms</label>
                <input type="number" id="p-bedrooms" name="bedrooms" class="form-control" value="<?= $property['bedrooms']; ?>">
            </div>
            <div class="form-group">
                <label for="p-bathrooms">Bathrooms</label>
                <input type="number" id="p-bathrooms" name="bathrooms" class="form-control" value="<?= $property['bathrooms']; ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="p-desc">Architectural Description *</label>
            <textarea id="p-desc" name="description" class="form-control" rows="5" required><?= e($property['description']); ?></textarea>
        </div>

        <!-- Amenities Selection -->
        <?php if (!empty($availableAmenities)): ?>
            <div class="form-group">
                <label>Signature Amenities Included</label>
                <div class="checkbox-group">
                    <?php foreach ($availableAmenities as $am): ?>
                        <label class="checkbox-item">
                            <input type="checkbox" name="amenities[]" value="<?= $am['id']; ?>" <?= in_array($am['id'], $selectedAmenities) ? 'checked' : ''; ?>>
                            <span><?= e($am['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Additional Images -->
        <div class="form-group">
            <label for="p-images">Upload Additional Photos</label>
            <input type="file" id="p-images" name="images[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp">
        </div>

        <div class="form-grid-2" style="align-items: center; background: rgba(0,0,0,0.3); padding: 16px; border-radius: 6px; border: 1px solid var(--admin-border);">
            <div class="form-group">
                <label for="p-status">Availability Status</label>
                <select id="p-status" name="status" class="form-control">
                    <option value="Available" <?= $property['status'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                    <option value="Upcoming" <?= $property['status'] === 'Upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                    <option value="Sold" <?= $property['status'] === 'Sold' ? 'selected' : ''; ?>>Sold</option>
                </select>
            </div>
            <div class="form-group" style="margin-top: 20px;">
                <label class="checkbox-item">
                    <input type="checkbox" name="is_featured" value="1" <?= !empty($property['is_featured']) ? 'checked' : ''; ?>>
                    <strong>Feature on Homepage Showcase</strong>
                </label>
            </div>
        </div>

        <div style="display: flex; gap: 16px; margin-top: 24px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?= BASE_URL; ?>/properties/index.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

