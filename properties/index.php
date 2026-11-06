<?php
/**
 * Magaram Real Estate - Properties Management List
 */
require_once __DIR__ . '/../includes/auth.php';

$activeTab = 'properties';
$adminTitle = 'Estate Portfolio Management';
$csrfToken = generate_csrf_token();

$db = get_db();
$properties = [];

if ($db) {
    try {
        $stmt = $db->query("
            SELECT p.*, 
                   COALESCE(pi.image_path, CONCAT('assets/img/property-', p.id, '.svg')) AS cover_image
            FROM properties p
            LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_cover = 1
            ORDER BY p.is_featured DESC, p.created_at DESC
        ");
        $properties = $stmt->fetchAll();
    } catch (Exception $e) {
        $properties = [];
    }
} else {
    $properties = get_fallback_properties();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h3 style="font-size: 1.25rem; margin: 0; color: var(--text-cream);">Active Listings (<?= count($properties); ?>)</h3>
            <p style="font-size: 0.85rem; color: var(--admin-muted); margin-top: 4px;">Manage all public and off-market estate listings.</p>
        </div>
        <a href="<?= BASE_URL; ?>/properties/add.php" class="btn btn-primary btn-sm">+ Add New Landmark</a>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th style="width: 70px;">Cover</th>
                <th>Title & Location</th>
                <th>Category</th>
                <th>Valuation</th>
                <th>Specs</th>
                <th>Status</th>
                <th>Featured</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($properties)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--admin-muted); padding: 40px;">
                        No properties found in database. <a href="<?= BASE_URL; ?>/properties/add.php" style="color: var(--gold-bright);">Add your first property &rarr;</a>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($properties as $p): ?>
                    <?php 
                        $cover = !empty($p['cover_image']) ? $p['cover_image'] : 'assets/img/property-1.svg';
                    ?>
                    <tr>
                        <td>
                            <img src="<?= BASE_URL . '/' . e($cover); ?>" alt="" style="width: 60px; height: 42px; object-fit: cover; border-radius: 4px; border: 1px solid var(--admin-border);">
                        </td>
                        <td>
                            <strong style="color: var(--text-cream); font-size: 0.95rem;"><?= e($p['title']); ?></strong>
                            <div style="font-size: 0.78rem; color: var(--gold-primary);"><?= e($p['location']); ?></div>
                            <?php if (!empty($p['is_sample'])): ?>
                                <span class="sample-badge">Sample</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($p['category']); ?></td>
                        <td>
                            <strong style="color: var(--gold-bright);">
                                <?= e($p['price_display'] ?? format_inr((float)$p['price'])); ?>
                            </strong>
                        </td>
                        <td>
                            <span style="font-size: 0.82rem; color: var(--admin-muted);">
                                <?= number_format($p['area_sqft']); ?> sq.ft &bull; <?= $p['bedrooms']; ?>B/<?= $p['bathrooms']; ?>B
                            </span>
                        </td>
                        <td>
                            <span class="badge" style="background: rgba(198,161,91,0.15); color: var(--gold-bright); border: 1px solid var(--admin-border);">
                                <?= e($p['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?= !empty($p['is_featured']) ? '<span style="color: #facc15;">&#9733; Featured</span>' : '<span style="color: #666;">Standard</span>'; ?>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="<?= BASE_URL; ?>/properties/edit.php?id=<?= $p['id']; ?>" class="btn btn-primary btn-sm" style="padding: 4px 10px; font-size: 0.75rem;">
                                Edit
                            </a>
                            <form action="<?= BASE_URL; ?>/properties/delete.php" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you certain you wish to delete this property?');">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                <input type="hidden" name="id" value="<?= $p['id']; ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="padding: 4px 8px; font-size: 0.75rem; color: #f87171; border-color: rgba(239, 68, 68, 0.4);">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

