<?php
require_once __DIR__ . '/../config/database.php'; 

if (!isset($pdo)) {
    die(json_encode(["success" => false, "message" => "Erreur de connexion à la base de données."]));
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialisation de la session
session_start();

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit(json_encode(['success' => false, 'message' => 'Méthode non autorisée']));
}

// Vérification de l'action demandée
if (!isset($_POST['action'])) {
    exit(json_encode(['success' => false, 'message' => 'Action non spécifiée']));
}

// Vérification de l'authentification
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
if (!$user_id) {
    exit(json_encode(['success' => false, 'message' => 'Vous devez être connecté pour effectuer cette action']));
}

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    exit(json_encode(['success' => false, 'message' => 'Token de sécurité invalide']));
}

// Connexion à la base de données
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    exit(json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']));
}

// Fonction pour valider et traiter un fichier uploadé
function processUploadedFile($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Vérification de la taille du fichier (5 Mo max)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Le fichier est trop volumineux (5 Mo maximum)');
    }
    
    // Vérification du type de fichier
    $allowed_types = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
        'application/pdf',
        'video/mp4', 'video/avi', 'video/quicktime'
    ];
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    
    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception('Type de fichier non autorisé');
    }
    
    // Génération d'un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid() . '.' . $extension;
    
    // Création du répertoire d'upload s'il n'existe pas
    $upload_dir = '../uploads/forum/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $upload_path = $upload_dir . $new_filename;
    
    // Déplacement du fichier
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Erreur lors de l\'upload du fichier');
    }
    
    // Retourner le chemin relatif pour stockage en base de données
    return 'uploads/forum/' . $new_filename;
}

// Traitement des différentes actions
$action = $_POST['action'];

