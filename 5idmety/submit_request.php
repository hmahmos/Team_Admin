<?php
include 'config.php';
session_start();

if (!isLoggedIn() || !hasRole('citizen')) {
    header("Location: auth.php");
    exit();
}

$error = '';
$success = '';

// Récupérer les services
$services = $db->query("SELECT * FROM services WHERE is_active = 1 ORDER BY service_name_ar")->fetchAll();

// Récupérer les types de requêtes pour un service sélectionné
$request_types = [];
if (isset($_GET['service_id'])) {
    $service_id = (int)$_GET['service_id'];
    $stmt = $db->prepare("SELECT * FROM request_types WHERE service_id = ? ORDER BY type_name_ar");
    $stmt->execute([$service_id]);
    $request_types = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Token de sécurité invalide";
    } else {
        $service_id = (int)$_POST['service_id'];
        $request_type_id = (int)$_POST['request_type_id'];
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $priority = sanitize($_POST['priority']);
        
        // Générer un numéro de suivi unique
        $tracking_number = 'MR' . date('Y') . sprintf('%06d', mt_rand(1, 999999));
        
        // Vérifier que le numéro de suivi est unique
        $stmt = $db->prepare("SELECT id FROM requests WHERE tracking_number = ?");
        $stmt->execute([$tracking_number]);
        while ($stmt->rowCount() > 0) {
            $tracking_number = 'MR' . date('Y') . sprintf('%06d', mt_rand(1, 999999));
            $stmt->execute([$tracking_number]);
        }
        
        try {
            $db->beginTransaction();
            
            // Insérer la requête
            $stmt = $db->prepare("
                INSERT INTO requests (user_id, service_id, request_type_id, title, description, priority, tracking_number) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $service_id, $request_type_id, $title, $description, $priority, $tracking_number]);
            $request_id = $db->lastInsertId();
            
            // Gérer les fichiers uploadés
            if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
                $upload_dir = 'uploads/requests/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($_FILES['files']['name'] as $key => $filename) {
                    if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_extension = pathinfo($filename, PATHINFO_EXTENSION);
                        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
                        
                        if (in_array(strtolower($file_extension), $allowed_extensions)) {
                            $new_filename = $request_id . '_' . time() . '_' . $key . '.' . $file_extension;
                            $file_path = $upload_dir . $new_filename;
                            
                            if (move_uploaded_file($_FILES['files']['tmp_name'][$key], $file_path)) {
                                $stmt = $db->prepare("
                                    INSERT INTO request_files (request_id, filename, original_name, file_size, mime_type) 
                                    VALUES (?, ?, ?, ?, ?)
                                ");
                                $stmt->execute([
                                    $request_id,
                                    $new_filename,
                                    $filename,
                                    $_FILES['files']['size'][$key],
                                    $_FILES['files']['type'][$key]
                                ]);
                            }
                        }
                    }
                }
            }
            
            // Créer une notification
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, title, message, type) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                'تم تقديم طلبك بنجاح',
                "تم تقديم طلبك برقم التتبع: $tracking_number وسيتم مراجعته قريباً",
                'success'
            ]);
            
            // Log de l'activité
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                'request_submitted',
                "تم تقديم طلب جديد برقم التتبع: $tracking_number",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            ]);
            
            $db->commit();
            
            $_SESSION['message'] = "تم تقديم طلبك بنجاح! رقم التتبع: $tracking_number";
            header("Location: citizen_dashboard.php");
            exit();
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = "حدث خطأ أثناء تقديم الطلب. يرجى المحاولة مرة أخرى.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقديم طلب جديد - منصة موريتانيا</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap');
        
        body {
            font-family: 'Tajawal', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <div class="h-8 w-8 bg-green-600 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-flag text-white text-sm"></i>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900">تقديم طلب جديد</h1>
                </div>
                
                <div class="flex items-center space-x-4 space-x-reverse">
                    <a href="citizen_dashboard.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-right text-xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                <span class="block sm:inline"><?= $error ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
                <span class="block sm:inline"><?= $success ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">معلومات الطلب</h3>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Service Selection -->
                    <div>
                        <label for="service_id" class="block text-sm font-medium text-gray-700 mb-2">اختر الخدمة</label>
                        <select id="service_id" name="service_id" required 
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                            <option value="">اختر الخدمة...</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?= $service['id'] ?>" <?= isset($_GET['service_id']) && $_GET['service_id'] == $service['id'] ? 'selected' : '' ?>>
                                    <?= $service['service_name_ar'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Request Type Selection -->
                    <div id="request_type_container" style="display: <?= !empty($request_types) ? 'block' : 'none' ?>;">
                        <label for="request_type_id" class="block text-sm font-medium text-gray-700 mb-2">نوع الطلب</label>
                        <select id="request_type_id" name="request_type_id" required 
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                            <option value="">اختر نوع الطلب...</option>
                            <?php foreach ($request_types as $type): ?>
                                <option value="<?= $type['id'] ?>" data-description="<?= $type['description'] ?>" data-documents="<?= $type['required_documents'] ?>">
                                    <?= $type['type_name_ar'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <div id="type_info" class="mt-2 p-3 bg-blue-50 rounded-md hidden">
                            <div id="type_description" class="text-sm text-blue-800 mb-2"></div>
                            <div id="required_documents" class="text-sm text-blue-600"></div>
                        </div>
                    </div>
                    
                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">عنوان الطلب</label>
                        <input type="text" id="title" name="title" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                               placeholder="أدخل عنوان مختصر للطلب">
                    </div>
                    
                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">تفاصيل الطلب</label>
                        <textarea id="description" name="description" rows="4" required 
                                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                  placeholder="اشرح تفاصيل طلبك بوضوح..."></textarea>
                    </div>
                    
                    <!-- Priority -->
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">أولوية الطلب</label>
                        <select id="priority" name="priority" 
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                            <option value="low">منخفضة</option>
                            <option value="medium" selected>متوسطة</option>
                            <option value="high">عالية</option>
                            <option value="urgent">عاجلة</option>
                        </select>
                    </div>
                    
                    <!-- File Upload -->
                    <div>
                        <label for="files" class="block text-sm font-medium text-gray-700 mb-2">المرفقات</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                            <div class="space-y-1 text-center">
                                <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl"></i>
                                <div class="flex text-sm text-gray-600">
                                    <label for="files" class="relative cursor-pointer bg-white rounded-md font-medium text-green-600 hover:text-green-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-green-500">
                                        <span>ارفع الملفات</span>
                                        <input id="files" name="files[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="sr-only">
                                    </label>
                                    <p class="pr-1">أو اسحب وأفلت</p>
                                </div>
                                <p class="text-xs text-gray-500">PDF, JPG, PNG, DOC حتى 10MB</p>
                            </div>
                        </div>
                        <div id="file_list" class="mt-2"></div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3 space-x-reverse">
                        <a href="citizen_dashboard.php" 
                           class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            إلغاء
                        </a>
                        <button type="submit" 
                                class="bg-green-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            تقديم الطلب
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const serviceSelect = document.getElementById('service_id');
            const requestTypeContainer = document.getElementById('request_type_container');
            const requestTypeSelect = document.getElementById('request_type_id');
            const typeInfo = document.getElementById('type_info');
            const typeDescription = document.getElementById('type_description');
            const requiredDocuments = document.getElementById('required_documents');
            const filesInput = document.getElementById('files');
            const fileList = document.getElementById('file_list');

            // Handle service selection
            serviceSelect.addEventListener('change', function() {
                if (this.value) {
                    // Redirect to load request types
                    window.location.href = 'submit_request.php?service_id=' + this.value;
                } else {
                    requestTypeContainer.style.display = 'none';
                    typeInfo.classList.add('hidden');
                }
            });

            // Handle request type selection
            requestTypeSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value) {
                    const description = selectedOption.getAttribute('data-description');
                    const documents = selectedOption.getAttribute('data-documents');
                    
                    typeDescription.textContent = description || '';
                    requiredDocuments.innerHTML = documents ? '<strong>المستندات المطلوبة:</strong> ' + documents : '';
                    typeInfo.classList.remove('hidden');
                } else {
                    typeInfo.classList.add('hidden');
                }
            });

            // Handle file selection
            filesInput.addEventListener('change', function() {
                fileList.innerHTML = '';
                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    const fileDiv = document.createElement('div');
                    fileDiv.className = 'flex items-center justify-between p-2 bg-gray-50 rounded mt-1';
                    fileDiv.innerHTML = `
                        <span class="text-sm text-gray-700">
                            <i class="fas fa-file mr-2"></i>
                            ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                        </span>
                    `;
                    fileList.appendChild(fileDiv);
                }
            });
        });
    </script>
</body>
</html>
