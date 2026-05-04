<?php
$content = $content ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?? 'My Project' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">
    
<?php if(isset($_SESSION['success'])): ?>
<script>
    Swal.fire({
        title: "Success!",
        text: '<?= $_SESSION['success'] ?>',
        icon: "success"
    });
</script>
<?php unset($_SESSION['success']); endif; ?>

<?php if(isset($_SESSION['error'])): ?>
<script>
    Swal.fire({
        title: "Error!",
        text: '<?= $_SESSION['error'] ?>',
        icon: "error"
    });
</script>
<?php unset($_SESSION['error']); endif; ?>

<nav class="navbar navbar-dark bg-dark px-3">
    <a class="navbar-brand" href="/">My Project</a>
    <div>
        <a class="btn btn-light btn-sm" href="/">Home</a>
        <a class="btn btn-success btn-sm" href="/views/users/create.php">Create User</a>
        <a class="btn btn-secondary btn-sm" href="/views/users/index.php">User List</a>
    </div>
</nav>

<div class="container mt-4 flex-grow-1">
    /**
     * Renders the main content area of the layout template.
     * 
     * This variable contains the rendered HTML output from the controller action
     * that was passed to the layout view. It represents the page-specific content
     * that should be displayed within the layout wrapper (header, footer, navigation, etc).
     * 
     * The $content variable is typically set in the controller before rendering the layout,
     * containing the output from the view file associated with the current action.
     * 
     * @var string $content The rendered HTML content of the current page
     */
    <?= $content ?>
</div>

<footer class="text-center mt-auto text-white bg-success p-3">
    <small>&copy; <?= date('Y') ?> My Project</small>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

</body>
</html>