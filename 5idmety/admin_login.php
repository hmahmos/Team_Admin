<?php
require_once 'config.php';

// Redirect if already logged in as admin
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'جميع الحقول مطلوبة / Tous les champs sont requis';
    } else {
        // Check admin credentials
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['role'] = 'admin';
            $_SESSION['user_name'] = $admin['full_name'];
            header('Location: admin_dashboard.php');
            exit();
        } else {
            $error = 'بيانات الدخول غير صحيحة / Identifiants incorrects';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>" dir="<?php echo getCurrentLanguage() === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('admin_login', 'دخول الإدارة', 'Connexion Admin'); ?> - Mauritania Services</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .rtl { direction: rtl; }
        .ltr { direction: ltr; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-8">
            <i class="fas fa-shield-alt text-4xl text-blue-600 mb-4"></i>
            <h1 class="text-2xl font-bold text-gray-900">
                <?php echo t('admin_login', 'دخول الإدارة', 'Connexion Admin'); ?>
            </h1>
            <p class="text-gray-600 mt-2">
                <?php echo t('admin_login_desc', 'دخول لوحة تحكم الإدارة', 'Accès au panneau d\'administration'); ?>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    <?php echo t('email', 'البريد الإلكتروني', 'Email'); ?>
                </label>
                <input type="email" id="email" name="email" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="admin@example.com">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    <?php echo t('password', 'كلمة المرور', 'Mot de passe'); ?>
                </label>
                <input type="password" id="password" name="password" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <?php echo t('login', 'دخول', 'Se connecter'); ?>
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="index.php" class="text-blue-600 hover:text-blue-800">
                <?php echo t('back_home', 'العودة للرئيسية', 'Retour à l\'accueil'); ?>
            </a>
        </div>
    </div>
</body>
</html>
