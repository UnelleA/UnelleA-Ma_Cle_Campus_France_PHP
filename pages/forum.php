<div class="mt-[80px]"></div>

<?php

 
// Activer l'affichage des erreurs en développement (à désactiver en production)
if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Journaliser les erreurs importantes
function logError($message) {
    error_log("[FORUM ERROR] " . $message);
}

// Vérification du paramètre 'topic_id' passé dans l'URL et log
if (isset($_GET['topic_id'])) {
    error_log("DEBUG: topic_id passé dans l'URL : " . $_GET['topic_id']);
} else {
    error_log("DEBUG: topic_id non défini dans l'URL");
}

// Convertir 'topic_id' en entier pour éviter les injections SQL
$topic_id = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;

error_log("DEBUG: topic_id après conversion : " . $topic_id);

// Vérifier et démarrer la session uniquement si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la connexion à la base de données
require_once __DIR__ . '/../config/database.php';

// Vérifier si la connexion PDO est bien établie
if (!isset($pdo) || !$pdo) {
    error_log("DEBUG: Connexion PDO non établie !");
    die("Erreur de connexion à la base de données.");
} else {
    error_log("DEBUG: Connexion PDO OK.");
}

// Inclure les fonctions supplémentaires
require_once __DIR__ . '/../includes/functions.php'; // Inclure les fonctions

// Utilisation de la connexion existante
global $pdo;
$db = $pdo; // Utiliser la connexion PDO existante

// Vérifier si l'utilisateur est connecté
$is_logged_in = is_logged_in();
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Génération d'un token CSRF pour éviter les attaques
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Récupération des paramètres d'URL avec assainissement des données
$action = isset($_GET['action']) ? htmlspecialchars($_GET['action']) : 'list';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$category = isset($_GET['category']) ? htmlspecialchars($_GET['category']) : '';
$sort = isset($_GET['sort']) ? htmlspecialchars($_GET['sort']) : 'date_desc';
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';

// Nombre d'éléments par page (pagination)
$items_per_page = 10;
?>

