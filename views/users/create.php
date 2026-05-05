<?php
$title = "Create User";
require_once '../../autoload.php';
ob_start();
?>

<h2>Create User</h2>

<form action="<?= BASE_URL ?>controllers/users/create.php" method="POST" class="card p-3">

    <?= csrf_field() ?>
    <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-success">Save</button>
</form>

<?php
$content = ob_get_clean();
include '../layout.php';