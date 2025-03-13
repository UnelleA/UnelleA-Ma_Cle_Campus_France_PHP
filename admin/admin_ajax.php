<?php
// Initialisation de la session
session_start();

// Vérification des droits d'administration
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => 'Accès non autorisé']));
}

// Vérification du token CSRF
if ((!isset($_POST['csrf_token']) && !isset($_GET['csrf_token'])) || 
    (!isset($_SESSION['admin_csrf_token'])) || 
    (isset($_POST['csrf_token']) && $_POST['csrf_token'] !== $_SESSION['admin_csrf_token']) ||
    (isset($_GET['csrf_token']) && $_GET['csrf_token'] !== $_SESSION['admin_csrf_token'])) {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => 'Token de sécurité invalide']));
}

// Connexion à la base de données
require_once '../config/database.php';
try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    exit(json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']));
}

// Traitement des actions AJAX
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

switch ($action) {
    case 'get_recent_activity':
        // Récupération de l'activité récente (10 dernières actions)
        try {
            $stmt = $db->query("
                (SELECT 
                    'topic' as type,
                    t.id,
                    t.title,
                    t.created_at,
                    u.username,
                    NULL as topic_id
                FROM 
                    forum_topics t
                JOIN 
                    users u ON t.user_id = u.id
                ORDER BY 
                    t.created_at DESC
                LIMIT 5)
                
                UNION ALL
                
                (SELECT 
                    'reply' as type,
                    r.id,
                    t.title,
                    r.created_at,
                    u.username,
                    r.topic_id
                FROM 
                    forum_replies r
                JOIN 
                    forum_topics t ON r.topic_id = t.id
                JOIN 
                    users u ON r.user_id = u.id
                ORDER BY 
                    r.created_at DESC
                LIMIT 5)
                
                ORDER BY 
                    created_at DESC
                LIMIT 10
            ");
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $html = '';
            
            if (count($activities) > 0) {
                $html .= '<div class="divide-y">';
                
                foreach ($activities as $activity) {
                    $created_date = new DateTime($activity['created_at']);
                    $formatted_date = $created_date->format('d/m/Y H:i');
                    
                    $html .= '<div class="py-4">';
                    $html .= '<div class="flex items-start">';
                    
                    if ($activity['type'] == 'topic') {
                        $html .= '<div class="bg-blue-100 text-blue-800 p-2 rounded-full mr-3">';
                        $html .= '<i class="fas fa-comment"></i>';
                        $html .= '</div>';
                        $html .= '<div class="flex-grow">';
                        $html .= '<p class="text-sm font-medium">' . htmlspecialchars($activity['username']) . ' a créé une nouvelle discussion</p>';
                        $html .= '<p class="text-sm text-gray-500">' . $formatted_date . '</p>';
                        $html .= '<a href="../index.php?page=forum&action=view&topic_id=' . $activity['id'] . '" target="_blank" class="text-primary hover:text-primary-hover text-sm mt-1 inline-block">';
                        $html .= htmlspecialchars($activity['title']);
                        $html .= '</a>';
                        $html .= '</div>';
                    } else {
                        $html .= '<div class="bg-green-100 text-green-800 p-2 rounded-full mr-3">';
                        $html .= '<i class="fas fa-reply"></i>';
                        $html .= '</div>';
                        $html .= '<div class="flex-grow">';
                        $html .= '<p class="text-sm font-medium">' . htmlspecialchars($activity['username']) . ' a répondu à une discussion</p>';
                        $html .= '<p class="text-sm text-gray-500">' . $formatted_date . '</p>';
                        $html .= '<a href="../index.php?page=forum&action=view&topic_id=' . $activity['topic_id'] . '" target="_blank" class="text-primary hover:text-primary-hover text-sm mt-1 inline-block">';
                        $html .= htmlspecialchars($activity['title']);
                        $html .= '</a>';
                        $html .= '</div>';
                    }
                    
                    $html .= '</div>';
                    $html .= '</div>';
                }
                
                $html .= '</div>';
            } else {
                $html .= '<div class="text-center py-8">';
                $html .= '<i class="fas fa-info-circle text-gray-300 text-5xl mb-4"></i>';
                $html .= '<p class="text-gray-500">Aucune activité récente.</p>';
                $html .= '</div>';
            }
            
            exit(json_encode(['success' => true, 'html' => $html]));
        } catch (PDOException $e) {
            exit(json_encode(['success' => false, 'message' => 'Erreur lors de la récupération de l\'activité récente']));
        }
        break;
        
    case 'get_users_list':
        // Récupération de la liste des utilisateurs avec filtrage et tri
        try {
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'activity_desc';
            
            // Construction de la requête SQL
            $sql = "SELECT 
                        u.id, 
                        u.username, 
                        u.email,
                        u.created_at,
                        (SELECT COUNT(*) FROM forum_topics WHERE user_id = u.id) as topics_count,
                        (SELECT COUNT(*) FROM forum_replies WHERE user_id = u.id) as replies_count,
                        (
                            (SELECT COUNT(*) FROM forum_topics WHERE user_id = u.id) + 
                            (SELECT COUNT(*) FROM forum_replies WHERE user_id = u.id)
                        ) as activity_count
                    FROM 
                        users u
                    WHERE 1=1";
            
            $params = [];
            
            // Ajout du filtre de recherche
            if (!empty($search)) {
                $sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            // Ajout du tri
            switch ($sort) {
                case 'activity_asc':
                    $sql .= " ORDER BY activity_count ASC, u.username ASC";
                    break;
                case 'username_asc':
                    $sql .= " ORDER BY u.username ASC";
                    break;
                case 'username_desc':
                    $sql .= " ORDER BY u.username DESC";
                    break;
                case 'date_desc':
                    $sql .= " ORDER BY u.created_at DESC, u.username ASC";
                    break;
                case 'date_asc':
                    $sql .= " ORDER BY u.created_at ASC, u.username ASC";
                    break;
                default: // activity_desc
                    $sql .= " ORDER BY activity_count DESC, u.username ASC";
                    break;
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $html = '';
            
            if (count($users) > 0) {
                $html .= '<div class="overflow-x-auto">';
                $html .= '<table class="min-w-full bg-white">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>';
                $html .= '<th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>';
                $html .= '<th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inscription</th>';
                $html .= '<th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activité</th>';
                $html .= '<th class="py-3 px-4 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>';
                $html .= '</tr>';
                $html .= '</thead>';
                $html .= '<tbody class="divide-y divide-gray-200">';
                
                foreach ($users as $user) {
                    $html .= '<tr>';
                    $html .= '<td class="py-4 px-4 whitespace-nowrap">';
                    $html .= '<div class="flex items-center">';
                    $html .= '<div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary-light flex items-center justify-center text-primary font-bold">';
                    $html .= strtoupper(substr($user['username'], 0, 1));
                    $html .= '</div>';
                    $html .= '<div class="ml-4">';
                    $html .= '<div class="text-sm font-medium text-gray-900">' . htmlspecialchars($user['username']) . '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</td>';
                    $html .= '<td class="py-4 px-4 whitespace-nowrap">';
                    $html .= '<div class="text-sm text-gray-900">' . htmlspecialchars($user['email']) . '</div>';
                    $html .= '</td>';
                    $html .= '<td class="py-4 px-4 whitespace-nowrap">';
                    $html .= '<div class="text-sm text-gray-500">' . date('d/m/Y', strtotime($user['created_at'])) . '</div>';
                    $html .= '</td>';
                    $html .= '<td class="py-4 px-4 whitespace-nowrap">';
                    $html .= '<div class="flex space-x-2">';
                    $html .= '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">';
                    $html .= $user['topics_count'] . ' discussions';
                    $html .= '</span>';
                    $html .= '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">';
                    $html .= $user['replies_count'] . ' réponses';
                    $html .= '</span>';
                    $html .= '</div>';
                    $html .= '</td>';
                    $html .= '<td class="py-4 px-4 whitespace-nowrap text-sm font-medium">';
                    $html .= '<a href="?section=users&user_id=' . $user['id'] . '" class="text-primary hover:text-primary-hover mr-3">';
                    $html .= '<i class="fas fa-eye"></i> Voir';
                    $html .= '</a>';
                    $html .= '<button class="delete-user-btn text-red-600 hover:text-red-800" data-id="' . $user['id'] . '" data-name="' . htmlspecialchars($user['username']) . '">';
                    $html .= '<i class="fas fa-trash-alt"></i> Supprimer';
                    $html .= '</button>';
                    $html .= '</td>';
                    $html .= '</tr>';
                }
                
                $html .= '</tbody>';
                $html .= '</table>';
                $html .= '</div>';
            } else {
                $html .= '<div class="text-center py-8">';
                $html .= '<i class="fas fa-users text-gray-300 text-5xl mb-4"></i>';
                $html .= '<p class="text-gray-500">Aucun utilisateur trouvé.</p>';
                $html .= '</div>';
            }
            
            exit(json_encode(['success' => true, 'html' => $html]));
        } catch (PDOException $e) {
            exit(json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des utilisateurs']));
        }
        break;
        
    case 'delete_user':
        // Suppression d'un utilisateur et de tout son contenu
        try {
            if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
                exit(json_encode(['success' => false, 'message' => 'ID utilisateur non spécifié']));
            }
            
            $user_id = (int)$_POST['user_id'];
            
            // Vérification que l'utilisateur existe
            $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            if (!$stmt->fetch()) {
                exit(json_encode(['success' => false, 'message' => 'Utilisateur introuvable']));
            }
            
            // Récupération des fichiers à supprimer
            $stmt = $db->prepare("
                SELECT file_path FROM forum_topics WHERE user_id = ? AND file_path IS NOT NULL AND file_path != ''
                UNION ALL
                SELECT file_path FROM forum_replies WHERE user_id = ? AND file_path IS NOT NULL AND file_path != ''
            ");
            $stmt->execute([$user_id, $user_id]);
            $files = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Début de la transaction
            $db->beginTransaction();
            
            // Suppression des réponses
            $stmt = $db->prepare("DELETE FROM forum_replies WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Suppression des discussions
            $stmt = $db->prepare("DELETE FROM forum_topics WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Suppression de l'utilisateur
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            // Validation de la transaction
            $db->commit();
            
            // Suppression des fichiers physiques
            foreach ($files as $file) {
                $file_path = '../' . $file;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            exit(json_encode(['success' => true]));
        } catch (PDOException $e) {
            // Annulation de la transaction en cas d'erreur
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            exit(json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de l\'utilisateur']));
        }
        break;
        
    case 'delete_topic':
        // Suppression d'une discussion et de toutes ses réponses
        try {
            if (!isset($_POST['topic_id']) || empty($_POST['topic_id'])) {
                exit(json_encode(['success' => false, 'message' => 'ID de discussion non spécifié']));
            }
            
            $topic_id = (int)$_POST['topic_id'];
            
            // Vérification que la discussion existe
            $stmt = $db->prepare("SELECT id, file_path FROM forum_topics WHERE id = ?");
            $stmt->execute([$topic_id]);
            $topic = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$topic) {
                exit(json_encode(['success' => false, 'message' => 'Discussion introuvable']));
            }
            
            // Récupération des fichiers des réponses à supprimer
            $stmt = $db->prepare("SELECT file_path FROM forum_replies WHERE topic_id = ? AND file_path IS NOT NULL AND file_path != ''");
            $stmt->execute([$topic_id]);
            $reply_files = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Début de la transaction
            $db->beginTransaction();
            
            // Suppression des réponses
            $stmt = $db->prepare("DELETE FROM forum_replies WHERE topic_id = ?");
            $stmt->execute([$topic_id]);
            
            // Suppression de la discussion
            $stmt = $db->prepare("DELETE FROM forum_topics WHERE id = ?");
            $stmt->execute([$topic_id]);
            
            // Validation de la transaction
            $db->commit();
            
            // Suppression des fichiers physiques
            if (!empty($topic['file_path'])) {
                $file_path = '../' . $topic['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            foreach ($reply_files as $file) {
                $file_path = '../' . $file;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            exit(json_encode(['success' => true]));
        } catch (PDOException $e) {
            // Annulation de la transaction en cas d'erreur
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            exit(json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la discussion']));
        }
        break;
        
    case 'delete_reply':
        // Suppression d'une réponse
        try {
            if (!isset($_POST['reply_id']) || empty($_POST['reply_id'])) {
                exit(json_encode(['success' => false, 'message' => 'ID de réponse non spécifié']));
            }
            
            $reply_id = (int)$_POST['reply_id'];
            
            // Vérification que la réponse existe
            $stmt = $db->prepare("SELECT id, file_path FROM forum_replies WHERE id = ?");
            $stmt->execute([$reply_id]);
            $reply = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reply) {
                exit(json_encode(['success' => false, 'message' => 'Réponse introuvable']));
            }
            
            // Suppression de la réponse
            $stmt = $db->prepare("DELETE FROM forum_replies WHERE id = ?");
            $stmt->execute([$reply_id]);
            
            // Suppression du fichier physique si présent
            if (!empty($reply['file_path'])) {
                $file_path = '../' . $reply['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            exit(json_encode(['success' => true]));
        } catch (PDOException $e) {
            exit(json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la réponse']));
        }
        break;
        
    case 'delete_file':
        // Suppression d'un fichier (sans supprimer le contenu associé)
        try {
            if (!isset($_POST['file_path']) || empty($_POST['file_path']) || 
                !isset($_POST['content_type']) || empty($_POST['content_type']) || 
                !isset($_POST['content_id']) || empty($_POST['content_id'])) {
                exit(json_encode(['success' => false, 'message' => 'Informations du fichier non spécifiées']));
            }
            
            $file_path = $_POST['file_path'];
            $content_type = $_POST['content_type'];
            $content_id = (int)$_POST['content_id'];
            
            // Mise à jour de l'enregistrement pour supprimer la référence au fichier
            if ($content_type == 'topic') {
                $stmt = $db->prepare("UPDATE forum_topics SET file_path = NULL WHERE id = ?");
            } else {
                $stmt = $db->prepare("UPDATE forum_replies SET file_path = NULL WHERE id = ?");
            }
            $stmt->execute([$content_id]);
            
            // Suppression du fichier physique
            $full_path = '../' . $file_path;
            if (file_exists($full_path)) {
                unlink($full_path);
            }
            
            exit(json_encode(['success' => true]));
        } catch (PDOException $e) {
            exit(json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du fichier']));
        }
        break;
        
    default:
        exit(json_encode(['success' => false, 'message' => 'Action non reconnue']));
}
?>

