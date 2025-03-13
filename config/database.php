<?php
// Paramètres de connexion à la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ma_cle_campus_france_unelle');

// Connexion à la base de données
try {
  // Utilisation des constantes définies pour la connexion PDO
  $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false, // Utiliser de vraies requêtes préparées
      PDO::MYSQL_ATTR_FOUND_ROWS => true
  ]);
} catch (PDOException $e) {
  error_log("Erreur de connexion à la base de données: " . $e->getMessage());
  die("Impossible de se connecter à la base de données. Veuillez réessayer plus tard.");
}
?>
