<?php
/**
 * HostelEase — Auth Layout Template
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' | ' : ''; ?><?php echo APP_NAME; ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>public/css/custom.css" rel="stylesheet">
    
    <style>
        body.auth-split-body { background: #f8fafc; min-height: 100vh; font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .auth-split-container { background: #fff; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.08); overflow: hidden; width: 100%; max-width: 1100px; display: flex; flex-direction: column; }
        @media (min-width: 992px) { .auth-split-container { flex-direction: row; min-height: 650px; } }
        /* Flash messages float */
        .toast-container { position: fixed; top: 1rem; right: 1rem; z-index: 9999; }
    </style>
</head>
<body class="auth-split-body">

    <!-- Flash Messages (Top Right) -->
    <div class="toast-container">
        <?php
        $flashTypes = ['success', 'error', 'warning', 'info'];
        foreach ($flashTypes as $type):
            $message = getFlash($type);
            if ($message):
                $bsClass = $type === 'error' ? 'danger' : $type;
        ?>
        <div class="alert alert-<?php echo $bsClass; ?> alert-dismissible fade show shadow-sm" role="alert">
            <?php echo e($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php
            endif;
        endforeach;
        ?>
    </div>

    <!-- Main Content injected here (will contain left panel and right form) -->
    <div class="auth-split-container">
        <?php if (isset($viewContent)) echo $viewContent; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>public/js/main.js"></script>
</body>
</html>
