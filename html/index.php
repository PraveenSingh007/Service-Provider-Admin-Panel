<?php

declare(strict_types=1);

// Root entry point redirecting to src/admin or src/user
if (isset($_GET['panel']) && $_GET['panel'] === 'admin') {
    // Redirect to admin panel if 'panel' parameter is set to 'admin'
    header('Location: src/admin/index.php');
    exit;
}

header('Location: src/user/index.php');
exit;
