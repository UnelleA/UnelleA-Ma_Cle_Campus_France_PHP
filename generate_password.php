<?php
$password = 'AdminD123@'; // Remplace par ton nouveau mot de passe
$hashed_password = password_hash($password, PASSWORD_BCRYPT);
echo "Hash généré : " . $hashed_password;
?>
