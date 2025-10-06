<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';

$pdo = getPDO();
$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
<h1 class="h4 mb-3">Get a Free Estimate</h1>
<div class="row">
  <div class="col-lg-6">
    <?php if ($success): ?>
      <div class="alert alert-success">Thank you! We will contact you shortly.</div>
    <?php elseif (!empty($errors)): ?>
      <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
      <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control" name="email"></div>
      <div class="mb-2"><label class="form-label">Phone</label><input class="form-control" name="phone" required></div>
      <div class="mb-3"><label class="form-label">Property Type</label><select class="form-select" name="property_type"><option value="">Select</option><option>1BHK</option><option>2BHK</option><option>3BHK</option><option>4BHK</option><option>Villa</option><option>Office</option></select></div>
      <div class="mb-3"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="4"></textarea></div>
      <button class="btn btn-primary" type="submit">Request Estimate</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
