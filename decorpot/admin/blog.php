<?php
declare(strict_types=1);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';
require_login();

$pdo = getPDO();
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        $excerpt = trim((string)($_POST['excerpt'] ?? ''));
        $content = (string)($_POST['content'] ?? '');
        $status = (string)($_POST['status'] ?? 'draft');
        $imagePath = null;

        if ($title === '' || $slug === '') { $errors[] = 'Title and slug are required.'; }

        if (!empty($_FILES['image']['name'])) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($allowed[$_FILES['image']['type']])) {
                $errors[] = 'Invalid image type';
            } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Image upload error';
            } else {
                $ext = $allowed[$_FILES['image']['type']];
                $basename = 'blog_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
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
                $sql = 'UPDATE blog_posts SET title=:title, slug=:slug, excerpt=:excerpt, content=:content, status=:status' . ($imagePath ? ', image_path=:image_path' : '') . ' WHERE id=:id';
                $params = [
                    ':title' => $title,
                    ':slug' => $slug,
                    ':excerpt' => $excerpt ?: null,
                    ':content' => $content ?: null,
                    ':status' => $status,
                    ':id' => $id,
                ];
                if ($imagePath) { $params[':image_path'] = $imagePath; }
                $pdo->prepare($sql)->execute($params);
                $success = 'updated';
            } else {
                $pdo->prepare('INSERT INTO blog_posts (title, slug, excerpt, content, status, image_path, published_at) VALUES (:title, :slug, :excerpt, :content, :status, :image_path, CASE WHEN :status = \"published\" THEN NOW() ELSE NULL END)')->execute([
                    ':title' => $title,
                    ':slug' => $slug,
                    ':excerpt' => $excerpt ?: null,
                    ':content' => $content ?: null,
                    ':status' => $status,
                    ':image_path' => $imagePath,
                ]);
                $success = 'created';
            }
        }
    }
}

if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    if (!verify_csrf_token($_GET['token'] ?? null)) {
        $errors[] = 'Invalid delete token.';
    } else {
        $id = (int)$_GET['id'];
        $pdo->prepare('DELETE FROM blog_posts WHERE id=:id')->execute([':id' => $id]);
        $success = 'deleted';
    }
}

$items = $pdo->query('SELECT * FROM blog_posts ORDER BY COALESCE(published_at, created_at) DESC')->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<h1 class="h4 mb-3">Manage Blog Posts</h1>
<?php if ($success): ?><div class="alert alert-success">Post <?= e($success) ?> successfully.</div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="card"><div class="card-body">
      <h2 class="h6">Add / Edit Post</h2>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="0">
        <div class="mb-2"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
        <div class="mb-2"><label class="form-label">Slug</label><input class="form-control" name="slug" required></div>
        <div class="mb-2"><label class="form-label">Excerpt</label><textarea class="form-control" name="excerpt" rows="2"></textarea></div>
        <div class="mb-2"><label class="form-label">Content</label><textarea class="form-control" name="content" rows="6"></textarea></div>
        <div class="mb-2"><label class="form-label">Status</label><select class="form-select" name="status"><option>draft</option><option>published</option></select></div>
        <div class="mb-3"><label class="form-label">Image</label><input type="file" name="image" class="form-control" accept="image/*"></div>
        <button class="btn btn-primary" type="submit">Save</button>
      </form>
    </div></div>
  </div>
  <div class="col-lg-7">
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead><tr><th>Image</th><th>Title</th><th>Status</th><th>Published</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td style="width:100px"><img src="<?= e($it['image_path'] ?: base_url('assets/images/placeholder.jpg')) ?>" class="img-fluid" alt=""></td>
            <td><?= e($it['title']) ?></td>
            <td><span class="badge bg-<?= $it['status'] === 'published' ? 'success' : 'secondary' ?>"><?= e($it['status']) ?></span></td>
            <td><?= e($it['published_at'] ?? '-') ?></td>
            <td>
              <a class="btn btn-sm btn-outline-danger" href="<?= e(base_url('admin/blog.php?action=delete&id=' . (int)$it['id'] . '&token=' . csrf_token())) ?>" onclick="return confirm('Delete this post?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
