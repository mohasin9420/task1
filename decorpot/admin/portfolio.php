<?php
declare(strict_types=1);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';
require_login();

$pdo = getPDO();
$errors = [];
$success = null;

// Fetch categories
$categories = $pdo->query('SELECT id, name FROM project_categories ORDER BY name')->fetchAll();

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $location = trim((string)($_POST['location'] ?? ''));
        $propertyType = (string)($_POST['property_type'] ?? '');
        $status = (string)($_POST['status'] ?? 'active');
        $imagePath = null;

        if ($title === '') {
            $errors[] = 'Title is required';
        }

        // Handle image upload (optional)
        if (!empty($_FILES['image']['name'])) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($allowed[$_FILES['image']['type']])) {
                $errors[] = 'Invalid image type';
            } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Image upload error';
            } else {
                $ext = $allowed[$_FILES['image']['type']];
                $basename = 'project_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetDir = __DIR__ . '/../assets/uploads/';
                if (!is_dir($targetDir)) { mkdir($targetDir, 0775, true); }
                $target = $targetDir . $basename;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $errors[] = 'Failed to save image';
                } else {
                    $imagePath = 'assets/uploads/' . $basename;
                }
            }
        }

        if (!$errors) {
            if ($id > 0) {
                // Update
                $sql = 'UPDATE portfolio SET title=:title, description=:description, category_id=:category_id, location=:location, property_type=:ptype, status=:status' . ($imagePath ? ', image_path=:image_path' : '') . ' WHERE id=:id';
                $params = [
                    ':title' => $title,
                    ':description' => $description ?: null,
                    ':category_id' => $categoryId ?: null,
                    ':location' => $location ?: null,
                    ':ptype' => $propertyType ?: null,
                    ':status' => $status,
                    ':id' => $id,
                ];
                if ($imagePath) { $params[':image_path'] = $imagePath; }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $success = 'updated';
            } else {
                // Insert
                $stmt = $pdo->prepare('INSERT INTO portfolio (title, description, category_id, location, property_type, status, image_path) VALUES (:title, :description, :category_id, :location, :ptype, :status, :image_path)');
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description ?: null,
                    ':category_id' => $categoryId ?: null,
                    ':location' => $location ?: null,
                    ':ptype' => $propertyType ?: null,
                    ':status' => $status,
                    ':image_path' => $imagePath,
                ]);
                $success = 'created';
            }
        }
    }
}

// Handle delete
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    if (!verify_csrf_token($_GET['token'] ?? null)) {
        $errors[] = 'Invalid delete token.';
    } else {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare('DELETE FROM portfolio WHERE id=:id');
        $stmt->execute([':id' => $id]);
        $success = 'deleted';
    }
}

$items = $pdo->query('SELECT p.*, c.name AS category FROM portfolio p LEFT JOIN project_categories c ON p.category_id=c.id ORDER BY p.created_at DESC')->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<h1 class="h4 mb-3">Manage Portfolio</h1>
<?php if ($success): ?>
  <div class="alert alert-success">Project <?= e($success) ?> successfully.</div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-body">
        <h2 class="h6">Add / Edit Project</h2>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" id="form-id" value="0">
          <div class="mb-2">
            <label class="form-label">Title</label>
            <input class="form-control" name="title" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="3"></textarea>
          </div>
          <div class="mb-2">
            <label class="form-label">Category</label>
            <select class="form-select" name="category_id">
              <option value="">None</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Location</label>
            <input class="form-control" name="location">
          </div>
          <div class="mb-2">
            <label class="form-label">Property Type</label>
            <select class="form-select" name="property_type">
              <option value="">Select</option>
              <option>1BHK</option><option>2BHK</option><option>3BHK</option><option>4BHK</option><option>Villa</option><option>Office</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
              <option>active</option>
              <option>inactive</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Image</label>
            <input type="file" class="form-control" name="image" accept="image/*">
          </div>
          <button class="btn btn-primary" type="submit">Save</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead><tr><th>Image</th><th>Title</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td style="width:100px"><img src="<?= e($it['image_path'] ?: base_url('assets/images/placeholder.jpg')) ?>" alt="" class="img-fluid"></td>
              <td><?= e($it['title']) ?></td>
              <td><?= e($it['category'] ?? '-') ?></td>
              <td><span class="badge bg-<?= $it['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e($it['status']) ?></span></td>
              <td>
                <a class="btn btn-sm btn-outline-danger" href="<?= e(base_url('admin/portfolio.php?action=delete&id=' . (int)$it['id'] . '&token=' . csrf_token())) ?>" onclick="return confirm('Delete this project?')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
