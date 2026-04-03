<?php
/**
 * HostelEase — 404 Error Page
 */
$pageTitle = '404 — Page Not Found';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' | ' . APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .error-container {
            text-align: center;
            padding: 3rem;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
        }
        .error-message {
            font-size: 1.5rem;
            font-weight: 300;
            color: rgba(255,255,255,0.7);
            margin-bottom: 2rem;
        }
        .btn-back {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: #fff;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <p class="error-message">Oops! The page you're looking for doesn't exist.</p>
        <a href="<?php echo BASE_URL; ?>" class="btn-back">
            ← Back to Home
        </a>
    </div>
</body>
</html>
