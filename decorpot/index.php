<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';

$pdo = getPDO();
$projects = $pdo->query("SELECT p.id, p.title, p.image_path, c.name AS category FROM portfolio p LEFT JOIN project_categories c ON p.category_id=c.id WHERE p.status='active' ORDER BY p.created_at DESC LIMIT 6")->fetchAll();
$services = $pdo->query("SELECT id, title, summary, image_path, slug FROM services WHERE status='active' ORDER BY created_at DESC LIMIT 6")->fetchAll();
$testimonials = $pdo->query("SELECT t.client_name, t.testimonial, t.rating, t.image_path FROM testimonials t WHERE t.status='approved' ORDER BY t.created_at DESC LIMIT 5")->fetchAll();

$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'estimate') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission.';
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $propertyType = trim((string)($_POST['property_type'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));

        if ($name === '' || $phone === '') {
            $errors[] = 'Name and phone are required.';
        }
        if (!$errors) {
            $stmt = $pdo->prepare('INSERT INTO contact_submissions (type, name, email, phone, property_type, message) VALUES (\'estimate\', :name, :email, :phone, :ptype, :message)');
            $stmt->execute([
                ':name' => $name,
                ':email' => $email ?: null,
                ':phone' => $phone,
                ':ptype' => $propertyType ?: null,
                ':message' => $message ?: null,
            ]);
            $success = true;
        }
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<section class="hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <h1 class="display-5 fw-bold">Make Your Home Beautiful with Decorpot</h1>
        <p class="lead">End-to-end interior design for homes and offices. Transparent pricing, quality craftsmanship.</p>
      </div>
      <div class="col-lg-5 ms-auto">
        <div class="card shadow-sm">
          <div class="card-body">
            <h2 class="h5 mb-3">Get a Free Estimate</h2>
            <?php if ($success): ?>
              <div class="alert alert-success">Thank you! We will contact you shortly.</div>
            <?php elseif (!empty($errors)): ?>
              <div class="alert alert-danger">
                <ul class="mb-0">
                  <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
            <form method="post">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="form_type" value="estimate">
              <div class="mb-2">
                <label class="form-label">Name</label>
                <input class="form-control" name="name" required>
              </div>
              <div class="mb-2">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email">
              </div>
              <div class="mb-2">
                <label class="form-label">Phone</label>
                <input class="form-control" name="phone" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Property Type</label>
                <select class="form-select" name="property_type">
                  <option value="">Select</option>
                  <option>1BHK</option><option>2BHK</option><option>3BHK</option><option>4BHK</option><option>Villa</option><option>Office</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea class="form-control" name="message" rows="3"></textarea>
              </div>
              <button class="btn btn-primary w-100" type="submit">Request Estimate</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="py-5">
  <h2 class="h4 mb-3">Featured Projects</h2>
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
</section>

<section class="py-5">
  <h2 class="h4 mb-3">Our Services</h2>
  <div class="row g-3">
    <?php foreach ($services as $s): ?>
      <div class="col-md-4">
        <div class="card h-100">
          <img src="<?= e($s['image_path'] ?: base_url('assets/images/placeholder.jpg')) ?>" class="card-img-top" alt="<?= e($s['title']) ?>">
          <div class="card-body">
            <h3 class="h6 mb-1"><?= e($s['title']) ?></h3>
            <p class="small text-muted mb-0"><?= e(mb_strimwidth((string)($s['summary'] ?? ''), 0, 120, '…')) ?></p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="py-5">
  <h2 class="h4 mb-3">Testimonials</h2>
  <div class="row g-3">
    <?php foreach ($testimonials as $t): ?>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="mb-2">
              <?php for ($i=0; $i < (int)$t['rating']; $i++): ?>⭐<?php endfor; ?>
            </div>
            <blockquote class="blockquote">
              <p class="mb-0"><?= e(mb_strimwidth((string)$t['testimonial'], 0, 180, '…')) ?></p>
            </blockquote>
            <div class="mt-2 fw-semibold">— <?= e($t['client_name']) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
