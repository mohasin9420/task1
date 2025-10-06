<?php
declare(strict_types=1);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';
require_login();

$pdo = getPDO();
$stats = [
    'projects' => (int)$pdo->query('SELECT COUNT(*) FROM portfolio')->fetchColumn(),
    'testimonials' => (int)$pdo->query("SELECT COUNT(*) FROM testimonials WHERE status='pending'")->fetchColumn(),
    'submissions' => (int)$pdo->query("SELECT COUNT(*) FROM contact_submissions WHERE status='new'")->fetchColumn(),
];
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<h1 class="h3 mb-4">Dashboard</h1>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted">Projects</div>
            <div class="display-6"><?= $stats['projects'] ?></div>
          </div>
          <a href="<?= e(base_url('admin/portfolio.php')) ?>" class="btn btn-sm btn-outline-primary">Manage</a>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted">Pending Testimonials</div>
            <div class="display-6"><?= $stats['testimonials'] ?></div>
          </div>
          <a href="<?= e(base_url('admin/testimonials.php')) ?>" class="btn btn-sm btn-outline-primary">Review</a>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted">New Submissions</div>
            <div class="display-6"><?= $stats['submissions'] ?></div>
          </div>
          <a href="<?= e(base_url('admin/submissions.php')) ?>" class="btn btn-sm btn-outline-primary">View</a>
        </div>
      </div>
    </div>
  </div>
</div>
<p class="mt-4"><a href="<?= e(base_url('admin/logout.php')) ?>" class="btn btn-outline-danger btn-sm">Logout</a></p>
<?php include __DIR__ . '/../includes/footer.php'; ?>
