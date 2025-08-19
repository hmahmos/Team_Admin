<?php
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = (int)$_POST['request_id'];
    $status = $_POST['status'];
    $admin_comment = trim($_POST['admin_comment']);
    
    if (in_array($status, ['pending', 'in_progress', 'approved', 'rejected'])) {
        $stmt = $pdo->prepare("UPDATE requests SET status = ?, admin_comment = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $admin_comment, $request_id]);
        
        // Send notification to user (you can implement email notification here)
        $success_message = t('status_updated', 'تم تحديث حالة الطلب بنجاح', 'Statut de la demande mis à jour avec succès');
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "
    SELECT r.*, s.name_ar, s.name_fr, s.category, u.full_name as user_name, u.email, u.phone
    FROM requests r 
    JOIN services s ON r.service_id = s.id 
    JOIN users u ON r.user_id = u.id 
    WHERE 1=1
";
$params = [];

if ($status_filter) {
    $query .= " AND r.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $query .= " AND (r.tracking_number LIKE ? OR u.full_name LIKE ? OR r.details LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY r.created_at DESC LIMIT 50";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM requests
";
$stmt = $pdo->prepare($stats_query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>" dir="<?php echo getCurrentLanguage() === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('admin_dashboard', 'لوحة تحكم الإدارة', 'Tableau de bord Admin'); ?> - Mauritania Services</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .rtl { direction: rtl; }
        .ltr { direction: ltr; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-in_progress { background-color: #dbeafe; color: #1e40af; }
        .status-approved { background-color: #d1fae5; color: #065f46; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <i class="fas fa-shield-alt text-2xl text-blue-600 mr-3"></i>
                    <h1 class="text-xl font-bold text-gray-900">
                        <?php echo t('admin_panel', 'لوحة تحكم الإدارة', 'Panneau d\'administration'); ?>
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">
                        <?php echo t('welcome', 'مرحباً', 'Bienvenue'); ?>, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt"></i> <?php echo t('logout', 'خروج', 'Déconnexion'); ?>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="bg-blue-600 text-white p-6 rounded-lg shadow-md">
                <div class="flex items-center">
                    <i class="fas fa-list text-3xl mr-4"></i>
                    <div>
                        <h3 class="text-2xl font-bold"><?php echo $stats['total']; ?></h3>
                        <p class="text-blue-100"><?php echo t('total_requests', 'إجمالي الطلبات', 'Total des demandes'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-yellow-600 text-white p-6 rounded-lg shadow-md">
                <div class="flex items-center">
                    <i class="fas fa-clock text-3xl mr-4"></i>
                    <div>
                        <h3 class="text-2xl font-bold"><?php echo $stats['pending']; ?></h3>
                        <p class="text-yellow-100"><?php echo t('pending', 'قيد الانتظار', 'En attente'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-blue-500 text-white p-6 rounded-lg shadow-md">
                <div class="flex items-center">
                    <i class="fas fa-cog text-3xl mr-4"></i>
                    <div>
                        <h3 class="text-2xl font-bold"><?php echo $stats['in_progress']; ?></h3>
                        <p class="text-blue-100"><?php echo t('in_progress', 'قيد المعالجة', 'En cours'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-green-600 text-white p-6 rounded-lg shadow-md">
                <div class="flex items-center">
                    <i class="fas fa-check text-3xl mr-4"></i>
                    <div>
                        <h3 class="text-2xl font-bold"><?php echo $stats['approved']; ?></h3>
                        <p class="text-green-100"><?php echo t('approved', 'مقبول', 'Approuvé'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-red-600 text-white p-6 rounded-lg shadow-md">
                <div class="flex items-center">
                    <i class="fas fa-times text-3xl mr-4"></i>
                    <div>
                        <h3 class="text-2xl font-bold"><?php echo $stats['rejected']; ?></h3>
                        <p class="text-red-100"><?php echo t('rejected', 'مرفوض', 'Rejeté'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo t('search', 'بحث', 'Rechercher'); ?>
                    </label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="<?php echo t('search_placeholder', 'رقم التتبع، اسم المواطن، أو تفاصيل الطلب', 'N° de suivi, nom du citoyen, ou détails'); ?>"
                           class="px-3 py-2 border border-gray-300 rounded-md w-64">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo t('filter_status', 'تصفية حسب الحالة', 'Filtrer par statut'); ?>
                    </label>
                    <select name="status" class="px-3 py-2 border border-gray-300 rounded-md">
                        <option value=""><?php echo t('all_statuses', 'جميع الحالات', 'Tous les statuts'); ?></option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>
                            <?php echo t('pending', 'قيد الانتظار', 'En attente'); ?>
                        </option>
                        <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>
                            <?php echo t('in_progress', 'قيد المعالجة', 'En cours'); ?>
                        </option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>
                            <?php echo t('approved', 'مقبول', 'Approuvé'); ?>
                        </option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>
                            <?php echo t('rejected', 'مرفوض', 'Rejeté'); ?>
                        </option>
                    </select>
                </div>
                
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <?php echo t('filter', 'تصفية', 'Filtrer'); ?>
                </button>
                
                <a href="admin_dashboard.php" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                    <?php echo t('clear', 'مسح', 'Effacer'); ?>
                </a>
            </form>
        </div>

        <!-- Requests Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">
                    <?php echo t('all_requests', 'جميع الطلبات', 'Toutes les demandes'); ?>
                </h2>
            </div>
            
            <?php if (empty($requests)): ?>
                <div class="p-6 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-4"></i>
                    <p><?php echo t('no_requests_found', 'لا توجد طلبات', 'Aucune demande trouvée'); ?></p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <?php echo t('tracking_number', 'رقم التتبع', 'N° de suivi'); ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <?php echo t('citizen', 'المواطن', 'Citoyen'); ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <?php echo t('service', 'الخدمة', 'Service'); ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <?php echo t('status', 'الحالة', 'Statut'); ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <?php echo t('date', 'التاريخ', 'Date'); ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <?php echo t('actions', 'الإجراءات', 'Actions'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($requests as $request): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                        <?php echo htmlspecialchars($request['tracking_number']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($request['user_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($request['email']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo getCurrentLanguage() === 'ar' ? $request['name_ar'] : $request['name_fr']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-<?php echo $request['status']; ?>">
                                            <?php 
                                            $status_texts = [
                                                'pending' => t('status_pending', 'قيد المراجعة', 'En attente'),
                                                'in_progress' => t('status_in_progress', 'قيد المعالجة', 'En cours'),
                                                'approved' => t('status_approved', 'مقبول', 'Approuvé'),
                                                'rejected' => t('status_rejected', 'مرفوض', 'Rejeté')
                                            ];
                                            echo $status_texts[$request['status']];
                                            ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('Y-m-d H:i', strtotime($request['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="openModal(<?php echo htmlspecialchars(json_encode($request)); ?>)" 
                                                class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i> <?php echo t('manage', 'إدارة', 'Gérer'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Request Management Modal -->
    <div id="requestModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl mx-4 max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-800">
                        <?php echo t('manage_request', 'إدارة الطلب', 'Gérer la demande'); ?>
                    </h3>
                    <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" id="modal_request_id" name="request_id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php echo t('tracking_number', 'رقم التتبع', 'N° de suivi'); ?>
                            </label>
                            <p id="modal_tracking" class="text-sm text-gray-900 font-mono bg-gray-100 p-2 rounded"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php echo t('citizen', 'المواطن', 'Citoyen'); ?>
                            </label>
                            <div id="modal_citizen" class="text-sm text-gray-900 bg-gray-100 p-2 rounded"></div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo t('service', 'الخدمة', 'Service'); ?>
                        </label>
                        <p id="modal_service" class="text-sm text-gray-900 bg-gray-100 p-2 rounded"></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo t('request_details', 'تفاصيل الطلب', 'Détails de la demande'); ?>
                        </label>
                        <div id="modal_details" class="text-sm text-gray-900 bg-gray-100 p-3 rounded min-h-20"></div>
                    </div>
                    
                    <div id="modal_attachment_section" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo t('attachment', 'المرفق', 'Pièce jointe'); ?>
                        </label>
                        <a id="modal_attachment_link" href="#" target="_blank" 
                           class="text-blue-600 hover:text-blue-800 flex items-center">
                            <i class="fas fa-paperclip mr-2"></i>
                            <?php echo t('view_attachment', 'عرض المرفق', 'Voir la pièce jointe'); ?>
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php echo t('status', 'الحالة', 'Statut'); ?>
                            </label>
                            <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="pending"><?php echo t('status_pending', 'قيد المراجعة', 'En attente'); ?></option>
                                <option value="in_progress"><?php echo t('status_in_progress', 'قيد المعالجة', 'En cours'); ?></option>
                                <option value="approved"><?php echo t('status_approved', 'مقبول', 'Approuvé'); ?></option>
                                <option value="rejected"><?php echo t('status_rejected', 'مرفوض', 'Rejeté'); ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php echo t('current_status', 'الحالة الحالية', 'Statut actuel'); ?>
                            </label>
                            <p id="modal_current_status" class="text-sm bg-gray-100 p-2 rounded"></p>
                        </div>
                    </div>
                    
                    <div>
                        <label for="admin_comment" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo t('admin_comment', 'تعليق الإدارة', 'Commentaire administrateur'); ?>
                        </label>
                        <textarea id="admin_comment" name="admin_comment" rows="4" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                  placeholder="<?php echo t('comment_placeholder', 'اكتب تعليقك أو ملاحظاتك هنا...', 'Écrivez votre commentaire ou vos notes ici...'); ?>"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-4 pt-4 border-t">
                        <button type="button" onclick="closeModal()" 
                                class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            <?php echo t('cancel', 'إلغاء', 'Annuler'); ?>
                        </button>
                        <button type="submit" name="update_status" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <i class="fas fa-save mr-2"></i>
                            <?php echo t('update', 'تحديث', 'Mettre à jour'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function openModal(request) {
        document.getElementById('modal_request_id').value = request.id;
        document.getElementById('modal_tracking').textContent = request.tracking_number;
        
        // Citizen info
        const citizenInfo = `
            <div><strong><?php echo t('name', 'الاسم', 'Nom'); ?>:</strong> ${request.user_name}</div>
            <div><strong><?php echo t('email', 'البريد الإلكتروني', 'Email'); ?>:</strong> ${request.email}</div>
            ${request.phone ? `<div><strong><?php echo t('phone', 'الهاتف', 'Téléphone'); ?>:</strong> ${request.phone}</div>` : ''}
        `;
        document.getElementById('modal_citizen').innerHTML = citizenInfo;
        
        // Service name
        const serviceName = <?php echo getCurrentLanguage() === 'ar' ? 'request.name_ar' : 'request.name_fr'; ?>;
        document.getElementById('modal_service').textContent = serviceName;
        
        // Request details
        document.getElementById('modal_details').textContent = request.details || '<?php echo t('no_details', 'لا توجد تفاصيل', 'Aucun détail'); ?>';
        
        // Current status
        const statusTexts = {
            'pending': '<?php echo t('status_pending', 'قيد المراجعة', 'En attente'); ?>',
            'in_progress': '<?php echo t('status_in_progress', 'قيد المعالجة', 'En cours'); ?>',
            'approved': '<?php echo t('status_approved', 'مقبول', 'Approuvé'); ?>',
            'rejected': '<?php echo t('status_rejected', 'مرفوض', 'Rejeté'); ?>'
        };
        document.getElementById('modal_current_status').textContent = statusTexts[request.status];
        
        // Set form values
        document.getElementById('status').value = request.status;
        document.getElementById('admin_comment').value = request.admin_comment || '';
        
        // Handle attachment
        if (request.attachment_path) {
            document.getElementById('modal_attachment_section').classList.remove('hidden');
            document.getElementById('modal_attachment_link').href = request.attachment_path;
        } else {
            document.getElementById('modal_attachment_section').classList.add('hidden');
        }
        
        document.getElementById('requestModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('requestModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    document.getElementById('requestModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
    </script>
</body>
</html>
