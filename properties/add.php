<?php
/**
 * Magaram Real Estate - Add Property
 */
require_once __DIR__ . '/../includes/auth.php';

$activeTab = 'properties';
$adminTitle = 'Add Landmark Property';
$csrfToken = generate_csrf_token();
$error = '';

$db = get_db();
$availableAmenities = [];
if ($db) {
    try {
        $availableAmenities = $db->query("SELECT * FROM amenities ORDER BY name ASC")->fetchAll();
    } catch (Exception $e) {}
}

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
        $selectedAmenities = $_POST['amenities'] ?? [];

        if (empty($title) || empty($location)) {
            $error = 'Please fill in both the Title and Location.';
        } else {
            if ($db) {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO properties 
                        (title, slug, category, location, price, price_display, area_sqft, bedrooms, bathrooms, description, status, is_featured, is_sample, created_at)
                        VALUES 
                        (:title, :slug, :cat, :loc, :price, :price_display, :area, :beds, :baths, :desc, :status, :featured, 0, NOW())
                    ");
                    $stmt->execute([
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
                        'featured'      => $isFeatured
                    ]);
                    $propertyId = $db->lastInsertId();

                    // Amenities
                    if (!empty($selectedAmenities) && is_array($selectedAmenities)) {
                        $amStmt = $db->prepare("INSERT INTO property_amenities (property_id, amenity_id) VALUES (:pid, :aid)");
                        foreach ($selectedAmenities as $aid) {
                            $amStmt->execute(['pid' => $propertyId, 'aid' => (int)$aid]);
                        }
                    }

                    // Handle Image Uploads
                    if (!empty($_FILES['images']['name'][0])) {
                        $isFirst = true;
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
                                        'pid'   => $propertyId,
                                        'path'  => $relPath,
                                        'cover' => $isFirst ? 1 : 0,
                                        'sort'  => $idx
                                    ]);
                                    $isFirst = false;
                                }
                            }
                        }
                    }

                    set_flash('success', "Property '{$title}' successfully added to portfolio!");
                    redirect(BASE_URL . '/properties/index.php');
                } catch (Exception $e) {
                    $error = 'Database Error: ' . $e->getMessage();
                }
            } else {
                set_flash('info', 'Database is currently offline. Start MySQL in XAMPP to persist.');
                redirect(BASE_URL . '/properties/index.php');
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-card" style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h3 style="font-size: 1.25rem; margin: 0; color: var(--text-cream);">Add New Landmark Property</h3>
        <a href="<?= BASE_URL; ?>/properties/index.php" class="btn btn-outline btn-sm">&larr; Back to Listings</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px;"><?= e($error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="luxury-form">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">

        <div class="form-grid-2">
            <div class="form-group">
                <label for="p-title">Property Title *</label>
                <input type="text" id="p-title" name="title" class="form-control" placeholder="e.g. The Sovereign Penthouse" required>
            </div>
            <div class="form-group">
                <label for="p-slug">URL Slug (Leave blank to auto-generate)</label>
                <input type="text" id="p-slug" name="slug" class="form-control" placeholder="the-sovereign-penthouse">
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="p-category">Category *</label>
                <select id="p-category" name="category" class="form-control">
                    <option value="Apartment">Apartment / Penthouse</option>
                    <option value="Villa">Villa / Private Estate</option>
                    <option value="Commercial">Commercial / Office Tower</option>
                    <option value="Residential">Residential</option>
                    <option value="Plot">Land Parcel / Plot</option>
                </select>
            </div>
            <div class="form-group">
                <label for="p-location">Prime Location *</label>
                <input type="text" id="p-location" name="location" class="form-control" placeholder="e.g. Jubilee Hills, Hyderabad" required>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="p-price">Valuation Amount (in INR) *</label>
                <input type="number" step="100000" id="p-price" name="price" class="form-control" placeholder="145000000" required>
            </div>
            <div class="form-group">
                <label for="p-price-display">Display Price Text</label>
                <input type="text" id="p-price-display" name="price_display" class="form-control" placeholder="e.g. ₹14.50 Cr or Price on Request">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
            <div class="form-group">
                <label for="p-area">Super Built Area (sq.ft) *</label>
                <input type="number" id="p-area" name="area_sqft" class="form-control" placeholder="6800" required>
            </div>
            <div class="form-group">
                <label for="p-bedrooms">Bedrooms</label>
                <input type="number" id="p-bedrooms" name="bedrooms" class="form-control" value="4">
            </div>
            <div class="form-group">
                <label for="p-bathrooms">Bathrooms</label>
                <input type="number" id="p-bathrooms" name="bathrooms" class="form-control" value="5">
            </div>
        </div>

        <div class="form-group">
            <label for="p-desc">Architectural Overview & Description *</label>
            <textarea id="p-desc" name="description" class="form-control" rows="5" placeholder="Detailed architectural description, bespoke finishes, views, ceiling heights..." required></textarea>
        </div>

        <!-- Amenities Selection -->
        <?php if (!empty($availableAmenities)): ?>
            <div class="form-group">
                <label>Signature Amenities Included</label>
                <div class="checkbox-group">
                    <?php foreach ($availableAmenities as $am): ?>
                        <label class="checkbox-item">
                            <input type="checkbox" name="amenities[]" value="<?= $am['id']; ?>">
                            <span><?= e($am['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Images Upload -->
        <div class="form-group">
            <label for="p-images">Upload Property Images (Multiple allowed, JPEG/PNG/WebP, max 8MB each)</label>
            <input type="file" id="p-images" name="images[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp">
            <small style="color: var(--admin-muted); display: block; margin-top: 4px;">The first selected image will serve as the initial cover photo.</small>
        </div>

        <div class="form-grid-2" style="align-items: center; background: rgba(0,0,0,0.3); padding: 16px; border-radius: 6px; border: 1px solid var(--admin-border);">
            <div class="form-group">
                <label for="p-status">Availability Status</label>
                <select id="p-status" name="status" class="form-control">
                    <option value="Available">Available</option>
                    <option value="Upcoming">Upcoming / In Development</option>
                    <option value="Sold">Sold / Transacted</option>
                </select>
            </div>
            <div class="form-group" style="margin-top: 20px;">
                <label class="checkbox-item">
                    <input type="checkbox" name="is_featured" value="1" checked>
                    <strong>Feature on Homepage Showcase</strong>
                </label>
            </div>
        </div>

        <div style="display: flex; gap: 16px; margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Publish Landmark Property</button>
            <a href="<?= BASE_URL; ?>/properties/index.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

