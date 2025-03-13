<?php
// Vérifier que la session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la connexion à la base de données si ce n'est pas déjà fait
if (!isset($pdo)) {
    require_once __DIR__ . '/../config/database.php'; // S'assurer que ce fichier initialise $pdo correctement
}

// Fonction pour nettoyer les entrées utilisateur
function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Fonction pour générer un token CSRF
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fonction pour vérifier si l'utilisateur est connecté
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Fonction pour vérifier si l'utilisateur est admin
function is_admin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Fonction pour rediriger en fonction du rôle
function redirect_by_role() {
    if (!is_logged_in()) {
        redirect('index.php?page=login');
        return;
    }
    
    // Vérifier explicitement le rôle admin
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        redirect('admin/admin_dashboard.php');
    } else {
        redirect('index.php?page=forum');
    }
}

// Fonction pour rediriger vers une URL
function redirect($url) {
    header("Location: $url");
    exit();
}

// Fonction pour définir et afficher des messages
function set_message($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function display_message() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message'], $_SESSION['message_type']);
        
        return '<div class="alert alert-' . $type . ' mb-4 p-4 rounded-lg ' . 
               ($type == 'success' ? 'bg-green-100 text-green-700' : 
               ($type == 'error' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700')) . 
               '">' . $message . '</div>';
    }
    return '';
}

// Fonction pour vérifier si un email existe déjà
function email_exists($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetchColumn() > 0;
}

// Fonction pour créer un nouvel utilisateur
function create_user($name, $email, $password, $role = 'etudiant') {
    global $pdo;

    if (email_exists($email)) {
        return "Cet email est déjà utilisé.";
    }

    // Hash du mot de passe
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // S'assurer qu'il n'y ait qu'un seul admin
    if ($role === 'admin') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        if ($stmt->fetchColumn() > 0) {
            return "Un administrateur existe déjà.";
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $email, $password_hash, $role]);
        return "Inscription réussie.";
    } catch (PDOException $e) {
        error_log("Erreur lors de la création de l'utilisateur : " . $e->getMessage());
        return "Erreur lors de l'inscription.";
    }
}

// Fonction pour authentifier un utilisateur
function authenticate_user($email, $password) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
    } catch (PDOException $e) {
        error_log("Erreur d'authentification : " . $e->getMessage());
    }
    return false;
}

// Fonction pour obtenir les informations d'un utilisateur
function get_user($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur récupération utilisateur : " . $e->getMessage());
        return null;
    }
}

// Fonction pour formater la date
function format_date($date_string, $format = 'd/m/Y à H:i') {
    return (new DateTime($date_string))->format($format);
}

// Fonction pour obtenir le nombre de réponses à une discussion
function get_reply_count($topic_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM forum_replies WHERE topic_id = ?");
    $stmt->execute([$topic_id]);
    return $stmt->fetchColumn();
}
?>
