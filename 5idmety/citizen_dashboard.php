<?php
include 'config.php';
session_start();

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    header("Location: auth.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'مواطن';

// جلب إحصائيات المستخدم
try {
    // التأكد من وجود الجداول
    $stmt = $db->prepare("SHOW TABLES LIKE 'service_requests'");
    $stmt->execute();
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        // عدد الطلبات
        $stmt = $db->prepare("SELECT COUNT(*) as total_requests FROM service_requests WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $total_requests = $stmt->fetch()['total_requests'];
        
        // الطلبات المعلقة
        $stmt = $db->prepare("SELECT COUNT(*) as pending_requests FROM service_requests WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$user_id]);
        $pending_requests = $stmt->fetch()['pending_requests'];
        
        // الطلبات المكتملة
        $stmt = $db->prepare("SELECT COUNT(*) as completed_requests FROM service_requests WHERE user_id = ? AND status = 'completed'");
        $stmt->execute([$user_id]);
        $completed_requests = $stmt->fetch()['completed_requests'];
        
        // آخر الطلبات
        $stmt = $db->prepare("
            SELECT sr.*, 'خدمة عامة' as service_name 
            FROM service_requests sr 
            WHERE sr.user_id = ? 
            ORDER BY sr.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $recent_requests = $stmt->fetchAll();
    } else {
        $total_requests = $pending_requests = $completed_requests = 0;
        $recent_requests = [];
    }
    
    // جلب الخدمات المتاحة (إنشاء قائمة افتراضية)
    $services = [
        ['id' => 1, 'name_ar' => 'خدمات البلدية', 'description_ar' => 'طلبات البلدية والقرى', 'category' => 'municipal'],
        ['id' => 2, 'name_ar' => 'خدمات التعليم', 'description_ar' => 'خدمات المدارس والمعاهد', 'category' => 'education'],
        ['id' => 3, 'name_ar' => 'خدمات الصحة', 'description_ar' => 'الخدمات الصحية', 'category' => 'health'],
        ['id' => 4, 'name_ar' => 'خدمات النقل', 'description_ar' => 'خدمات النقل والمواصلات', 'category' => 'transport'],
        ['id' => 5, 'name_ar' => 'خدمات اجتماعية', 'description_ar' => 'الخدمات الاجتماعية', 'category' => 'social'],
        ['id' => 6, 'name_ar' => 'خدمات قانونية', 'description_ar' => 'الخدمات القانونية', 'category' => 'legal']
    ];
    
} catch (PDOException $e) {
    error_log("Database error in citizen dashboard: " . $e->getMessage());
    $total_requests = $pending_requests = $completed_requests = 0;
    $recent_requests = [];
    $services = [];
}

// معالجة تقديم طلب جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $service_id = sanitize($_POST['service_id']);
        $description = sanitize($_POST['description']);
        $priority = sanitize($_POST['priority']);
        
        if (!empty($service_id) && !empty($description)) {
            try {
                $tracking_number = generateTrackingNumber();
                
                // إنشاء جدول الطلبات إذا لم يكن موجوداً
                $db->exec("CREATE TABLE IF NOT EXISTS service_requests (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    service_id INT NOT NULL,
                    description TEXT NOT NULL,
                    priority ENUM('normal', 'high', 'urgent') DEFAULT 'normal',
                    tracking_number VARCHAR(20) UNIQUE NOT NULL,
                    status ENUM('pending', 'in_progress', 'completed', 'rejected') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )");
                
                $stmt = $db->prepare("
                    INSERT INTO service_requests (user_id, service_id, description, priority, tracking_number, status) 
                    VALUES (?, ?, ?, ?, ?, 'pending')
                ");
                
                if ($stmt->execute([$user_id, $service_id, $description, $priority, $tracking_number])) {
                    $_SESSION['success_message'] = "تم تقديم طلبك بنجاح. رقم التتبع: " . $tracking_number;
                    logActivity($db, $user_id, 'request_submitted', "تم تقديم طلب جديد برقم التتبع: $tracking_number");
                    header("Location: citizen_dashboard.php");
                    exit();
                } else {
                    $error_message = "حدث خطأ أثناء تقديم الطلب";
                }
            } catch (PDOException $e) {
                error_log("Error submitting request: " . $e->getMessage());
                $error_message = "حدث خطأ في النظام";
            }
        } else {
            $error_message = "يرجى ملء جميع الحقول المطلوبة";
        }
    } else {
        $error_message = "رمز الأمان غير صحيح";
    }
}

// رسائل النجاح والخطأ
$success_message = $_SESSION['success_message'] ?? '';
$login_success = $_SESSION['login_success'] ?? '';
$welcome_message = $_SESSION['welcome_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['login_success'], $_SESSION['welcome_message']);
$error_message = $error_message ?? '';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المواطن - منصة موريتانيا</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap');
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8fafc;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .service-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 24px;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="gradient-bg shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-flag text-white text-3xl"></i>
                    </div>
                    <div class="mr-4">
                        <h1 class="text-2xl font-bold text-white">منصة موريتانيا للخدمات</h1>
                        <p class="text-blue-100">لوحة تحكم المواطن</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4 space-x-reverse">
                    <div class="text-white text-left">
                        <p class="text-sm text-blue-100">مرحباً</p>
                        <p class="font-medium"><?= htmlspecialchars($user_name) ?></p>
                    </div>
                    <div class="relative">
                        <button class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white p-2 rounded-full transition">
                            <i class="fas fa-user"></i>
                        </button>
                    </div>
                    <a href="auth.php?action=logout" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-sign-out-alt ml-1"></i>
                        تسجيل الخروج
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- رسائل النجاح والخطأ -->
        <?php if ($welcome_message): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-check-circle ml-2"></i>
                    <span><?= $welcome_message ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($login_success): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-check-circle ml-2"></i>
                    <span><?= $login_success ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-check-circle ml-2"></i>
                    <span><?= $success_message ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle ml-2"></i>
                    <span><?= $error_message ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- إحصائيات سريعة -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="service-icon bg-blue-500">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm font-medium text-gray-600">إجمالي الطلبات</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $total_requests ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="service-icon bg-yellow-500">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm font-medium text-gray-600">طلبات معلقة</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $pending_requests ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="service-icon bg-green-500">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm font-medium text-gray-600">طلبات مكتملة</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $completed_requests ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- نموذج تقديم طلب جديد -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-plus-circle text-green-600 ml-2"></i>
                    تقديم طلب جديد
                </h2>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div>
                        <label for="service_id" class="block text-sm font-medium text-gray-700 mb-2">
                            نوع الخدمة
                        </label>
                        <select id="service_id" name="service_id" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">اختر الخدمة</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?= $service['id'] ?>"><?= htmlspecialchars($service['name_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            وصف الطلب
                        </label>
                        <textarea id="description" name="description" rows="4" required 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                  placeholder="اشرح طلبك بالتفصيل..."></textarea>
                    </div>
                    
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">
                            الأولوية
                        </label>
                        <select id="priority" name="priority" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="normal">عادية</option>
                            <option value="high">عالية</option>
                            <option value="urgent">عاجلة</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="submit_request" 
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                        <i class="fas fa-paper-plane ml-2"></i>
                        تقديم الطلب
                    </button>
                </form>
            </div>

            <!-- آخر الطلبات -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-history text-blue-600 ml-2"></i>
                    آخر الطلبات
                </h2>
                
                <?php if (empty($recent_requests)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-4"></i>
                        <p>لا توجد طلبات بعد</p>
                        <p class="text-sm">قم بتقديم طلبك الأول</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_requests as $request): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-medium text-gray-900"><?= htmlspecialchars($request['service_name']) ?></h3>
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?php 
                                        switch($request['status']) {
                                            case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'in_progress': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'completed': echo 'bg-green-100 text-green-800'; break;
                                            case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php
                                        switch($request['status']) {
                                            case 'pending': echo 'معلق'; break;
                                            case 'in_progress': echo 'قيد المعالجة'; break;
                                            case 'completed': echo 'مكتمل'; break;
                                            case 'rejected': echo 'مرفوض'; break;
                                            default: echo $request['status'];
                                        }
                                        ?>
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-2"><?= htmlspecialchars(substr($request['description'], 0, 100)) ?>...</p>
                                <div class="flex justify-between items-center text-xs text-gray-500">
                                    <span>رقم التتبع: <?= htmlspecialchars($request['tracking_number']) ?></span>
                                    <span><?= date('Y-m-d H:i', strtotime($request['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <a href="my_requests.php" class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                            عرض جميع الطلبات
                            <i class="fas fa-arrow-left mr-1"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- شبكة الخدمات المتاحة -->
        <div class="mt-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">
                <i class="fas fa-th-large text-purple-600 ml-2"></i>
                الخدمات المتاحة
            </h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php 
                $service_icons = [
                    'municipal' => 'fas fa-city',
                    'health' => 'fas fa-heartbeat',
                    'education' => 'fas fa-graduation-cap',
                    'transport' => 'fas fa-bus',
                    'social' => 'fas fa-users',
                    'legal' => 'fas fa-gavel',
                    'economic' => 'fas fa-chart-line',
                    'environment' => 'fas fa-leaf'
                ];
                
                $service_colors = [
                    'municipal' => 'bg-blue-500',
                    'health' => 'bg-red-500',
                    'education' => 'bg-green-500',
                    'transport' => 'bg-yellow-500',
                    'social' => 'bg-purple-500',
                    'legal' => 'bg-indigo-500',
                    'economic' => 'bg-pink-500',
                    'environment' => 'bg-teal-500'
                ];
                
                foreach ($services as $service): 
                    $icon = $service_icons[$service['category']] ?? 'fas fa-cog';
                    $color = $service_colors[$service['category']] ?? 'bg-gray-500';
                ?>
                    <div class="bg-white rounded-lg shadow-md p-6 card-hover cursor-pointer" 
                         onclick="selectService(<?= $service['id'] ?>, '<?= htmlspecialchars($service['name_ar']) ?>')">
                        <div class="text-center">
                            <div class="service-icon <?= $color ?> mx-auto mb-4">
                                <i class="<?= $icon ?>"></i>
                            </div>
                            <h3 class="font-medium text-gray-900 mb-2"><?= htmlspecialchars($service['name_ar']) ?></h3>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($service['description_ar']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function selectService(serviceId, serviceName) {
            document.getElementById('service_id').value = serviceId;
            document.getElementById('description').focus();
            
            // Scroll to form
            document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
        }
        
        // Auto-hide success messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.bg-green-50');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
