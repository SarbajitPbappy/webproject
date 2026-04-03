<?php
/**
 * HostelEase — Main Layout Template
 * 
 * Shared layout with sidebar, navbar, and footer.
 * Variables expected: $pageTitle, $content (or include body via sections)
 */
$user = currentUser();
$currentUrl = $_GET['url'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HostelEase — Web-Based Hostel Management System">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' | ' : ''; ?><?php echo APP_NAME; ?></title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>public/css/custom.css" rel="stylesheet">
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar -->
        <?php require_once APP_ROOT . '/views/layouts/partials/sidebar.php'; ?>

        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Top Navbar -->
            <?php require_once APP_ROOT . '/views/layouts/partials/navbar.php'; ?>

            <!-- Page Content -->
            <main class="content-area">
                <!-- Flash Messages -->
                <?php
                $flashTypes = ['success', 'error', 'warning', 'info'];
                foreach ($flashTypes as $type):
                    $message = getFlash($type);
                    if ($message):
                        $bsClass = $type === 'error' ? 'danger' : $type;
                ?>
                <div class="alert alert-<?php echo $bsClass; ?> alert-dismissible fade show animate-slide-down" role="alert">
                    <i class="bi bi-<?php echo $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-triangle' : ($type === 'warning' ? 'exclamation-circle' : 'info-circle')); ?> me-2"></i>
                    <?php echo e($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php
                    endif;
                endforeach;
                ?>

                <!-- Page Body (injected by each view) -->
                <?php if (isset($viewContent)) echo $viewContent; ?>
            </main>

            <!-- Footer -->
            <?php require_once APP_ROOT . '/views/layouts/partials/footer.php'; ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>public/js/main.js"></script>
    
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
