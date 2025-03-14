<?php
// Initialisation de la session
session_start();

// Vérification des droits d'administration
if (!(isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ) {
    // Redirection vers la page d'accueil si l'utilisateur n'est pas administrateur
    header('Location: ../index.php');
    exit;
}

// Génération d'un token CSRF pour la sécurité
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['admin_csrf_token'] = $csrf_token;

// Connexion à la base de données
require_once '../config/database.php';
global $pdo;
$db = $pdo; // Utiliser la connexion PDO existante
if (!$db) {
    die("Erreur de connexion à la base de données.");
}

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Récupération des statistiques globales
try {
    // Nombre total d'utilisateurs actifs (ayant au moins un message sur le forum)
    $stmt = $db->query("SELECT COUNT(DISTINCT user_id) as active_users FROM 
                        (SELECT user_id FROM forum_topics 
                         UNION 
                         SELECT user_id FROM forum_replies) as active_users_table");
    $active_users_count = $stmt->fetchColumn();
    
    // Nombre total de discussions
    $stmt = $db->query("SELECT COUNT(*) as topics_count FROM forum_topics");
    $topics_count = $stmt->fetchColumn();
    
    // Nombre total de réponses
    $stmt = $db->query("SELECT COUNT(*) as replies_count FROM forum_replies");
    $replies_count = $stmt->fetchColumn();
    
    // Nombre total de fichiers partagés
    $stmt = $db->query("SELECT COUNT(*) as files_count FROM 
                        (SELECT id FROM forum_topics WHERE file_path IS NOT NULL AND file_path != ''
                         UNION ALL
                         SELECT id FROM forum_replies WHERE file_path IS NOT NULL AND file_path != '') as files_table");
    $files_count = $stmt->fetchColumn();
    
    // Statistiques par catégorie
    $stmt = $db->query("SELECT category, COUNT(*) as count FROM forum_topics GROUP BY category ORDER BY count DESC");
    $categories_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques d'activité par mois (12 derniers mois)
    $stmt = $db->query("SELECT 
                            YEAR(created_at) as year,
                            MONTH(created_at) as month,
                            COUNT(*) as count
                        FROM 
                            (SELECT created_at FROM forum_topics
                             UNION ALL
                             SELECT created_at FROM forum_replies) as all_activity
                        WHERE 
                            created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                        GROUP BY 
                            YEAR(created_at), MONTH(created_at)
                        ORDER BY 
                            year ASC, month ASC");
    $activity_by_month = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatage des données pour le graphique d'activité
    $months_labels = [];
    $activity_data = [];
    
    foreach ($activity_by_month as $month_data) {
        $month_name = date('M Y', mktime(0, 0, 0, $month_data['month'], 1, $month_data['year']));
        $months_labels[] = $month_name;
        $activity_data[] = $month_data['count'];
    }
    
    // Utilisateurs les plus actifs (top 10)
    $stmt = $db->query("SELECT 
                            u.id, 
                            u.name, 
                            COUNT(a.id) as post_count
                        FROM 
                            users u
                        LEFT JOIN 
                            (SELECT id, user_id FROM forum_topics
                             UNION ALL
                             SELECT id, user_id FROM forum_replies) as a ON u.id = a.user_id
                        GROUP BY 
                            u.id
                        HAVING 
                            post_count > 0
                        ORDER BY 
                            post_count DESC
                        LIMIT 10");
    $top_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des statistiques: " . $e->getMessage();
}

// Récupération de la section active
$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord administrateur - Ma Clé Campus France</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0163CB',
                        'primary-light': '#A0C4FF',
                        'primary-hover': '#014c9d',
                        'gray-light': '#E0E7FF',
                        'gray-dark': '#545E66',
                    },
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .sidebar {
            width: 280px;
            transition: all 0.3s;
        }
        
        .main-content {
            margin-left: 280px;
            transition: all 0.3s;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }
            
            .sidebar.active {
                width: 280px;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .main-content.sidebar-active {
                margin-left: 280px;
            }
        }
        
        .stat-card {
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <div class="sidebar fixed top-0 left-0 h-full bg-primary text-white shadow-lg z-20">
        <div class="p-5 border-b border-primary-light">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold">Admin Dashboard</h2>
                <button id="close-sidebar" class="md:hidden text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="p-5 border-b border-primary-light">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-primary-light flex items-center justify-center text-primary font-bold">
                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                </div>
                <div>
                    <p class="font-medium"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                    <p class="text-sm text-primary-light">Administrateur</p>
                </div>
            </div>
        </div>
        
        <nav class="p-5">
            <ul class="space-y-2">
                <li>
                    <a href="?section=dashboard" class="flex items-center space-x-3 p-3 rounded-lg <?php echo $section == 'dashboard' ? 'bg-primary-hover' : 'hover:bg-primary-hover'; ?> transition">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Tableau de bord</span>
                    </a>
                </li>
                <li>
                    <a href="?section=users" class="flex items-center space-x-3 p-3 rounded-lg <?php echo $section == 'users' ? 'bg-primary-hover' : 'hover:bg-primary-hover'; ?> transition">
                        <i class="fas fa-users"></i>
                        <span>Gestion des utilisateurs</span>
                    </a>
                </li>
                <li>
                    <a href="?section=topics" class="flex items-center space-x-3 p-3 rounded-lg <?php echo $section == 'topics' ? 'bg-primary-hover' : 'hover:bg-primary-hover'; ?> transition">
                        <i class="fas fa-comments"></i>
                        <span>Modération des discussions</span>
                    </a>
                </li>
                <li>
                    <a href="?section=files" class="flex items-center space-x-3 p-3 rounded-lg <?php echo $section == 'files' ? 'bg-primary-hover' : 'hover:bg-primary-hover'; ?> transition">
                        <i class="fas fa-file-alt"></i>
                        <span>Gestion des fichiers</span>
                    </a>
                </li>
                <li class="pt-6 border-t border-primary-light mt-6">
                    <a href="../index.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-primary-hover transition">
                        <i class="fas fa-home"></i>
                        <span>Retour au site</span>
                    </a>
                </li>
                <li>
                    <a href="../index.php?page=logout" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-primary-hover transition">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Déconnexion</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content min-h-screen bg-gray-100">
        <!-- Top Bar -->
        <div class="bg-white shadow-sm">
            <div class="container mx-auto px-4 py-3 flex justify-between items-center">
                <button id="toggle-sidebar" class="md:hidden text-gray-700">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <div class="flex items-center space-x-4">
                    <a href="../index.php?page=forum" class="text-gray-700 hover:text-primary transition">
                        <i class="fas fa-external-link-alt mr-1"></i> Voir le forum
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Content -->
        <div class="container mx-auto px-4 py-8">
            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                    <p><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($section == 'dashboard'): ?>
                <!-- Dashboard Section -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">Tableau de bord</h1>
                    <p class="text-gray-600">Vue d'ensemble de l'activité du forum</p>
                </div>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-md p-6 stat-card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-700">Utilisateurs actifs</h3>
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-500">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($active_users_count); ?></p>
                        <p class="text-sm text-gray-500 mt-2">Utilisateurs ayant participé au forum</p>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6 stat-card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-700">Discussions</h3>
                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-500">
                                <i class="fas fa-comments"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($topics_count); ?></p>
                        <p class="text-sm text-gray-500 mt-2">Sujets créés sur le forum</p>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6 stat-card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-700">Réponses</h3>
                            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-500">
                                <i class="fas fa-reply"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($replies_count); ?></p>
                        <p class="text-sm text-gray-500 mt-2">Réponses aux discussions</p>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6 stat-card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-700">Fichiers partagés</h3>
                            <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-500">
                                <i class="fas fa-file-alt"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($files_count); ?></p>
                        <p class="text-sm text-gray-500 mt-2">Documents et médias partagés</p>
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Activity Chart -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Activité mensuelle</h3>
                        <div class="h-80">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Categories Chart -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Discussions par catégorie</h3>
                        <div class="h-80">
                            <canvas id="categoriesChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Top Users -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Utilisateurs les plus actifs</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                                    <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Messages</th>
                                    <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($top_users as $user): ?>
                                <tr>
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary-light flex items-center justify-center text-primary font-bold">
                                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo number_format($user['post_count']); ?> messages</div>
                                    </td>
                                    <td class="py-4 px-4 whitespace-nowrap text-sm font-medium">
                                        <a href="?section=users&user_id=<?php echo $user['id']; ?>" class="text-primary hover:text-primary-hover mr-3">
                                            <i class="fas fa-eye"></i> Voir
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-700">Activité récente</h3>
                        <a href="?section=topics" class="text-primary hover:text-primary-hover text-sm">Voir tout</a>
                    </div>
                    
                    <div id="recent-activity" class="space-y-4">
                        <div class="text-center py-8">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-primary"></div>
                            <p class="mt-2 text-gray-500">Chargement de l'activité récente...</p>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($section == 'users'): ?>
                <!-- Users Management Section -->
                <?php
                // Récupération de l'utilisateur spécifique si un ID est fourni
                $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
                
                if ($user_id > 0) {
                    // Affichage des détails d'un utilisateur spécifique
                    try {
                        // Informations de l'utilisateur
                        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$user) {
                            echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">';
                            echo '<p>Utilisateur introuvable.</p>';
                            echo '</div>';
                        } else {
                            // Statistiques de l'utilisateur
                            $stmt = $db->prepare("
                                SELECT 
                                    (SELECT COUNT(*) FROM forum_topics WHERE user_id = ?) as topics_count,
                                    (SELECT COUNT(*) FROM forum_replies WHERE user_id = ?) as replies_count,
                                    (SELECT COUNT(*) FROM forum_topics WHERE user_id = ? AND file_path IS NOT NULL AND file_path != '') + 
                                    (SELECT COUNT(*) FROM forum_replies WHERE user_id = ? AND file_path IS NOT NULL AND file_path != '') as files_count
                            ");
                            $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
                            $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Discussions créées par l'utilisateur
                            $stmt = $db->prepare("
                                SELECT t.*, 
                                       (SELECT COUNT(*) FROM forum_replies WHERE topic_id = t.id) as reply_count
                                FROM forum_topics t
                                WHERE t.user_id = ?
                                ORDER BY t.created_at DESC
                                LIMIT 10
                            ");
                            $stmt->execute([$user_id]);
                            $user_topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Réponses de l'utilisateur
                            $stmt = $db->prepare("
                                SELECT r.*, t.title as topic_title, t.id as topic_id
                                FROM forum_replies r
                                JOIN forum_topics t ON r.topic_id = t.id
                                WHERE r.user_id = ?
                                ORDER BY r.created_at DESC
                                LIMIT 10
                            ");
                            $stmt->execute([$user_id]);
                            $user_replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Fichiers partagés par l'utilisateur
                            $stmt = $db->prepare("
                                SELECT 'topic' as type, t.id, t.title as name, t.file_path, t.created_at
                                FROM forum_topics t
                                WHERE t.user_id = ? AND t.file_path IS NOT NULL AND t.file_path != ''
                                UNION ALL
                                SELECT 'reply' as type, r.id, t.title as name, r.file_path, r.created_at
                                FROM forum_replies r
                                JOIN forum_topics t ON r.topic_id = t.id
                                WHERE r.user_id = ? AND r.file_path IS NOT NULL AND r.file_path != ''
                                ORDER BY created_at DESC
                                LIMIT 10
                            ");
                            $stmt->execute([$user_id, $user_id]);
                            $user_files = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Affichage des informations de l'utilisateur
                            ?>
                            <div class="mb-6 flex justify-between items-center">
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-800 mb-2">Profil de <?php echo htmlspecialchars($user['name']); ?></h1>
                                    <p class="text-gray-600">Gestion et statistiques de l'utilisateur</p>
                                </div>
                                <a href="?section=users" class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-4 rounded-lg transition">
                                    <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
                                </a>
                            </div>
                            
                            <!-- User Info Card -->
                            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                                <div class="flex flex-col md:flex-row md:items-center">
                                    <div class="flex-shrink-0 mb-4 md:mb-0 md:mr-6">
                                        <div class="w-24 h-24 rounded-full bg-primary-light flex items-center justify-center text-primary text-4xl font-bold">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                    </div>
                                    <div class="flex-grow">
                                        <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($user['name']); ?></h2>
                                        <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                                        <p class="text-sm text-gray-500 mt-1">Membre depuis <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                                        
                                        <div class="mt-4 flex flex-wrap gap-4">
                                            <div class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                                <?php echo number_format($user_stats['topics_count']); ?> discussions
                                            </div>
                                            <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                                <?php echo number_format($user_stats['replies_count']); ?> réponses
                                            </div>
                                            <div class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                                                <?php echo number_format($user_stats['files_count']); ?> fichiers partagés
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-6 md:mt-0">
                                        <button class="delete-user-btn bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg transition" 
                                                data-id="<?php echo $user['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($user['name']); ?>">
                                            <i class="fas fa-user-times mr-2"></i>Supprimer l'utilisateur
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- User Content Tabs -->
                            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                                <div class="border-b">
                                    <nav class="flex">
                                        <button class="user-tab-btn active px-6 py-4 text-primary border-b-2 border-primary font-medium" data-tab="discussions">
                                            Discussions
                                        </button>
                                        <button class="user-tab-btn px-6 py-4 text-gray-500 hover:text-gray-700 font-medium" data-tab="replies">
                                            Réponses
                                        </button>
                                        <button class="user-tab-btn px-6 py-4 text-gray-500 hover:text-gray-700 font-medium" data-tab="files">
                                            Fichiers partagés
                                        </button>
                                    </nav>
                                </div>
                                
                                <!-- Discussions Tab -->
                                <div id="discussions-tab" class="user-tab-content p-6">
                                    <?php if (count($user_topics) > 0): ?>
                                        <div class="overflow-x-auto">
                                            <table class="min-  > 0): ?>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full bg-white">
                                                <thead>
                                                    <tr>
                                                        <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titre</th>
                                                        <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>
                                                        <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                                        <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Réponses</th>
                                                        <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200">
                                                    <?php foreach ($user_topics as $topic): ?>
                                                    <tr>
                                                        <td class="py-4 px-4">
                                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($topic['title']); ?></div>
                                                        </td>
                                                        <td class="py-4 px-4">
                                                            <?php 
                                                            $category_name = '';
                                                            $category_class = '';
                                                            
                                                            switch ($topic['category']) {
                                                                case 'procedure':
                                                                    $category_name = 'Procédure Campus France';
                                                                    $category_class = 'bg-blue-100 text-blue-800';
                                                                    break;
                                                                case 'visa':
                                                                    $category_name = 'Visa étudiant';
                                                                    $category_class = 'bg-green-100 text-green-800';
                                                                    break;
                                                                case 'logement':
                                                                    $category_name = 'Logement';
                                                                    $category_class = 'bg-yellow-100 text-yellow-800';
                                                                    break;
                                                                case 'etudes':
                                                                    $category_name = 'Études et formations';
                                                                    $category_class = 'bg-purple-100 text-purple-800';
                                                                    break;
                                                                case 'vie':
                                                                    $category_name = 'Vie en France';
                                                                    $category_class = 'bg-pink-100 text-pink-800';
                                                                    break;
                                                                default:
                                                                    $category_name = 'Autres';
                                                                    $category_class = 'bg-gray-100 text-gray-800';
                                                                    break;
                                                            }
                                                            ?>
                                                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $category_class; ?>"><?php echo $category_name; ?></span>
                                                        </td>
                                                        <td class="py-4 px-4">
                                                            <div class="text-sm text-gray-500"><?php echo date('d/m/Y H:i', strtotime($topic['created_at'])); ?></div>
                                                        </td>
                                                        <td class="py-4 px-4">
                                                            <div class="text-sm text-gray-900"><?php echo number_format($topic['reply_count']); ?></div>
                                                        </td>
                                                        <td class="py-4 px-4 whitespace-nowrap text-sm font-medium">
                                                            <a href="../index.php?page=forum&action=view&topic_id=<?php echo $topic['id']; ?>" target="_blank" class="text-primary hover:text-primary-hover mr-3">
                                                                <i class="fas fa-eye"></i> Voir
                                                            </a>
                                                            <button class="delete-topic-btn text-red-600 hover:text-red-800" 
                                                                    data-id="<?php echo $topic['id']; ?>" 
                                                                    data-title="<?php echo htmlspecialchars($topic['title']); ?>">
                                                                <i class="fas fa-trash-alt"></i> Supprimer
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php if (count($user_topics) >= 10): ?>
                                            <div class="mt-4 text-center">
                                                <a href="?section=topics&user_id=<?php echo $user_id; ?>" class="text-primary hover:text-primary-hover">
                                                    Voir toutes les discussions de cet utilisateur
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="text-center py-8">
                                            <i class="fas fa-comments text-gray-300 text-5xl mb-4"></i>
                                            <p class="text-gray-500">Cet utilisateur n'a pas encore créé de discussion.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Replies Tab -->
                                <div id="replies-tab" class="user-tab-content p-6 hidden">
                                    <?php if (count($user_replies) > 0): ?>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full bg-white">
                                                <thead>
                                                    <tr>
                                                        <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discussion</th>
                                                        <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Réponse</th>
                                                        <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                                        <th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200">
                                                    <?php foreach ($user_replies as $reply): ?>
                                                    <tr>
                                                        <td class="py-4 px-4">
                                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($reply['topic_title']); ?></div>
                                                        </td>
                                                        <td class="py-4 px-4">
                                                            <div class="text-sm text-gray-500 truncate max-w-xs"><?php echo htmlspecialchars(substr($reply['content'], 0, 100)) . (strlen($reply['content']) > 100 ? '...' : ''); ?></div>
                                                        </td>
                                                        <td class="py-4 px-4">
                                                            <div class="text-sm text-gray-500"><?php echo date('d/m/Y H:i', strtotime($reply['created_at'])); ?></div>
                                                        </td>
                                                        <td class="py-4 px-4 whitespace-nowrap text-sm font-medium">
                                                            <a href="../index.php?page=forum&action=view&topic_id=<?php echo $reply['topic_id']; ?>" target="_blank" class="text-primary hover:text-primary-hover mr-3">
                                                                <i class="fas fa-eye"></i> Voir
                                                            </a>
                                                            <button class="delete-reply-btn text-red-600 hover:text-red-800" 
                                                                    data-id="<?php echo $reply['id']; ?>">
                                                                <i class="fas fa-trash-alt"></i> Supprimer
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php if (count($user_replies) >= 10): ?>
                                            <div class="mt-4 text-center">
                                                <a href="?section=topics&user_id=<?php echo $user_id; ?>&type=replies" class="text-primary hover:text-primary-hover">
                                                    Voir toutes les réponses de cet utilisateur
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="text-center py-8">
                                            <i class="fas fa-reply text-gray-300 text-5xl mb-4"></i>
                                            <p class="text-gray-500">Cet utilisateur n'a pas encore répondu à des discussions.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Files Tab -->
                                <div id="files-tab" class="user-tab-content p-6 hidden">
                                    <?php if (count($user_files) > 0): ?>
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <?php foreach ($user_files as $file): 
                                                $file_extension = strtolower(pathinfo($file['file_path'], PATHINFO_EXTENSION));
                                                $file_icon = '';
                                                $file_color = '';
                                                
                                                switch ($file_extension) {
                                                    case 'pdf':
                                                        $file_icon = 'fa-file-pdf';
                                                        $file_color = 'text-red-500';
                                                        break;
                                                    case 'jpg':
                                                    case 'jpeg':
                                                    case 'png':
                                                    case 'gif':
                                                        $file_icon = 'fa-file-image';
                                                        $file_color = 'text-green-500';
                                                        break;
                                                    case 'mp4':
                                                    case 'avi':
                                                    case 'mov':
                                                        $file_icon = 'fa-file-video';
                                                        $file_color = 'text-blue-500';
                                                        break;
                                                    default:
                                                        $file_icon = 'fa-file';
                                                        $file_color = 'text-gray-500';
                                                        break;
                                                }
                                            ?>
                                            <div class="bg-white border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition">
                                                <div class="p-4">
                                                    <div class="flex items-center mb-3">
                                                        <i class="fas <?php echo $file_icon; ?> <?php echo $file_color; ?> text-2xl mr-3"></i>
                                                        <div class="flex-grow">
                                                            <div class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars(basename($file['file_path'])); ?></div>
                                                            <div class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime($file['created_at'])); ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="text-xs text-gray-500 mb-3">
                                                        Dans <?php echo $file['type'] == 'topic' ? 'la discussion' : 'une réponse à'; ?>: <?php echo htmlspecialchars($file['name']); ?>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="text-primary hover:text-primary-hover text-sm">
                                                            <i class="fas fa-external-link-alt mr-1"></i> Voir
                                                        </a>
                                                        <button class="delete-file-btn text-red-600 hover:text-red-800 text-sm" 
                                                                data-path="<?php echo htmlspecialchars($file['file_path']); ?>"
                                                                data-type="<?php echo $file['type']; ?>"
                                                                data-id="<?php echo $file['id']; ?>">
                                                            <i class="fas fa-trash-alt mr-1"></i> Supprimer
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if (count($user_files) >= 10): ?>
                                            <div class="mt-4 text-center">
                                                <a href="?section=files&user_id=<?php echo $user_id; ?>" class="text-primary hover:text-primary-hover">
                                                    Voir tous les fichiers de cet utilisateur
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="text-center py-8">
                                            <i class="fas fa-file-alt text-gray-300 text-5xl mb-4"></i>
                                            <p class="text-gray-500">Cet utilisateur n'a pas encore partagé de fichiers.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php
                        }
                    } catch (PDOException $e) {
                        echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">';
                        echo '<p>Erreur lors de la récupération des informations de l\'utilisateur: ' . $e->getMessage() . '</p>';
                        echo '</div>';
                    }
                } else {
                    // Liste de tous les utilisateurs
                    ?>
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-800 mb-2">Gestion des utilisateurs</h1>
                        <p class="text-gray-600">Liste des utilisateurs actifs sur le forum</p>
                    </div>
                    
                    <!-- Search and Filter -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                        <form id="users-filter-form" class="flex flex-col md:flex-row md:items-end space-y-4 md:space-y-0 md:space-x-4">
                            <div class="flex-grow">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Rechercher un utilisateur</label>
                                <input type="text" id="search" name="search" placeholder="Nom d'utilisateur ou email" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            <div>
                                <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Trier par</label>
                                <select id="sort" name="sort" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="activity_desc">Activité (décroissant)</option>
                                    <option value="activity_asc">Activité (croissant)</option>
                                    <option value="name_asc">Nom d'utilisateur (A-Z)</option>
                                    <option value="name_desc">Nom d'utilisateur (Z-A)</option>
                                    <option value="date_desc">Date d'inscription (récent)</option>
                                    <option value="date_asc">Date d'inscription (ancien)</option>
                                </select>
                            </div>
                            <div>
                                <button type="submit" class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-lg transition">
                                    <i class="fas fa-search mr-2"></i>Filtrer
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Users List -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6 border-b">
                            <h2 class="text-lg font-semibold text-gray-700">Utilisateurs actifs</h2>
                        </div>
                        
                        <div id="users-list" class="p-6">
                            <div class="text-center py-8">
                                <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-primary"></div>
                                <p class="mt-2 text-gray-500">Chargement des utilisateurs...</p>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
                
            <?php elseif ($section == 'topics'): ?>
                <!-- Topics Moderation Section -->
                <?php
                // Récupération des paramètres de filtrage
                $category = isset($_GET['category']) ? $_GET['category'] : '';
                $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
                $search = isset($_GET['search']) ? $_GET['search'] : '';
                $sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
                $type = isset($_GET['type']) ? $_GET['type'] : 'topics';
                $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
                $items_per_page = 20;
                ?>
                
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">Modération du forum</h1>
                    <p class="text-gray-600">Gérer les discussions et réponses du forum</p>
                </div>
                
                <!-- Search and Filter -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <form id="topics-filter-form" action="" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <input type="hidden" name="section" value="topics">
                        
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Titre ou contenu" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                            <select id="category" name="category" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">Toutes les catégories</option>
                                <option value="procedure" <?php echo $category == 'procedure' ? 'selected' : ''; ?>>Procédure Campus France</option>
                                <option value="visa" <?php echo $category == 'visa' ? 'selected' : ''; ?>>Visa étudiant</option>
                                <option value="logement" <?php echo $category == 'logement' ? 'selected' : ''; ?>>Logement</option>
                                <option value="etudes" <?php echo $category == 'etudes' ? 'selected' : ''; ?>>Études et formations</option>
                                <option value="vie" <?php echo $category == 'vie' ? 'selected' : ''; ?>>Vie en France</option>
                                <option value="autre" <?php echo $category == 'autre' ? 'selected' : ''; ?>>Autres</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select id="type" name="type" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="topics" <?php echo $type == 'topics' ? 'selected' : ''; ?>>Discussions</option>
                                <option value="replies" <?php echo $type == 'replies' ? 'selected' : ''; ?>>Réponses</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Trier par</label>
                            <select id="sort" name="sort" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="date_desc" <?php echo $sort == 'date_desc' ? 'selected' : ''; ?>>Date (récent)</option>
                                <option value="date_asc" <?php echo $sort == 'date_asc' ? 'selected' : ''; ?>>Date (ancien)</option>
                                <option value="replies_desc" <?php echo $sort == 'replies_desc' ? 'selected' : ''; ?>>Réponses (décroissant)</option>
                                <option value="replies_asc" <?php echo $sort == 'replies_asc' ? 'selected' : ''; ?>>Réponses (croissant)</option>
                                <option value="views_desc" <?php echo $sort == 'views_desc' ? 'selected' : ''; ?>>Vues (décroissant)</option>
                                <option value="views_asc" <?php echo $sort == 'views_asc' ? 'selected' : ''; ?>>Vues (croissant)</option>
                            </select>
                        </div>
                        
                        <div class="md:col-span-2 lg:col-span-4 flex justify-end">
                            <button type="submit" class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-lg transition">
                                <i class="fas fa-filter mr-2"></i>Appliquer les filtres
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Topics/Replies List -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 border-b flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-700">
                            <?php echo $type == 'topics' ? 'Discussions' : 'Réponses'; ?>
                            <?php if (!empty($search)): ?>
                                contenant "<?php echo htmlspecialchars($search); ?>"
                            <?php endif; ?>
                            <?php if (!empty($category)): ?>
                                dans la catégorie "<?php 
                                    switch ($category) {
                                        case 'procedure': echo 'Procédure Campus France'; break;
                                        case 'visa': echo 'Visa étudiant'; break;
                                        case 'logement': echo 'Logement'; break;
                                        case 'etudes': echo 'Études et formations'; break;
                                        case 'vie': echo 'Vie en France'; break;
                                        case 'autre': echo 'Autres'; break;
                                    }
                                ?>"
                            <?php endif; ?>
                            <?php if ($user_id > 0): 
                                try {
                                    $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
                                    $stmt->execute([$user_id]);
                                    $name = $stmt->fetchColumn();
                                    if ($name) {
                                        echo ' de ' . htmlspecialchars($name);
                                    }
                                } catch (PDOException $e) {
                                    // Ignorer l'erreur
                                }
                            endif; ?>
                        </h2>
                    </div>
                    
                    <?php
                    try {
                        // Construction de la requête SQL de base
                        if ($type == 'topics') {
                            $sql = "SELECT t.*, u.name, 
                                    (SELECT COUNT(*) FROM forum_replies WHERE topic_id = t.id) as reply_count 
                                    FROM forum_topics t 
                                    JOIN users u ON t.user_id = u.id 
                                    WHERE 1=1";
                            
                            // Ajout des filtres
                            $params = [];
                            
                            if (!empty($category)) {
                                $sql .= " AND t.category = ?";
                                $params[] = $category;
                            }
                            
                            if ($user_id > 0) {
                                $sql .= " AND t.user_id = ?";
                                $params[] = $user_id;
                            }
                            
                            if (!empty($search)) {
                                $sql .= " AND (t.title LIKE ? OR t.content LIKE ?)";
                                $params[] = "%$search%";
                                $params[] = "%$search%";
                            }
                            
                            // Ajout du tri
                            switch ($sort) {
                                case 'date_asc':
                                    $sql .= " ORDER BY t.created_at ASC";
                                    break;
                                case 'replies_desc':
                                    $sql .= " ORDER BY reply_count DESC, t.created_at DESC";
                                    break;
                                case 'replies_asc':
                                    $sql .= " ORDER BY reply_count ASC, t.created_at DESC";
                                    break;
                                case 'views_desc':
                                    $sql .= " ORDER BY t.views DESC, t.created_at DESC";
                                    break;
                                case 'views_asc':
                                    $sql .= " ORDER BY t.views ASC, t.created_at DESC";
                                    break;
                                default: // date_desc
                                    $sql .= " ORDER BY t.created_at DESC";
                                    break;
                            }
                            
                            // Requête pour compter le nombre total de discussions
                            $count_sql = str_replace("SELECT t.*, u.name, (SELECT COUNT(*) FROM forum_replies WHERE topic_id = t.id) as reply_count", "SELECT COUNT(*)", $sql);
                            $count_stmt = $db->prepare($count_sql);
                            $count_stmt->execute($params);
                            $total_items = $count_stmt->fetchColumn();
                            
                            // Calcul de la pagination
                            $total_pages = ceil($total_items / $items_per_page);
                            $offset = ($page - 1) * $items_per_page;
                            
                            // Ajout de la limite pour la pagination
                            $sql .= " LIMIT $offset, $items_per_page";
                            
                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Affichage des discussions
                            if (count($items) > 0) {
                                echo '<div class="overflow-x-auto">';
                                echo '<table class="min-w-full bg-white">';
                                echo '<thead>';
                                echo '<tr>';
                                echo '<th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titre</th>';
                                echo '<th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Auteur</th>';
                                echo '<th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>';
                                echo '<th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>';
                                echo '<th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Réponses</th>';
                                echo '<th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>';
                                echo '</tr>';
                                echo '</thead>';
                                echo '<tbody class="divide-y divide-gray-200">';
                                
                                foreach ($items as $item) {
                                    // Formatage de la date
                                    $created_date = new DateTime($item['created_at']);
                                    $formatted_date = $created_date->format('d/m/Y H:i');
                                    
                                    // Détermination de la classe CSS pour la catégorie
                                    $category_class = '';
                                    $category_name = '';
                                    
                                    switch ($item['category']) {
                                        case 'procedure':
                                            $category_class = 'bg-blue-100 text-blue-800';
                                            $category_name = 'Procédure Campus France';
                                            break;
                                        case 'visa':
                                            $category_class = 'bg-green-100 text-green-800';
                                            $category_name = 'Visa étudiant';
                                            break;
                                        case 'logement':
                                            $category_class = 'bg-yellow-100 text-yellow-800';
                                            $category_name = 'Logement';
                                            break;
                                        case 'etudes':
                                            $category_class = 'bg-purple-100 text-purple-800';
                                            $category_name = 'Études et formations';
                                            break;
                                        case 'vie':
                                            $category_class = 'bg-pink-100 text-pink-800';
                                            $category_name = 'Vie en France';
                                            break;
                                        default:
                                            $category_class = 'bg-gray-100 text-gray-800';
                                            $category_name = 'Autres';
                                            break;
                                    }
                                    
                                    echo '<tr>';
                                    echo '<td class="py-4 px-4">';
                                    echo '<div class="text-sm font-medium text-gray-900">' . htmlspecialchars($item['title']) . '</div>';
                                    echo '</td>';
                                    echo '<td class="py-4 px-4">';
                                    echo '<div class="text-sm text-gray-900">' . htmlspecialchars($item['name']) . '</div>';
                                    echo '</td>';
                                    echo '<td class="py-4 px-4">';
                                    echo '<span class="px-2 py-1 rounded-full text-xs font-medium ' . $category_class . '">' . $category_name . '</span>';
                                    echo '</td>';
                                    echo '<td class="py-4 px-4">';
                                    echo '<div class="text-sm text-gray-500">' . $formatted_date . '</div>';
                                    echo '</td>';
                                    echo '<td class="py-4 px-4">';
                                    echo '<div class="text-sm text-gray-900">' . number_format($item['reply_count']) . '</div>';
                                    echo '</td>';
                                    echo '<td class="py-4 px-4 whitespace-nowrap text-sm font-medium">';
                                    echo '<a href="../index.php?page=forum&action=view&topic_id=' . $item['id'] . '" target="_blank" class="text-primary hover:text-primary-hover mr-3">';
                                    echo '<i class="fas fa-eye"></i> Voir';
                                    echo '</a>';
                                    echo '<button class="delete-topic-btn text-red-600 hover:text-red-800" data-id="' . $item['id'] . '" data-title="' . htmlspecialchars($item['title']) . '">';
                                    echo '<i class="fas fa-trash-alt"></i> Supprimer';
                                    echo '</button>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                                
                                echo '</tbody>';
                                echo '</table>';
                                echo '</div>';
                                
                                // Pagination
                                if ($total_pages > 1) {
                                    echo '<div class="flex justify-center p-4 border-t">';
                                    echo '<div class="flex space-x-1">';
                                    
                                    if ($page > 1) {
                                        echo '<a href="?section=topics&page_num=' . ($page - 1) . '&category=' . $category . '&user_id=' . $user_id . '&search=' . urlencode($search) . '&sort=' . $sort . '&type=' . $type . '" class="px-3 py-1 rounded-md border hover:bg-gray-100 transition">';
                                        echo '<i class="fas fa-chevron-left"></i>';
                                        echo '</a>';
                                    }
                                    
                                    // Affichage des numéros de page
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1) {
                                        echo '<a href="?section=topics&page_num=1&category=' . $category . '&user_id=' . $user_id . '&search=' . urlencode($search) . '&sort=' . $sort . '&type=' . $type . '" class="px-3 py-1 rounded-md border hover:bg-gray-100 transition">1</a>';
                                        if ($start_page > 2) {
                                            echo '<span class="px-3 py-1">...</span>';
                                        }
                                    }
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        $active_class = ($i == $page) ? 'bg-primary text-white' : 'hover:bg-gray-100';
                                        echo '<a href="?section=topics&page_num=' . $i . '&category=' . $category . '&user_id=' . $user_id . '&search=' . urlencode($search) . '&sort=' . $sort . '&type=' . $type . '" class="px-3 py-1 rounded-md border ' . $active_class . ' transition">' . $i . '</a>';
                                    }
                                    
                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<span class="px-3 py-1">...</span>';
                                        }
                                        echo '<a href="?section=topics&page_num=' . $total_pages . '&category=' . $category . '&user_id=' . $user_id . '&search=' . urlencode($search) . '&sort=' . $sort . '&type=' . $type . '" class="px-3 py-1 rounded-md border hover:bg-gray-100 transition">' . $total_pages . '</a>';
                                    }
                                    
                                    if ($page < $total_pages) {
                                        echo '<a href="?section=topics&page_num=' . ($page + 1) . '&category=' . $category . '&user_id=' . $user_id . '&search=' . urlencode($search) . '&sort=' . $sort . '&type=' . $type . '" class="px-3 py-1 rounded-md border hover:bg-gray-100 transition">';
                                        echo '<i class="fas fa-chevron-right"></i>';
                                        echo '</a>';
                                    }
                                    
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="text-center py-8">';
                                echo '<i class="fas fa-search text-gray-300 text-5xl mb-4"></i>';
                                echo '<p class="text-gray-500">Aucune discussion trouvée.</p>';
                                echo '</div>';
                            }
                        } else {
                            // Affichage des réponses
                            $sql = "SELECT r.*, t.title as topic_title, t.id as topic_id, u.name 
                                    FROM forum_replies r 
                                    JOIN forum_topics t ON r.topic_id = t.id 
                                    JOIN users u ON r.user_id = u.id 
                                    WHERE 1=1";
                            
                            // Ajout des filtres
                            $params = [];
                            
                            if (!empty($category)) {
                                $sql .= " AND t.category = ?";
                                $params[] = $category;
                            }
                            
                            if ($user_id > 0) {
                                $sql .= " AND r.user_id = ?";
                                $params[] = $user_id;
                            }
                            
                            if (!empty($search)) {
                                $sql .= " AND (t.title LIKE ? OR r.content LIKE ?)";
                                $params[] = "%$search%";
                                $params[] = "%$search%";
                            }
                            
                            // Ajout du tri
                            switch ($sort) {
                                case 'date_asc':
                                    $sql .= " ORDER BY r.created_at ASC";
                                    break;
                                default: // date_desc
                                    $sql .= " ORDER BY r.created_at DESC";
                                    break;
                            }
                            
                            // Requête pour compter le nombre total de réponses
                            $count_sql = str_replace("SELECT r.*, t.title as topic_title, t.id as topic_id, u.name", "SELECT COUNT(*)", $sql);
                            $count_stmt = $db->prepare($count_sql);
                            $count_stmt->execute($params);
                            $total_items = $count_stmt->fetchColumn();
                            
                            // Calcul de la pagination
                            $total_pages = ceil($total_items / $items_per_page);
                            $offset = ($page - 1) * $items_per_page;
                            
                            // Ajout de la limite pour la pagination
                            $sql .= " LIMIT $offset, $items_per_page";
                            
                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Affichage des réponses
                            if (count($items) > 0) {
                                echo '<div class="overflow-x-auto">';
                                echo '<table class="min-w-full bg-white">';
                                echo '<thead>';
                                echo '<tr>';
                                echo '<th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discussion</th>';
                                echo '<th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Réponse</th>';
                                echo '<th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Auteur</th>';
                                echo '<th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>';
                                echo '<th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>';
                                echo '</tr>';
                                echo '</thead>';
                                echo '<tbody class="divide-y divide-gray-200">';
                                
                                foreach ($items as $item) {
                                    // Formatage de la date
                                    $created_date = new DateTime($item['created_at']);
                                    $formatted_date = $created_date->format('d/m/Y H:i');
                                    
                                    echo '<tr>';
                                    echo '<td class="py-4 px-4">';
                                    echo '<div class="text-sm font-medium text-gray-900">' . htmlspecialchars($item['topic_title']) . '</div>';
                                    echo '</td>';
                                    echo '<td class="py-4 px-4">';
                                    echo '<div class="text-sm text-gray-500 truncate max-w-xs">' . htmlspecialchars(substr($item['content'], 0, 100)) . (strlen($item['content']) > 100 ? '...' : '') . '</div>';
                                    echo '</td>';
                                    echo '<td class="py-4 px-4">';
                                    echo '<div class="text-sm text-gray-900">' . htmlspecialchars($item['name']) . '</div>';
                                    echo '</td>';
                                    echo '<td class="py-4 px-4">';
                                    echo '<div class="text-sm text-gray-500">' . $formatted_date . '</div>';
                                    echo '</td>';
                                    echo '<td class="py-4 px-4 whitespace-nowrap text-sm font-medium">';
                                    echo '<a href="../index.php?page=forum&action=view&topic_id=' . $item['topic_id'] . '" target="_blank" class="text-primary hover:text-primary-hover mr-3">';
                                    echo '<i class="fas fa-eye"></i> Voir';
                                    echo '</a>';
                                    echo '<button class="delete-reply-btn text-red-600 hover:text-red-800" data-id="' . $item['id'] . '">';
                                    echo '<i class="fas fa-trash-alt"></i> Supprimer';
                                    echo '</button>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                                
                                echo '</tbody>';
                                echo '</table>';
                                echo '</div>';
                                
                                // Pagination
                                if ($total_pages > 1) {
                                    echo '<div class="flex justify-center p-4 border-t">';
                                    echo '<div class="flex space-x-1">';
                                    
                                    if ($page > 1) {
                                        echo '<a href="?section=topics&page_num=' . ($page - 1) . '&category=' . $category . '&user_id=' . $user_id . '&search=' . urlencode($search) . '&sort=' . $sort . '&type=' . $type . '" class="px-3 py-1 rounded-md border hover:bg-gray-100 transition">';
                                        echo '<i class="fas fa-chevron-left"></i>';
                                        echo '</a>';
                                    }
                                    
                                    // Affichage des numéros de page
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1) {
                                        echo '<a href="?section=topics&page_num=1&category=' . $category . '&user_id=' . $user_id . '&search=' . urlencode($search) . '&sort=' . $sort . '&type=' . $type . '" class="px-3 py-1 rounded-md border hover:bg-gray-100 transition">1</a>';
                                        if ($start_page > 2) {
                                            echo '<span class="px-3 py-1">...</span>';
                                        }
                                    }
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        $active_class = ($i == $page) ? 'bg-primary text-white' : 'hover:bg-gray-100';
                                        echo '<a href="?section=topics&page_num=' . $i . '&category=' . $category . '&user_id=' . $user_id . '&search=' . urlencode($search) . '&sort=' . $sort . '&type=' . $type . '" class="px-3 py-1 rounded-md border ' . $active_class . ' transition">' . $i . '</a>';
                                    }
                                    
                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<span class="px-3 py-1">...</span>';
                                        }
                                        echo '<a href="?section=topics&page_num=' . $total_pages . '&category=' . $category . '&user_id=' . $user_id . '&search=' . urlencode($search) . '&sort=' . $sort . '&type=' . $type . '" class="px-3 py-1 rounded-md border hover:bg-gray-100 transition">' . $total_pages . '</a>';
                                    }
                                    
                                    if ($page < $total_pages) {
                                        echo '<a href="?section=topics&page_num=' . ($page + 1) . '&category=' . $category . '&user_id=' . $user_id . '&search=' . urlencode($search) . '&sort=' . $sort . '&type=' . $type . '" class="px-3 py-1 rounded-md border hover:bg-gray-100 transition">';
                                        echo '<i class="fas fa-chevron-right"></i>';
                                        echo '</a>';
                                    }
                                    
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="text-center py-8">';
                                echo '<i class="fas fa-search text-gray-300 text-5xl mb-4"></i>';
                                echo '<p class="text-gray-500">Aucune réponse trouvée.</p>';
                                echo '</div>';
                            }
                        }
                    } catch (PDOException $e) {
                        echo '<div class="p-6 text-center text-red-500">';
                        echo 'Erreur lors de la récupération des données. Veuillez réessayer plus tard.';
                        echo '</div>';
                        // Log de l'erreur pour l'administrateur
                        error_log('Admin dashboard error: ' . $e->getMessage());
                    }
                    ?>
                </div>
                
            <?php elseif ($section == 'files'): ?>
                <!-- Files Management Section -->
                <?php
                // Récupération des paramètres de filtrage
                $file_type = isset($_GET['file_type']) ? $_GET['file_type'] : '';
                $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
                $search = isset($_GET['search']) ? $_GET['search'] : '';
                $sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
                $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
                $items_per_page = 24;
                ?>
                
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">Gestion des fichiers</h1>
                    <p class="text-gray-600">Gérer les fichiers partagés sur le forum</p>
                </div>
                
                <!-- Search and Filter -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <form id="files-filter-form" action="" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <input type="hidden" name="section" value="files">
                        
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nom de fichier" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label for="file_type" class="block text-sm font-medium text-gray-700 mb-1">Type de fichier</label>
                            <select id="file_type" name="file_type" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">Tous les types</option>
                                <option value="image" <?php echo $file_type == 'image' ? 'selected' : ''; ?>>Images</option>
                                <option value="pdf" <?php echo $file_type == 'pdf' ? 'selected' : ''; ?>>PDF</option>
                                <option value="video" <?php echo $file_type == 'video' ? 'selected' : ''; ?>>Vidéos</option>
                                <option value="other" <?php echo $file_type == 'other' ? 'selected' : ''; ?>>Autres</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Trier par</label>
                            <select id="sort" name="sort" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="date_desc" <?php echo $sort == 'date_desc' ? 'selected' : ''; ?>>Date (récent)</option>
                                <option value="date_asc" <?php echo $sort == 'date_asc' ? 'selected' : ''; ?>>Date (ancien)</option>
                                <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Nom (A-Z)</option>
                                <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Nom (Z-A)</option>
                            </select>
                        </div>
                        
                        <div class="md:col-span-2 lg:col-span-4 flex justify-end">
                            <button type="submit" class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-lg transition">
                                <i class="fas fa-filter mr-2"></i>Appliquer les filtres
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Files Grid -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 border-b flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-700">
                            Fichiers partagés
                            <?php if (!empty($search)): ?>
                                contenant "<?php echo htmlspecialchars($search); ?>"
                            <?php endif; ?>
                            <?php if (!empty($file_type)): ?>
                                de type "<?php 
                                    switch ($file_type) {
                                        case 'image': echo 'Images'; break;
                                        case 'pdf': echo 'PDF'; break;
                                        case 'video': echo 'Vidéos'; break;
                                        case 'other': echo 'Autres'; break;
                                    }
                                ?>"
                            <?php endif; ?>
                            <?php if ($user_id > 0): 
                                try {
                                    $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
                                    $stmt->execute([$user_id]);
                                    $name = $stmt->fetchColumn();
                                    if ($name) {
                                        echo ' de ' . htmlspecialchars($name);
                                    }
                                } catch (PDOException $e) {
                                    // Ignorer l'erreur
                                }
                            endif; ?>
                        </h2>
                    </div>
                    
                    <?php
                    try {
                        // Construction de la requête SQL
                        $sql = "SELECT 
                                    f.*, 
                                    u.name,
                                    CASE 
                                        WHEN f.type = 'topic' THEN t.title 
                                        ELSE (SELECT title FROM forum_topics WHERE id = r.topic_id) 
                                    END as content_title
                                FROM 
                                    (
                                        SELECT 
                                            'topic' as type, 
                                            t.id, 
                                            t.user_id, 
                                            t.file_path, 
                                            t.created_at 
                                        FROM 
                                            forum_topics t 
                                        WHERE 
                                            t.file_path IS NOT NULL AND t.file_path != ''
                                        
                                        UNION ALL
                                        
                                        SELECT 
                                            'reply' as type, 
                                            r.id, 
                                            r.user_id, 
                                            r.file_path, 
                                            r.created_at 
                                        FROM 
                                            forum_replies r 
                                        WHERE 
                                            r.file_path IS NOT NULL AND r.file_path != ''
                                    ) as f
                                LEFT JOIN 
                                    users u ON f.user_id = u.id
                                LEFT JOIN 
                                    forum_topics t ON (f.type = 'topic' AND f.id = t.id)
                                LEFT JOIN 
                                    forum_replies r ON (f.type = 'reply' AND f.id = r.id)
                                WHERE 1=1";
                        
                        // Ajout des filtres
                        $params = [];
                        
                        if ($user_id > 0) {
                            $sql .= " AND f.user_id = ?";
                            $params[] = $user_id;
                        }
                        
                        if (!empty($search)) {
                            $sql .= " AND f.file_path LIKE ?";
                            $params[] = "%$search%";
                        }
                        
                        if (!empty($file_type)) {
                            switch ($file_type) {
                                case 'image':
                                    $sql .= " AND (f.file_path LIKE '%.jpg' OR f.file_path LIKE '%.jpeg' OR f.file_path LIKE '%.png' OR f.file_path LIKE '%.gif')";
                                    break;
                                case 'pdf':
                                    $sql .= " AND f.file_path LIKE '%.pdf'";
                                    break;
                                case 'video':
                                    $sql .= " AND (f.file_path LIKE '%.mp4' OR f.file_path LIKE '%.avi' OR f.file_path LIKE '%.mov')";
                                    break;
                                case 'other':
                                    $sql .= " AND f.file_path NOT LIKE '%.jpg' AND f.file_path NOT LIKE '%.jpeg' AND f.file_path NOT LIKE '%.png' AND f.file_path NOT LIKE '%.gif' AND f.file_path NOT LIKE '%.pdf' AND f.file_path NOT LIKE '%.mp4' AND f.file_path NOT LIKE '%.avi' AND f.file_path NOT LIKE '%.mov'";
                                    break;
                            }
                        }
                        
                        // Ajout du tri
                        switch ($sort) {
                            case 'date_asc':
                                $sql .= " ORDER BY f.created_at ASC";
                                break;
                            case 'name_asc':
                                $sql .= " ORDER BY f.file_path ASC";
                                break;
                            case 'name_desc':
                                $sql .= " ORDER BY f.file_path DESC";
                                break;
                            default: // date_desc
                                $sql .= " ORDER BY f.created_at DESC";
                                break;
                        }
                        
                        // Requête pour compter le nombre total de fichiers
                        $count_sql = preg_replace('/SELECT\s+f\.\*,\s+u\.name,\s+CASE.*?END\s+as\s+content_title/i', 'SELECT COUNT(*)', $sql);
                        $count_stmt = $db->prepare($count_sql);
                        $count_stmt->execute($params);
                        $total_items = $count_stmt->fetchColumn();
                        
                        // Calcul de la pagination
                        $total_pages = ceil($total_items / $items_per_page);
                        $offset = ($page - 1) * $items_per_page;
                        
                        // Ajout de la limite pour la pagination
                        $sql .= " LIMIT $offset, $items_per_page";
                        
                        $stmt = $db->prepare($sql);
                        $stmt->execute($params);
                        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Affichage des fichiers
                        if (count($files) > 0) {
                            echo '<div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">';
                            
                            foreach ($files as $file) {
                                $file_extension = strtolower(pathinfo($file['file_path'], PATHINFO_EXTENSION));
                                $file_icon = '';
                                $file_color = '';
                                $file_type_name = '';
                                $file_preview = '';
                                
                                switch ($file_extension) {
                                    case 'pdf':
                                        $file_icon = 'fa-file-pdf';
                                        $file_color = 'text-red-500';
                                        $file_type_name = 'PDF';
                                        $file_preview = '<div class="bg-gray-100 h-40 flex items-center justify-center"><i class="fas fa-file-pdf text-red-500 text-5xl"></i></div>';
                                        break;
                                    case 'jpg':
                                    case 'jpeg':
                                    case 'png':
                                    case 'gif':
                                        $file_icon = 'fa-file-image';
                                        $file_color = 'text-green-500';
                                        $file_type_name = 'Image';
                                        $file_preview = '<img src="../' . htmlspecialchars($file['file_path']) . '" alt="Aperçu" class="h-40 w-full object-cover">';
                                        break;
                                    case 'mp4':
                                    case 'avi':
                                    case 'mov':
                                        $file_icon = 'fa-file-video';
                                        $file_color = 'text-blue-500';
                                        $file_type_name = 'Vidéo';
                                        $file_preview = '<div class="bg-gray-100 h-40 flex items-center justify-center"><i class="fas fa-file-video text-blue-500 text-5xl"></i></div>';
                                        break;
                                    default:
                                        $file_icon = 'fa-file';
                                        $file_color = 'text-gray-500';
                                        $file_type_name = 'Fichier';
                                        $file_preview = '<div class="bg-gray-100 h-40 flex items-center justify-center"><i class="fas fa-file text-gray-500 text-5xl"></i></div>';
                                        break;
                                }
                                
                                echo '<div class="bg-white border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition">';
                                echo $file_preview;
                                echo '<div class="p-4">';
                                echo '<div class="flex items-center mb-2">';
                                echo '<i class="fas ' . $file_icon . ' ' . $file_color . ' mr-2"></i>';
                                echo '<div class="text-sm font-medium text-gray-900 truncate">' . htmlspecialchars(basename($file['file_path'])) . '</div>';
                                echo '</div></div>';

                                echo '</div>';
                                echo '<div class="text-xs text-gray-500 mb-2">';
                                echo 'Par ' . htmlspecialchars($file['name']) . ' · ' . date('d/m/Y H:i', strtotime($file['created_at']));
                                echo '</div>';
                                echo '<div class="text-xs text-gray-500 mb-3">';
                                echo 'Dans ' . ($file['type'] == 'topic' ? 'la discussion' : 'une réponse à') . ': ' . htmlspecialchars($file['content_title']);
                                echo '</div>';
                                echo '<div class="flex justify-between">';
                                echo '<a href="../' . htmlspecialchars($file['file_path']) . '" target="_blank" class="text-primary hover:text-primary-hover text-sm">';
                                echo '<i class="fas fa-external-link-alt mr-1"></i> Voir';
                                echo '</a>';
                                echo '<button class="delete-file-btn text-red-600 hover:text-red-800 text-sm" data-path="' . htmlspecialchars($file['file_path']) . '" data-type="' . $file['type'] . '" data-id="' . $file['id'] . '">';
                                echo '<i class="fas fa-trash-alt mr-1"></i> Supprimer';
                                echo '</button>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            }
                            
                            echo '</div>';
                            
                            // Pagination
                            if ($total_pages > 1) {
                                echo '<div class="flex justify-center p-4 border-t">';
                                echo '<div class="flex space-x-1">';
                                
                                if ($page > 1) {
                                    echo '<a href="?section=files&page_num=' . ($page - 1) . '&file_type=' . $file_type . '&user_id=' . $user_id . '&search=' . urlencode($search) . '&sort=' . $sort . '" class="px-3 py-1 rounded-md border hover:bg-gray-100 transition">';
                                    echo '<i class="fas fa-chevron-left"></i>';
                                    echo '</a>';
                                }
                                
                                // Affichage des numéros de page
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1) {
                                    echo '<a href="?section=files&page_num=1&file_type=' . $file_type . '&user_id=' . $user_id . '&search=' . urlencode($search) . '&sort=' . $sort . '" class="px-3 py-1 rounded-md border hover:bg-gray-100 transition">1</a>';
                                    if ($start_page > 2) {
                                        echo '<span class="px-3 py-1">...</span>';
                                    }
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    $active_class = ($i == $page) ? 'bg-primary text-white' : 'hover:bg-gray-100';
                                    echo '<a href="?section=files&page_num=' . $i . '&file_type=' . $file_type . '&user_id=' . $user_id . '&search=' . urlencode($search) . '&sort=' . $sort . '" class="px-3 py-1 rounded-md border ' . $active_class . ' transition">' . $i . '</a>';
                                }
                                
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<span class="px-3 py-1">...</span>';
                                    }
                                    echo '<a href="?section=files&page_num=' . $total_pages . '&file_type=' . $file_type . '&user_id=' . $user_id . '&search=' . urlencode($search) . '&sort=' . $sort . '" class="px-3 py-1 rounded-md border hover:bg-gray-100 transition">' . $total_pages . '</a>';
                                }
                                
                                if ($page < $total_pages) {
                                    echo '<a href="?section=files&page_num=' . ($page + 1) . '&file_type=' . $file_type . '&user_id=' . $user_id . '&search=' . urlencode($search) . '&sort=' . $sort . '" class="px-3 py-1 rounded-md border hover:bg-gray-100 transition">';
                                    echo '<i class="fas fa-chevron-right"></i>';
                                    echo '</a>';
                                }
                                
                                echo '</div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="text-center py-8">';
                            echo '<i class="fas fa-search text-gray-300 text-5xl mb-4"></i>';
                            echo '<p class="text-gray-500">Aucun fichier trouvé.</p>';
                            echo '</div>';
                        }
                    } catch (PDOException $e) {
                        echo '<div class="p-6 text-center text-red-500">';
                        echo 'Erreur lors de la récupération des fichiers. Veuillez réessayer plus tard.';
                        echo '</div>';
                        // Log de l'erreur pour l'administrateur
                        error_log('Admin dashboard error: ' . $e->getMessage());
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modals de confirmation -->
    <!-- Modal de confirmation de suppression d'utilisateur -->
    <div id="delete-user-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Confirmer la suppression</h3>
            <p class="text-gray-600 mb-6">Êtes-vous sûr de vouloir supprimer l'utilisateur <span id="delete-user-name" class="font-semibold"></span> ? Cette action est irréversible et supprimera également toutes ses discussions, réponses et fichiers.</p>
            <div class="flex justify-end space-x-2">
                <button id="cancel-delete-user" class="px-4 py-2 border rounded-lg hover:bg-gray-100 transition">
                    Annuler
                </button>
                <button id="confirm-delete-user" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                    Supprimer
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de confirmation de suppression de discussion -->
    <div id="delete-topic-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Confirmer la suppression</h3>
            <p class="text-gray-600 mb-6">Êtes-vous sûr de vouloir supprimer la discussion <span id="delete-topic-title" class="font-semibold"></span> ? Cette action est irréversible et supprimera également toutes les réponses associées.</p>
            <div class="flex justify-end space-x-2">
                <button id="cancel-delete-topic" class="px-4 py-2 border rounded-lg hover:bg-gray-100 transition">
                    Annuler
                </button>
                <button id="confirm-delete-topic" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                    Supprimer
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de confirmation de suppression de réponse -->
    <div id="delete-reply-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Confirmer la suppression</h3>
            <p class="text-gray-600 mb-6">Êtes-vous sûr de vouloir supprimer cette réponse ? Cette action est irréversible.</p>
            <div class="flex justify-end space-x-2">
                <button id="cancel-delete-reply" class="px-4 py-2 border rounded-lg hover:bg-gray-100 transition">
                    Annuler
                </button>
                <button id="confirm-delete-reply" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                    Supprimer
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal de confirmation de suppression de fichier -->
    <div id="delete-file-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Confirmer la suppression</h3>
            <p class="text-gray-600 mb-6">Êtes-vous sûr de vouloir supprimer ce fichier ? Cette action est irréversible.</p>
            <div class="flex justify-end space-x-2">
                <button id="cancel-delete-file" class="px-4 py-2 border rounded-lg hover:bg-gray-100 transition">
                    Annuler
                </button>
                <button id="confirm-delete-file" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                    Supprimer
                </button>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Variables pour la sidebar
            const toggleSidebarBtn = document.getElementById('toggle-sidebar');
            const closeSidebarBtn = document.getElementById('close-sidebar');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            // Variables pour les modals de suppression
            let userIdToDelete = null;
            let topicIdToDelete = null;
            let replyIdToDelete = null;
            let fileToDelete = null;
            
            // Toggle sidebar sur mobile
            if (toggleSidebarBtn && closeSidebarBtn && sidebar && mainContent) {
                toggleSidebarBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    mainContent.classList.toggle('sidebar-active');
                });
                
                closeSidebarBtn.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    mainContent.classList.remove('sidebar-active');
                });
            }
            
            // Chargement de l'activité récente sur le tableau de bord
            const recentActivityContainer = document.getElementById('recent-activity');
            if (recentActivityContainer) {
                fetch('admin_ajax.php?action=get_recent_activity&csrf_token=<?php echo $csrf_token; ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            recentActivityContainer.innerHTML = data.html;
                        } else {
                            recentActivityContainer.innerHTML = '<div class="text-center py-4 text-red-500">Erreur lors du chargement de l\'activité récente.</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        recentActivityContainer.innerHTML = '<div class="text-center py-4 text-red-500">Erreur lors du chargement de l\'activité récente.</div>';
                    });
            }
            
            // Chargement de la liste des utilisateurs
            const usersListContainer = document.getElementById('users-list');
            if (usersListContainer) {
                const usersFilterForm = document.getElementById('users-filter-form');
                
                const loadUsersList = (search = '', sort = 'activity_desc') => {
                    usersListContainer.innerHTML = '<div class="text-center py-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-primary"></div><p class="mt-2 text-gray-500">Chargement des utilisateurs...</p></div>';
                    
                    fetch(`admin_ajax.php?action=get_users_list&search=${encodeURIComponent(search)}&sort=${sort}&csrf_token=<?php echo $csrf_token; ?>`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                usersListContainer.innerHTML = data.html;
                                
                                // Réattacher les événements aux boutons de suppression
                                document.querySelectorAll('.delete-user-btn').forEach(btn => {
                                    btn.addEventListener('click', function() {
                                        userIdToDelete = this.getAttribute('data-id');
                                        const name = this.getAttribute('data-name');
                                        document.getElementById('delete-user-name').textContent = name;
                                        document.getElementById('delete-user-modal').classList.remove('hidden');
                                    });
                                });
                            } else {
                                usersListContainer.innerHTML = '<div class="text-center py-4 text-red-500">Erreur lors du chargement des utilisateurs.</div>';
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            usersListContainer.innerHTML = '<div class="text-center py-4 text-red-500">Erreur lors du chargement des utilisateurs.</div>';
                        });
                };
                
                // Chargement initial
                loadUsersList();
                
                // Filtrage des utilisateurs
                if (usersFilterForm) {
                    usersFilterForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const search = this.querySelector('#search').value;
                        const sort = this.querySelector('#sort').value;
                        loadUsersList(search, sort);
                    });
                }
            }
            
            // Tabs pour le profil utilisateur
            const userTabBtns = document.querySelectorAll('.user-tab-btn');
            if (userTabBtns.length > 0) {
                userTabBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        // Retirer la classe active de tous les boutons
                        userTabBtns.forEach(b => {
                            b.classList.remove('active');
                            b.classList.remove('text-primary');
                            b.classList.remove('border-b-2');
                            b.classList.remove('border-primary');
                            b.classList.add('text-gray-500');
                        });
                        
                        // Ajouter la classe active au bouton cliqué
                        this.classList.add('active');
                        this.classList.add('text-primary');
                        this.classList.add('border-b-2');
                        this.classList.add('border-primary');
                        this.classList.remove('text-gray-500');
                        
                        // Cacher tous les contenus de tab
                        document.querySelectorAll('.user-tab-content').forEach(content => {
                            content.classList.add('hidden');
                        });
                        
                        // Afficher le contenu correspondant
                        const tabName = this.getAttribute('data-tab');
                        document.getElementById(tabName + '-tab').classList.remove('hidden');
                    });
                });
            }
            
            // Suppression d'utilisateur
            const deleteUserModal = document.getElementById('delete-user-modal');
            const cancelDeleteUser = document.getElementById('cancel-delete-user');
            const confirmDeleteUser = document.getElementById('confirm-delete-user');
            const deleteUserBtns = document.querySelectorAll('.delete-user-btn');
            
            if (deleteUserBtns.length > 0 && deleteUserModal && cancelDeleteUser && confirmDeleteUser) {
                deleteUserBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        userIdToDelete = this.getAttribute('data-id');
                        const name = this.getAttribute('data-name');
                        document.getElementById('delete-user-name').textContent = name;
                        deleteUserModal.classList.remove('hidden');
                    });
                });
                
                cancelDeleteUser.addEventListener('click', function() {
                    deleteUserModal.classList.add('hidden');
                    userIdToDelete = null;
                });
                
                confirmDeleteUser.addEventListener('click', function() {
                    if (userIdToDelete) {
                        const formData = new FormData();
                        formData.append('action', 'delete_user');
                        formData.append('user_id', userIdToDelete);
                        formData.append('csrf_token', '<?php echo $csrf_token; ?>');
                        
                        // Afficher un indicateur de chargement
                        const originalBtnText = confirmDeleteUser.innerHTML;
                        confirmDeleteUser.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression...';
                        confirmDeleteUser.disabled = true;
                        
                        fetch('admin_ajax.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Redirection vers la liste des utilisateurs
                                window.location.href = '?section=users';
                            } else {
                                // Afficher l'erreur
                                alert('Erreur : ' + data.message);
                                confirmDeleteUser.innerHTML = originalBtnText;
                                confirmDeleteUser.disabled = false;
                                deleteUserModal.classList.add('hidden');
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            alert('Une erreur est survenue. Veuillez réessayer.');
                            confirmDeleteUser.innerHTML = originalBtnText;
                            confirmDeleteUser.disabled = false;
                            deleteUserModal.classList.add('hidden');
                        });
                    }
                });
            }
            
            // Suppression de discussion
            const deleteTopicModal = document.getElementById('delete-topic-modal');
            const cancelDeleteTopic = document.getElementById('cancel-delete-topic');
            const confirmDeleteTopic = document.getElementById('confirm-delete-topic');
            const deleteTopicBtns = document.querySelectorAll('.delete-topic-btn');
            
            if (deleteTopicBtns.length > 0 && deleteTopicModal && cancelDeleteTopic && confirmDeleteTopic) {
                deleteTopicBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        topicIdToDelete = this.getAttribute('data-id');
                        const topicTitle = this.getAttribute('data-title');
                        document.getElementById('delete-topic-title').textContent = topicTitle;
                        deleteTopicModal.classList.remove('hidden');
                    });
                });
                
                cancelDeleteTopic.addEventListener('click', function() {
                    deleteTopicModal.classList.add('hidden');
                    topicIdToDelete = null;
                });
                
                confirmDeleteTopic.addEventListener('click', function() {
                    if (topicIdToDelete) {
                        const formData = new FormData();
                        formData.append('action', 'delete_topic');
                        formData.append('topic_id', topicIdToDelete);
                        formData.append('csrf_token', '<?php echo $csrf_token; ?>');
                        
                        // Afficher un indicateur de chargement
                        const originalBtnText = confirmDeleteTopic.innerHTML;
                        confirmDeleteTopic.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression...';
                        confirmDeleteTopic.disabled = true;
                        
                        fetch('admin_ajax.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Recharger la page
                                window.location.reload();
                            } else {
                                // Afficher l'erreur
                                alert('Erreur : ' + data.message);
                                confirmDeleteTopic.innerHTML = originalBtnText;
                                confirmDeleteTopic.disabled = false;
                                deleteTopicModal.classList.add('hidden');
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            alert('Une erreur est survenue. Veuillez réessayer.');
                            confirmDeleteTopic.innerHTML = originalBtnText;
                            confirmDeleteTopic.disabled = false;
                            deleteTopicModal.classList.add('hidden');
                        });
                    }
                });
            }
            
            // Suppression de réponse
            const deleteReplyModal = document.getElementById('delete-reply-modal');
            const cancelDeleteReply = document.getElementById('cancel-delete-reply');
            const confirmDeleteReply = document.getElementById('confirm-delete-reply');
            const deleteReplyBtns = document.querySelectorAll('.delete-reply-btn');
            
            if (deleteReplyBtns.length > 0 && deleteReplyModal && cancelDeleteReply && confirmDeleteReply) {
                deleteReplyBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        replyIdToDelete = this.getAttribute('data-id');
                        deleteReplyModal.classList.remove('hidden');
                    });
                });
                
                cancelDeleteReply.addEventListener('click', function() {
                    deleteReplyModal.classList.add('hidden');
                    replyIdToDelete = null;
                });
                
                confirmDeleteReply.addEventListener('click', function() {
                    if (replyIdToDelete) {
                        const formData = new FormData();
                        formData.append('action', 'delete_reply');
                        formData.append('reply_id', replyIdToDelete);
                        formData.append('csrf_token', '<?php echo $csrf_token; ?>');
                        
                        // Afficher un indicateur de chargement
                        const originalBtnText = confirmDeleteReply.innerHTML;
                        confirmDeleteReply.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression...';
                        confirmDeleteReply.disabled = true;
                        
                        fetch('admin_ajax.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Recharger la page
                                window.location.reload();
                            } else {
                                // Afficher l'erreur
                                alert('Erreur : ' + data.message);
                                confirmDeleteReply.innerHTML = originalBtnText;
                                confirmDeleteReply.disabled = false;
                                deleteReplyModal.classList.add('hidden');
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            alert('Une erreur est survenue. Veuillez réessayer.');
                            confirmDeleteReply.innerHTML = originalBtnText;
                            confirmDeleteReply.disabled = false;
                            deleteReplyModal.classList.add('hidden');
                        });
                    }
                });
            }
            
            // Suppression de fichier
            const deleteFileModal = document.getElementById('delete-file-modal');
            const cancelDeleteFile = document.getElementById('cancel-delete-file');
            const confirmDeleteFile = document.getElementById('confirm-delete-file');
            const deleteFileBtns = document.querySelectorAll('.delete-file-btn');
            
            if (deleteFileBtns.length > 0 && deleteFileModal && cancelDeleteFile && confirmDeleteFile) {
                deleteFileBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        fileToDelete = {
                            path: this.getAttribute('data-path'),
                            type: this.getAttribute('data-type'),
                            id: this.getAttribute('data-id')
                        };
                        deleteFileModal.classList.remove('hidden');
                    });
                });
                
                cancelDeleteFile.addEventListener('click', function() {
                    deleteFileModal.classList.add('hidden');
                    fileToDelete = null;
                });
                
                confirmDeleteFile.addEventListener('click', function() {
                    if (fileToDelete) {
                        const formData = new FormData();
                        formData.append('action', 'delete_file');
                        formData.append('file_path', fileToDelete.path);
                        formData.append('content_type', fileToDelete.type);
                        formData.append('content_id', fileToDelete.id);
                        formData.append('csrf_token', '<?php echo $csrf_token; ?>');
                        
                        // Afficher un indicateur de chargement
                        const originalBtnText = confirmDeleteFile.innerHTML;
                        confirmDeleteFile.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression...';
                        confirmDeleteFile.disabled = true;
                        
                        fetch('admin_ajax.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Recharger la page
                                window.location.reload();
                            } else {
                                // Afficher l'erreur
                                alert('Erreur : ' + data.message);
                                confirmDeleteFile.innerHTML = originalBtnText;
                                confirmDeleteFile.disabled = false;
                                deleteFileModal.classList.add('hidden');
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            alert('Une erreur est survenue. Veuillez réessayer.');
                            confirmDeleteFile.innerHTML = originalBtnText;
                            confirmDeleteFile.disabled = false;
                            deleteFileModal.classList.add('hidden');
                        });
                    }
                });
            }
            
            // Fermer les modals en cliquant en dehors
            window.addEventListener('click', function(e) {
                if (deleteUserModal && e.target === deleteUserModal) {
                    deleteUserModal.classList.add('hidden');
                    userIdToDelete = null;
                }
                
                if (deleteTopicModal && e.target === deleteTopicModal) {
                    deleteTopicModal.classList.add('hidden');
                    topicIdToDelete = null;
                }
                
                if (deleteReplyModal && e.target === deleteReplyModal) {
                    deleteReplyModal.classList.add('hidden');
                    replyIdToDelete = null;
                }
                
                if (deleteFileModal && e.target === deleteFileModal) {
                    deleteFileModal.classList.add('hidden');
                    fileToDelete = null;
                }
            });
            
            // Initialisation des graphiques
            <?php if ($section == 'dashboard' && !isset($error_message)): ?>
            // Graphique d'activité mensuelle
            const activityCtx = document.getElementById('activityChart').getContext('2d');
            const activityChart = new Chart(activityCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($months_labels); ?>,
                    datasets: [{
                        label: 'Activité mensuelle',
                        data: <?php echo json_encode($activity_data); ?>,
                        backgroundColor: 'rgba(1, 99, 203, 0.2)',
                        borderColor: 'rgba(1, 99, 203, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        pointBackgroundColor: 'rgba(1, 99, 203, 1)',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' messages';
                                }
                            }
                        }
                    }
                }
            });
            
            // Graphique des catégories
            const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
            const categoriesChart = new Chart(categoriesCtx, {
                type: 'doughnut',
                data: {
                    labels: [
                        <?php 
                        foreach ($categories_stats as $category) {
                            switch ($category['category']) {
                                case 'procedure': echo "'Procédure Campus France', "; break;
                                case 'visa': echo "'Visa étudiant', "; break;
                                case 'logement': echo "'Logement', "; break;
                                case 'etudes': echo "'Études et formations', "; break;
                                case 'vie': echo "'Vie en France', "; break;
                                case 'autre': echo "'Autres', "; break;
                            }
                        }
                        ?>
                    ],
                    datasets: [{
                        data: [
                            <?php 
                            foreach ($categories_stats as $category) {
                                echo $category['count'] . ', ';
                            }
                            ?>
                        ],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(153, 102, 255, 0.8)',
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(201, 203, 207, 0.8)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((acc, data) => acc + data, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>