<div class="bg-gray-50 min-h-screen py-8">
    <div class="container mx-auto px-4">
        <!-- En-tête du forum -->
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-primary mb-2">Forum des Étudiants Internationaux</h1>
            <p class="text-gray-600 max-w-2xl mx-auto">
                Échangez avec d'autres étudiants, posez vos questions et partagez vos expériences sur les études en France.
            </p>
        </div>

        <!-- Barre de recherche et filtres -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <form id="search-form" action="index.php" method="GET" class="space-y-4">
                <input type="hidden" name="page" value="forum">
                
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Barre de recherche -->
                    <div class="flex-grow">
                        <div class="relative">
                            <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" 
                                placeholder="Rechercher dans le forum..." 
                                class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                                aria-label="Recherche dans le forum">
                            <div class="absolute left-3 top-2.5 text-gray-400">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtres -->
                    <div class="flex flex-wrap gap-2">
                        <!-- Catégories -->
                        <select name="category" id="category" class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary"
                            aria-label="Filtrer par catégorie">
                            <option value="">Toutes les catégories</option>
                            <option value="procedure" <?php echo $category == 'procedure' ? 'selected' : ''; ?>>Procédure Campus France</option>
                            <option value="visa" <?php echo $category == 'visa' ? 'selected' : ''; ?>>Visa étudiant</option>
                            <option value="logement" <?php echo $category == 'logement' ? 'selected' : ''; ?>>Logement</option>
                            <option value="etudes" <?php echo $category == 'etudes' ? 'selected' : ''; ?>>Études et formations</option>
                            <option value="vie" <?php echo $category == 'vie' ? 'selected' : ''; ?>>Vie en France</option>
                            <option value="autre" <?php echo $category == 'autre' ? 'selected' : ''; ?>>Autres</option>
                        </select>
                        
                        <!-- Tri -->
                        <select name="sort" id="sort" class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary"
                            aria-label="Trier les discussions">
                            <option value="date_desc" <?php echo $sort == 'date_desc' ? 'selected' : ''; ?>>Plus récents</option>
                            <option value="date_asc" <?php echo $sort == 'date_asc' ? 'selected' : ''; ?>>Plus anciens</option>
                            <option value="replies" <?php echo $sort == 'replies' ? 'selected' : ''; ?>>Plus de réponses</option>
                            <option value="views" <?php echo $sort == 'views' ? 'selected' : ''; ?>>Plus vus</option>
                        </select>
                        
                        <!-- Bouton de recherche -->
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition"
                            aria-label="Filtrer les discussions">
                            Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php 
        // Vérification et action pour l'affichage de la discussion
        include('components/discussions_forum.php');

        if ($action == 'list'): 
            // la requête SQL pour vérifier la page
            error_log("DEBUG: Page actuelle : " . $page);
        
            // Connexion à la base de données (ajoutez votre propre connexion ici)
            $sql = "SELECT * FROM forum_topics ORDER BY created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Vérification si l'utilisateur est connecté
            $is_logged_in = isset($_SESSION['user_id']); // À ajuster selon ton système de gestion de session
            $user_id = $is_logged_in ? $_SESSION['user_id'] : null;
        ?>
            <!-- Liste des discussions -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <!-- En-tête de la liste -->
                <div class="flex justify-between items-center p-4 border-b bg-gray-50">
                    <h2 class="text-xl font-semibold text-gray-800">Discussions</h2>
                    <?php if ($is_logged_in): ?>
                        <button id="new-topic-btn" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition">
                            <i class="fas fa-plus mr-2"></i>Nouvelle discussion
                        </button>
                    <?php else: ?>
                        <a href="index.php?page=login" class="text-primary hover:underline">
                            Connectez-vous pour créer une discussion
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Formulaire de nouvelle discussion (caché par défaut) -->
                <?php if ($is_logged_in):  $csrf_token = generate_csrf_token();?>
                <div id="new-topic-form" class="p-4 border-b bg-gray-50 hidden">
                    <form id="create-topic-form" action="ajax/forum_actions.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="create_topic">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div>
                            <label for="topic_title" class="block text-sm font-medium text-gray-700 mb-1">Titre</label>
                            <input type="text" id="topic_title" name="title" required 
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label for="topic_category" class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                            <select id="topic_category" name="category" required 
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="procedure">Procédure Campus France</option>
                                <option value="visa">Visa étudiant</option>
                                <option value="logement">Logement</option>
                                <option value="etudes">Études et formations</option>
                                <option value="vie">Vie en France</option>
                                <option value="autre">Autres</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="topic_content" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                            <textarea 
                                id="topic_content" 
                                name="content" 
                                rows="5" 
                                required 
                                minlength="10" 
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                            ></textarea>
                        </div>
                        
                        <div>
                            <label for="topic_file" class="block text-sm font-medium text-gray-700 mb-1">Pièce jointe (facultatif)</label>
                            <input type="file" id="topic_file" name="file" 
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                            <p class="text-xs text-gray-500 mt-1">Formats acceptés : PDF, JPG, PNG, MP4 (max 5 Mo)</p>
                        </div>
                        
                        <div class="flex justify-end space-x-2">
                            <button type="button" id="cancel-topic-btn" class="px-4 py-2 border rounded-lg hover:bg-gray-100 transition">
                                Annuler
                            </button>
                            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition">
                                Publier
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <div id="topics-list">
              <?php
                try {
                    // Assurez-vous que la connexion à la base de données est valide
                    global $pdo;
                    $db = $pdo;
                
                    // Validation des entrées utilisateur
                    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
                    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                    $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'date_desc';
                    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                    $items_per_page = isset($_GET['items_per_page']) ? intval($_GET['items_per_page']) : 10;
                
                    // Validation des valeurs de pagination
                    $page = max(1, $page); // Assure que $page est au moins 1
                    $items_per_page = max(1, $items_per_page); // Assure que $items_per_page est au moins 1
                
                    // Construction de la requête SQL de base
                    $sql = "SELECT t.*, u.name, 
                                   (SELECT COUNT(*) FROM forum_replies WHERE topic_id = t.id) as reply_count 
                            FROM forum_topics t 
                            JOIN users u ON t.user_id = u.id 
                            WHERE 1=1";
                    $params = [];
                
                    // Ajout des filtres
                    if (!empty($category)) {
                        $sql .= " AND t.category = :category";
                        $params[':category'] = $category;
                    }
                
 
                    if (!empty($search)) {
                        $sql .= " AND (t.title LIKE :search_title OR t.content LIKE :search_content)";
                        $params[':search_title'] = "%$search%";
                        $params[':search_content'] = "%$search%";
                    }
                
                    // Ajout du tri
                    switch ($sort) {
                        case 'date_asc':
                            $sql .= " ORDER BY t.created_at ASC";
                            break;
                        case 'replies':
                            $sql .= " ORDER BY reply_count DESC, t.created_at DESC";
                            break;
                        case 'views':
                            $sql .= " ORDER BY t.views DESC, t.created_at DESC";
                            break;
                        default: // date_desc
                            $sql .= " ORDER BY t.created_at DESC";
                            break;
                    }
                
                    // Requête pour compter le nombre total de discussions
                    $count_sql = "SELECT COUNT(*) FROM ($sql) as total";
                    $count_stmt = $db->prepare($count_sql);
                    foreach ($params as $key => $value) {
                        $count_stmt->bindValue($key, $value);
                    }
                    $count_stmt->execute();
                    $total_topics = $count_stmt->fetchColumn();
                
                    // Calcul de la pagination
                    $total_pages = ceil($total_topics / $items_per_page);
                    $offset = ($page - 1) * $items_per_page;
                
                    // Ajout de la limite pour la pagination
                    $sql .= " LIMIT :offset, :items_per_page";
                
                    // Préparation et exécution de la requête
                    $stmt = $db->prepare($sql);
                
                    // Liaison des paramètres de filtres
                    foreach ($params as $key => $value) {
                        $stmt->bindValue($key, $value);
                    }
                
                    // Liaison des paramètres de pagination
                    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $stmt->bindValue(':items_per_page', $items_per_page, PDO::PARAM_INT);
                
                    // Exécution de la requête
                    $stmt->execute();
                    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                    // Affichage des discussions
                    if (count($topics) > 0) {
                        foreach ($topics as $topic) {
                            // Formatage de la date
                            $created_date = new DateTime($topic['created_at']);
                            $formatted_date = $created_date->format('d/m/Y à H:i');
                
                            // Détermination de la classe CSS pour la catégorie
                            $category_class = '';
                            $category_name = '';
                
                            switch ($topic['category']) {
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
                
                            // Affichage du sujet
                            echo '<div class="border-b hover:bg-gray-50 transition">';
                            echo '<a href="index.php?page=forum&action=view&topic_id=' . $topic['id'] . '" class="block p-4">';
                            echo '<div class="flex justify-between items-start">';
                            echo '<div class="flex-grow">';
                            echo '<h3 class="text-lg font-medium text-gray-900 hover:text-primary transition">' . htmlspecialchars($topic['title']) . '</h3>';
                            echo '<div class="flex flex-wrap items-center gap-2 mt-1">';
                            echo '<span class="px-2 py-1 rounded-full text-xs font-medium ' . $category_class . '">' . $category_name . '</span>';
                            
                            // Affichage de l'icône de pièce jointe si présente
                            if (!empty($topic['file_path'])) {
                                $file_extension = pathinfo($topic['file_path'], PATHINFO_EXTENSION);
                                $file_icon = '';
                
                                switch (strtolower($file_extension)) {
                                    case 'pdf':
                                        $file_icon = '<i class="fas fa-file-pdf text-red-500"></i>';
                                        break;
                                    case 'jpg':
                                    case 'jpeg':
                                    case 'png':
                                    case 'gif':
                                        $file_icon = '<i class="fas fa-file-image text-green-500"></i>';
                                        break;
                                    case 'mp4':
                                    case 'avi':
                                    case 'mov':
                                        $file_icon = '<i class="fas fa-file-video text-blue-500"></i>';
                                        break;
                                    default:
                                        $file_icon = '<i class="fas fa-file text-gray-500"></i>';
                                        break;
                                }
                                
                                echo '<span class="text-xs text-gray-500 flex items-center">' . $file_icon . ' Pièce jointe</span>';
                            }
                            
                            echo '</div>';
                            echo '<div class="text-sm text-gray-500 mt-1">Par <span class="font-medium">' . htmlspecialchars($topic['name']) . '</span> · ' . $formatted_date . '</div>';
                            echo '</div>';
                            echo '<div class="flex flex-col items-end text-sm text-gray-500">';
                            echo '<div class="flex items-center"><i class="fas fa-comment mr-1"></i> ' . $topic['reply_count'] . ' réponses</div>';
                            echo '<div class="flex items-center"><i class="fas fa-eye mr-1"></i> ' . $topic['views'] . ' vues</div>';
                            echo '</div>';
                            echo '</div>';
                            echo '</a>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="p-8 text-center text-gray-500">';
                        echo '<i class="fas fa-search text-4xl mb-2"></i>';
                        echo '<p>Aucune discussion trouvée.</p>';
                        if (!empty($search) || !empty($category)) {
                            echo '<p class="mt-2">Essayez de modifier vos critères de recherche.</p>';
                        }
                        echo '</div>';
                    }
                } catch (PDOException $e) {
                    // Gestion des erreurs
                    echo '<div class="p-4 text-center text-red-500">';
                    echo 'Erreur lors de la récupération des discussions. Veuillez réessayer plus tard.';
                    echo '</div>';
                    // Log de l'erreur pour l'administrateur
                    error_log('Forum error: ' . $e->getMessage());
                }
                ?>

                <!-- Pagination -->
                <?php if (isset($total_pages) && $total_pages > 1): ?>
                    <div class="flex justify-center p-4 border-t">
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="index.php?page=forum&action=list&page=<?php echo ($page - 1); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&search=<?php echo urlencode($search); ?>" 
                                class="px-3 py-1 rounded-md border hover:bg-gray-100 transition">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            // Affichage des numéros de page
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            // Lien vers la première page, si nécessaire
                            if ($start_page > 1) {
                                echo '<a href="index.php?page=forum&action=list&page=1&category=' . urlencode($category) . '&sort=' . urlencode($sort) . '&search=' . urlencode($search) . '" 
                                    class="px-3 py-1 rounded-md border hover:bg-gray-100 transition">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="px-3 py-1">...</span>';
                                }
                            }

                            // Lien vers les pages dans la plage de pages à afficher
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                $active_class = ($i == $page) ? 'bg-primary text-white' : 'hover:bg-gray-100';
                                echo '<a href="index.php?page=forum&action=list&page=' . $i . '&category=' . urlencode($category) . '&sort=' . urlencode($sort) . '&search=' . urlencode($search) . '" 
                                    class="px-3 py-1 rounded-md border ' . $active_class . ' transition">' . $i . '</a>';
                            }

                            // Lien vers la dernière page, si nécessaire
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<span class="px-3 py-1">...</span>';
                                }
                                echo '<a href="index.php?page=forum&action=list&page=' . $total_pages . '&category=' . urlencode($category) . '&sort=' . urlencode($sort) . '&search=' . urlencode($search) . '" 
                                    class="px-3 py-1 rounded-md border hover:bg-gray-100 transition">' . $total_pages . '</a>';
                            }
                            ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="index.php?page=forum&action=list&page=<?php echo ($page + 1); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&search=<?php echo urlencode($search); ?>" 
                                class="px-3 py-1 rounded-md border hover:bg-gray-100 transition">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php
            // DEBUGGING - Vérification du topic_id reçu
            error_log("DEBUG: topic_id reçu: " . $topic_id);

            // Vérification de la connexion PDO
            if (!$pdo) {
                error_log("DEBUG: Problème de connexion PDO.");
                die("Erreur de connexion à la base de données.");
            }

            // Vérification de l'existence de la discussion
            $stmt = $pdo->prepare("SELECT * FROM forum_topics WHERE id = ?");
            $stmt->execute([$topic_id]);
            $topic = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$topic) {
                error_log("DEBUG: Aucun topic trouvé pour topic_id: " . $topic_id);
                die();
            } else {
                error_log("DEBUG: Discussion trouvée avec succès : " . print_r($topic, true));
            }
            ?>

            <!-- Fil d'Ariane -->
            <div class="flex items-center mb-4 text-sm">
                <a href="index.php?page=forum" class="text-gray-500 hover:text-primary transition">Forum</a>
                <span class="mx-2 text-gray-400">/</span>
                <a href="index.php?page=forum&category=<?php echo $topic['category']; ?>" class="text-gray-500 hover:text-primary transition"><?php echo $category_name; ?></a>
                <span class="mx-2 text-gray-400">/</span>
                <span class="text-gray-700 truncate"><?php echo htmlspecialchars($topic['title']); ?></span>
            </div>

            <!-- Discussion principale -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <!-- En-tête de la discussion -->
                <div class="p-4 border-b bg-gray-50">
                    <h2 class="text-2xl font-semibold text-gray-800"><?php echo htmlspecialchars($topic['title']); ?></h2>
                    <div class="flex flex-wrap items-center gap-2 mt-1">
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $category_class; ?>"><?php echo $category_name; ?></span>
                        <span class="text-sm text-gray-500">
                            <i class="fas fa-eye mr-1"></i> <?php echo $topic['views']; ?> vues
                        </span>
                    </div>
                </div>
                
                <!-- Message principal -->
                <div class="p-4 border-b" id="topic-<?php echo $topic['id']; ?>">
                    <div class="flex">
                        <!-- Avatar et informations de l'utilisateur -->
                        <div class="mr-4">
                            <div class="w-12 h-12 rounded-full bg-gray-200 overflow-hidden">
                                <?php if (!empty($topic['avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars($topic['avatar']); ?>" alt="Avatar" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-primary text-white text-xl">
                                        <?php echo strtoupper(substr($topic['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="text-center mt-1 text-sm font-medium"><?php echo htmlspecialchars($topic['name']); ?></div>
                        </div>
                        
                        <!-- Contenu du message -->
                        <div class="flex-grow">
                            <div class="text-sm text-gray-500 mb-2">
                                Posté le <?php echo $formatted_date; ?>
                            </div>
                            
                            <div class="prose max-w-none mb-4 topic-content">
                                <?php echo nl2br(htmlspecialchars($topic['content'])); ?>
                            </div>
                            
                            <?php if (!empty($topic['file_path'])): ?>
                                <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                                    <?php
                                    $file_extension = strtolower(pathinfo($topic['file_path'], PATHINFO_EXTENSION));
                                    $file_name = basename($topic['file_path']);
                                    
                                    // Affichage différent selon le type de fichier
                                    if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                                        // Image
                                        echo '<div class="mb-2 font-medium text-sm">Image jointe :</div>';
                                        echo '<img src="' . htmlspecialchars($topic['file_path']) . '" alt="Image jointe" class="max-w-full max-h-96 rounded">';
                                    } elseif (in_array($file_extension, ['mp4', 'avi', 'mov'])) {
                                        // Vidéo
                                        echo '<div class="mb-2 font-medium text-sm">Vidéo jointe :</div>';
                                        echo '<video controls class="max-w-full max-h-96 rounded">
                                                <source src="' . htmlspecialchars($topic['file_path']) . '" type="video/' . $file_extension . '">
                                                Votre navigateur ne supporte pas la lecture de vidéos.
                                              </video>';
                                    } elseif ($file_extension == 'pdf') {
                                        // PDF
                                        echo '<div class="flex items-center">';
                                        echo '<i class="fas fa-file-pdf text-red-500 text-2xl mr-2"></i>';
                                        echo '<div>';
                                        echo '<div class="font-medium">' . htmlspecialchars($file_name) . '</div>';
                                        echo '<a href="' . htmlspecialchars($topic['file_path']) . '" target="_blank" class="text-primary hover:underline text-sm">Ouvrir le PDF</a>';
                                        echo '</div>';
                                        echo '</div>';
                                    } else {
                                        // Autres types de fichiers
                                        echo '<div class="flex items-center">';
                                        echo '<i class="fas fa-file text-gray-500 text-2xl mr-2"></i>';
                                        echo '<div>';
                                        echo '<div class="font-medium">' . htmlspecialchars($file_name) . '</div>';
                                        echo '<a href="' . htmlspecialchars($topic['file_path']) . '" download class="text-primary hover:underline text-sm">Télécharger</a>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($reply['file_path'])): ?>
                <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                    <?php
                    $file_extension = strtolower(pathinfo($reply['file_path'], PATHINFO_EXTENSION));
                    $file_name = basename($reply['file_path']);
                    
                    switch ($file_extension) {
                        case 'jpg':
                        case 'jpeg':
                        case 'png':
                        case 'gif':
                            echo '<div class="mb-2 font-medium text-sm">Image jointe :</div>';
                            echo '<img src="' . htmlspecialchars($reply['file_path']) . '" alt="Image jointe" class="max-w-full max-h-96 rounded">';
                            break;
                        case 'mp4':
                        case 'avi':
                        case 'mov':
                            echo '<div class="mb-2 font-medium text-sm">Vidéo jointe :</div>';
                            echo '<video controls class="max-w-full max-h-96 rounded">
                                    <source src="' . htmlspecialchars($reply['file_path']) . '" type="video/' . $file_extension . '">
                                    Votre navigateur ne supporte pas la lecture de vidéos.
                                  </video>';
                            break;
                        case 'pdf':
                            echo '<div class="flex items-center">';
                            echo '<i class="fas fa-file-pdf text-red-500 text-2xl mr-2"></i>';
                            echo '<div>';
                            echo '<div class="font-medium">' . htmlspecialchars($file_name) . '</div>';
                            echo '<a href="' . htmlspecialchars($reply['file_path']) . '" target="_blank" class="text-primary hover:underline text-sm">Ouvrir le PDF</a>';
                            echo '</div>';
                            echo '</div>';
                            break;
                        default:
                            echo '<div class="flex items-center">';
                            echo '<i class="fas fa-file text-gray-500 text-2xl mr-2"></i>';
                            echo '<div>';
                            echo '<div class="font-medium">' . htmlspecialchars($file_name) . '</div>';
                            echo '<a href="' . htmlspecialchars($reply['file_path']) . '" download class="text-primary hover:underline text-sm">Télécharger</a>';
                            echo '</div>';
                            echo '</div>';
                            break;
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Page non trouvée -->
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <i class="fas fa-exclamation-circle text-red-500 text-4xl mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Page introuvable</h2>
                <p class="text-gray-600 mb-4">La page que vous recherchez n'existe pas.</p>
                <a href="index.php?page=forum" class="inline-block bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition">Retour au forum</a>
            </div>
        <?php endif; ?>
    </div>
</div>

 
