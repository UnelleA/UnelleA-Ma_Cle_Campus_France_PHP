<?php
// Vérifier si l'utilisateur est déjà connecté
if (is_logged_in()) {
    // Afficher un message de connexion réussie pour l'utilisateur connecté
    $role = $_SESSION['role']; // Vérifier le rôle de l'utilisateur (admin ou étudiant)
    set_message('Connexion réussie. Bienvenue ' . ($_SESSION['user_name'] ?? 'Utilisateur') . ' !', 'success');
    redirect_by_role(); // Redirige vers le dashboard pour admin, forum pour étudiant
}
?>

<div class="mt-[120px]"></div>
<div class="bg-gray-light py-12">
  <div class="container mx-auto px-4">
      <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-8">
          <h1 class="text-2xl font-bold text-primary mb-6 text-center">Connexion</h1>
          
          <?php
          // Traitement du formulaire de connexion
          if ($_SERVER['REQUEST_METHOD'] === 'POST') {
              // Vérification du token CSRF
              if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                  set_message('Erreur de sécurité. Veuillez réessayer.', 'error');
              } else {
                  // Nettoyage des entrées utilisateur
                  $email = isset($_POST['email']) ? clean_input($_POST['email']) : '';
                  $password = isset($_POST['password']) ? $_POST['password'] : '';
                  
                  // Validation des champs
                  $errors = [];
                  
                  if (empty($email)) {
                      $errors[] = 'L\'email est requis.';
                  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                      $errors[] = 'L\'email n\'est pas valide.';
                  }
                  
                  if (empty($password)) {
                      $errors[] = 'Le mot de passe est requis.';
                  }
                  
                  if (empty($errors)) {
                      // Authentification de l'utilisateur
                      if (authenticate_user($email, $password)) {
                          // Connexion réussie, redirection en fonction du rôle
                          set_message('Connexion réussie. Bienvenue ' . $_SESSION['user_name'] . ' !', 'success');
                          redirect_by_role(); // Redirige vers le dashboard pour admin, forum pour étudiant
                      } else {
                          set_message('Email ou mot de passe incorrect.', 'error');
                      }
                  } else {
                      // Affichage des erreurs
                      $error_message = implode('<br>', $errors);
                      set_message($error_message, 'error');
                  }
              }
          }

          // Affichage des messages
          echo display_message();
          
          // Génération d'un nouveau token CSRF
          $csrf_token = generate_csrf_token();
          ?>

          <form method="POST" action="index.php?page=login" class="space-y-4">
              <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
              
              <div>
                  <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                  <input type="email" id="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" required>
              </div>
              
              <div>
                  <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                  <input type="password" id="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" required>
              </div>
              
              <div class="flex items-center justify-between">
                  <div class="flex items-center">
                      <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                      <label for="remember" class="ml-2 block text-sm text-gray-700">Se souvenir de moi</label>
                  </div>
                  <a href="#" class="text-sm text-primary hover:underline">Mot de passe oublié ?</a>
              </div>
              
              <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white py-2 px-4 rounded-md">Se connecter</button>
          </form>
          
          <div class="mt-6 text-center">
              <p class="text-sm text-gray-700">Vous n'avez pas de compte ? <a href="index.php?page=register" class="text-primary hover:underline">S'inscrire</a></p>
          </div>
      </div>
  </div>
</div>