switch ($action) {
    case 'create_topic':
        // Vérification des données requises
        if (empty($_POST['title']) || empty($_POST['content']) || empty($_POST['category'])) {
            exit(json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis']));
        }
        
        // Validation des données
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $category = $_POST['category'];
        
        if (strlen($title) < 5 || strlen($title) > 255) {
            exit(json_encode(['success' => false, 'message' => 'Le titre doit contenir entre 5 et 255 caractères']));
        }
        
        if (strlen($content) < 10) {
            exit(json_encode(['success' => false, 'message' => 'Le contenu doit contenir au moins 10 caractères']));
        }
        
        $allowed_categories = ['procedure', 'visa', 'logement', 'etudes', 'vie', 'autre'];
        if (!in_array($category, $allowed_categories)) {
            exit(json_encode(['success' => false, 'message' => 'Catégorie invalide']));
        }
        
        // Traitement du fichier joint (si présent)
        $file_path = null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $file_path = processUploadedFile($_FILES['file']);
            } catch (Exception $e) {
                exit(json_encode(['success' => false, 'message' => $e->getMessage()]));
            }
        }
        
        // Insertion dans la base de données
        try {
            $stmt = $pdo->prepare("INSERT INTO forum_topics (title, content, category, user_id, file_path, created_at, views) VALUES (?, ?, ?, ?, ?, NOW(), 0)");
            $stmt->execute([$title, $content, $category, $user_id, $file_path]);
        
            $topic_id = $pdo->lastInsertId();
        
            if ($topic_id) {
                // Retourner l'URL de redirection
                $redirect_url = "/index.php?page=forum&action=view&topic_id=" . $topic_id;
                header("Location: " . $redirect_url);
                exit(json_encode(['success' => true, 'topic_id' => $topic_id, 'redirect_url' => $redirect_url, 'message' => 'Discussion créée avec succès']));
            } else {
                throw new Exception("Impossible de récupérer l'ID de la discussion");
            }
        } catch (PDOException $e) {
            exit(json_encode(['success' => false, 'message' => 'Erreur lors de la création de la discussion: ' . $e->getMessage()]));
        }
        break;

    case 'update_topic':
        // Vérification des données requises
        if (empty($_POST['topic_id']) || empty($_POST['title']) || empty($_POST['content']) || empty($_POST['category'])) {
            exit(json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis']));
        }
        
        // Validation des données
        $topic_id = (int)$_POST['topic_id'];
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $category = $_POST['category'];
        
        if (strlen($title) < 5 || strlen($title) > 255) {
            exit(json_encode(['success' => false, 'message' => 'Le titre doit contenir entre 5 et 255 caractères']));
        }
        
        if (strlen($content) < 10) {
            exit(json_encode(['success' => false, 'message' => 'Le contenu doit contenir au moins 10 caractères']));
        }
        
        $allowed_categories = ['procedure', 'visa', 'logement', 'etudes', 'vie', 'autre'];
        if (!in_array($category, $allowed_categories)) {
            exit(json_encode(['success' => false, 'message' => 'Catégorie invalide']));
        }
        
        // Vérification que l'utilisateur est bien l'auteur de la discussion
        try {
            $stmt = $db->prepare("SELECT user_id FROM forum_topics WHERE id = ?");
            $stmt->execute([$topic_id]);
            $topic = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$topic) {
                exit(json_encode(['success' => false, 'message' => 'Discussion introuvable']));
            }
            
            if ($topic['user_id'] != $user_id) {
                exit(json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier cette discussion']));
            }
            
            // Mise à jour dans la base de données
            $stmt = $db->prepare("UPDATE forum_topics SET title = ?, content = ?, category = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$title, $content, $category, $topic_id]);
            
            exit(json_encode(['success' => true]));
        } catch (PDOException $e) {
            exit(json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de la discussion']));
        }
        break;
        
    case 'delete_topic':
        // Vérification des données requises
        if (empty($_POST['topic_id'])) {
            exit(json_encode(['success' => false, 'message' => 'ID de discussion non spécifié']));
        }
        
        $topic_id = (int)$_POST['topic_id'];
        
        // Vérification que l'utilisateur est bien l'auteur de la discussion
        try {
            $stmt = $db->prepare("SELECT user_id, file_path FROM forum_topics WHERE id = ?");
            $stmt->execute([$topic_id]);
            $topic = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$topic) {
                exit(json_encode(['success' => false, 'message' => 'Discussion introuvable']));
            }
            
            if ($topic['user_id'] != $user_id) {
                exit(json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à supprimer cette discussion']));
            }
            
            // Récupération des fichiers joints aux réponses pour suppression
            $stmt = $db->prepare("SELECT file_path FROM forum_replies WHERE topic_id = ? AND file_path IS NOT NULL");
            $stmt->execute([$topic_id]);
            $reply_files = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Suppression des réponses
            $stmt = $db->prepare("DELETE FROM forum_replies WHERE topic_id = ?");
            $stmt->execute([$topic_id]);
            
            // Suppression de la discussion
            $stmt = $db->prepare("DELETE FROM forum_topics WHERE id = ?");
            $stmt->execute([$topic_id]);
            
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
            exit(json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la discussion']));
        }
        break;
        
    case 'create_reply':
        // Vérification des données requises
        if (empty($_POST['topic_id']) || empty($_POST['content'])) {
            exit(json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis']));
        }
        
        // Validation des données
        $topic_id = (int)$_POST['topic_id'];
        $content = trim($_POST['content']);
        
        if (strlen($content) < 5) {
            exit(json_encode(['success' => false, 'message' => 'Le contenu doit contenir au moins 5 caractères']));
        }
        
        // Vérification que la discussion existe
        try {
            $stmt = $db->prepare("SELECT id FROM forum_topics WHERE id = ?");
            $stmt->execute([$topic_id]);
            
            if (!$stmt->fetch()) {
                exit(json_encode(['success' => false, 'message' => 'Discussion introuvable']));
            }
            
            // Traitement du fichier joint (si présent)
            $file_path = null;
            if (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
                try {
                    $file_path = processUploadedFile($_FILES['file']);
                } catch (Exception $e) {
                    exit(json_encode(['success' => false, 'message' => $e->getMessage()]));
                }
            }
            
            // Insertion dans la base de données
            $stmt = $db->prepare("INSERT INTO forum_replies (topic_id, user_id, content, file_path, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$topic_id, $user_id, $content, $file_path]);
            
            exit(json_encode(['success' => true]));
        } catch (PDOException $e) {
            exit(json_encode(['success' => false, 'message' => 'Erreur lors de la création de la réponse']));
        }
        break;
        
    case 'update_reply':
        // Vérification des données requises
        if (empty($_POST['reply_id']) || empty($_POST['content'])) {
            exit(json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis']));
        }
        
        // Validation des données
        $reply_id = (int)$_POST['reply_id'];
        $content = trim($_POST['content']);
        
        if (strlen($content) < 5) {
            exit(json_encode(['success' => false, 'message' => 'Le contenu doit contenir au moins 5 caractères']));
        }
        
        // Vérification que l'utilisateur est bien l'auteur de la réponse
        try {
            $stmt = $db->prepare("SELECT user_id FROM forum_replies WHERE id = ?");
            $stmt->execute([$reply_id]);
            $reply = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reply) {
                exit(json_encode(['success' => false, 'message' => 'Réponse introuvable']));
            }
            
            if ($reply['user_id'] != $user_id) {
                exit(json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier cette réponse']));
            }
            
            // Mise à jour dans la base de données
            $stmt = $db->prepare("UPDATE forum_replies SET content = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$content, $reply_id]);
            
            exit(json_encode(['success' => true]));
        } catch (PDOException $e) {
            exit(json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de la réponse']));
        }
        break;
        
    case 'delete_reply':
        // Vérification des données requises
        if (empty($_POST['reply_id'])) {
            exit(json_encode(['success' => false, 'message' => 'ID de réponse non spécifié']));
        }
        
        $reply_id = (int)$_POST['reply_id'];
        
        // Vérification que l'utilisateur est bien l'auteur de la réponse
        try {
            $stmt = $db->prepare("SELECT user_id, file_path FROM forum_replies WHERE id = ?");
            $stmt->execute([$reply_id]);
            $reply = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reply) {
                exit(json_encode(['success' => false, 'message' => 'Réponse introuvable']));
            }
            
            if ($reply['user_id'] != $user_id) {
                exit(json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à supprimer cette réponse']));
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
        
    default:
        exit(json_encode(['success' => false, 'message' => 'Action non reconnue']));
}
?>

