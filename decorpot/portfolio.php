<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';

$pdo = getPDO();
$categories = $pdo->query('SELECT id, name, slug FROM project_categories ORDER BY name')->fetchAll();
$cat = isset($_GET['cat']) ? (string)$_GET['cat'] : '';
$params = [];
$sql = "SELECT p.id, p.title, p.image_path, c.name AS category FROM portfolio p LEFT JOIN project_categories c ON p.category_id=c.id WHERE p.status='active'";
if ($cat !== '') {
    $sql .= ' AND c.slug = :slug';
    $params[':slug'] = $cat;
}
$sql .= ' ORDER BY p.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h1 class="h4 mb-0">Portfolio</h1>
  <form class="d-flex" method="get">
    <select class="form-select me-2" name="cat" onchange="this.form.submit()">
      <option value="">All Categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= e($c['slug']) ?>" <?= $cat === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <noscript><button class="btn btn-primary">Filter</button></noscript>
  </form>
</div>
<div class="row g-3">
  <?php foreach ($projects as $p): ?>
    <div class="col-md-4">
      <div class="card h-100">
        <img src="<?= e($p['image_path'] ?: base_url('assets/images/placeholder.jpg')) ?>" class="card-img-top" alt="<?= e($p['title']) ?>">
        <div class="card-body">
          <h3 class="h6 mb-1"><?= e($p['title']) ?></h3>
          <div class="text-muted small"><?= e($p['category'] ?? 'Uncategorized') ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
