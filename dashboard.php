<?php
/**
 * Magaram Real Estate - Admin Dashboard
 */
require_once __DIR__ . '/includes/auth.php';

$activeTab = 'dashboard';
$adminTitle = 'Executive Overview';

$db = get_db();
$totalProps = 0;
$availableProps = 0;
$totalEnquiries = 0;
$newEnquiries = 0;
$recentEnquiries = [];

if ($db) {
    try {
        $totalProps = (int)$db->query("SELECT COUNT(*) FROM properties")->fetchColumn();
        $availableProps = (int)$db->query("SELECT COUNT(*) FROM properties WHERE status = 'Available'")->fetchColumn();
        $totalEnquiries = (int)$db->query("SELECT COUNT(*) FROM enquiries")->fetchColumn();
        $newEnquiries = (int)$db->query("SELECT COUNT(*) FROM enquiries WHERE status = 'New'")->fetchColumn();

        $stmt = $db->query("
            SELECT e.*, p.title AS property_title 
            FROM enquiries e
            LEFT JOIN properties p ON e.property_id = p.id
            ORDER BY e.created_at DESC
            LIMIT 6
        ");
        $recentEnquiries = $stmt->fetchAll();
    } catch (Exception $e) {}
} else {
    $totalProps = count(get_fallback_properties());
    $availableProps = 5;
    $totalEnquiries = 3;
    $newEnquiries = 2;
    $recentEnquiries = [
        ['id' => 1, 'name' => 'Vikramaditya Rao', 'phone' => '+91 98765 43210', 'email' => 'vikram@apex.in', 'type' => 'Site Visit', 'status' => 'New', 'property_title' => 'The Sovereign Penthouse', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 2, 'name' => 'Dr. Aisha Merchant', 'phone' => '+91 98111 22334', 'email' => 'aisha@hospital.org', 'type' => 'Brochure', 'status' => 'New', 'property_title' => 'The Elysian Garden Villa', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
        ['id' => 3, 'name' => 'Karan Singhania', 'phone' => '+91 99999 88888', 'email' => 'karan@singhania.com', 'type' => 'Enquiry', 'status' => 'Contacted', 'property_title' => 'Prestige Crown Commercial', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))]
    ];
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Statistics Overview Grid -->
<div class="stat-widgets-grid">
    <div class="widget-box">
        <div class="widget-label">Total Properties</div>
        <div class="widget-value"><?= $totalProps; ?></div>
        <div style="font-size: 0.78rem; color: var(--admin-muted); margin-top: 4px;">In Active Portfolio</div>
    </div>
    <div class="widget-box">
        <div class="widget-label">Available for Acquisition</div>
        <div class="widget-value" style="color: #4ade80;"><?= $availableProps; ?></div>
        <div style="font-size: 0.78rem; color: var(--admin-muted); margin-top: 4px;">Ready for viewings</div>
    </div>
    <div class="widget-box">
        <div class="widget-label">Total Inquiries Received</div>
        <div class="widget-value"><?= $totalEnquiries; ?></div>
        <div style="font-size: 0.78rem; color: var(--admin-muted); margin-top: 4px;">Leads captured</div>
    </div>
    <div class="widget-box">
        <div class="widget-label">New / Pending Leads</div>
        <div class="widget-value" style="color: #60a5fa;"><?= $newEnquiries; ?></div>
        <div style="font-size: 0.78rem; color: var(--admin-muted); margin-top: 4px;">Requires outreach</div>
    </div>
</div>

<!-- Recent Inquiries Table -->
<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="font-size: 1.15rem; color: var(--text-cream); margin: 0;">Recent Client Inquiries</h3>
        <a href="<?= BASE_URL; ?>/enquiries.php" class="btn btn-outline btn-sm">View All Leads &rarr;</a>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Client Name</th>
                <th>Phone</th>
                <th>Type</th>
                <th>Property Interest</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recentEnquiries)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--admin-muted); padding: 30px;">
                        No enquiries registered yet.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($recentEnquiries as $enq): ?>
                    <tr>
                        <td><?= date('d M, H:i', strtotime($enq['created_at'])); ?></td>
                        <td>
                            <strong><?= e($enq['name']); ?></strong><br>
                            <span style="font-size: 0.78rem; color: var(--admin-muted);"><?= e($enq['email']); ?></span>
                        </td>
                        <td><?= e($enq['phone']); ?></td>
                        <td>
                            <span style="color: var(--gold-bright); font-weight: 500; font-size: 0.85rem;">
                                <?= e($enq['type']); ?>
                            </span>
                        </td>
                        <td><?= e($enq['property_title'] ?? 'General Portfolio'); ?></td>
                        <td>
                            <span class="badge badge-<?= strtolower($enq['status']); ?>">
                                <?= e($enq['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= BASE_URL; ?>/enquiries.php" class="btn btn-outline btn-sm" style="padding: 4px 10px; font-size: 0.75rem;">
                                Manage
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
