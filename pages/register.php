<?php
// Vérifier si l'utilisateur est déjà connecté
if (is_logged_in()) {
    redirect_by_role();
}
?>

<div class="bg-gray-light py-12">
  <div class="container mx-auto px-4">
      <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-8">
          <h1 class="text-2xl font-bold text-primary mb-6 text-center">Inscription</h1>
          
          <?php
          // Traitement du formulaire d'inscription
          if ($_SERVER['REQUEST_METHOD'] === 'POST') {
              // Vérification du token CSRF
              if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                  set_message('Erreur de sécurité. Veuillez réessayer.', 'error');
              } else {
                  $name = clean_input($_POST['name']);
                  $email = clean_input($_POST['email']);
                  $password = $_POST['password'];
                  $password_confirm = $_POST['password_confirm'];
                  
                  // Validation des champs
                  $errors = [];
                  
                  if (empty($name)) {
                      $errors[] = 'Le nom est requis.';
                  }
                  
                  if (empty($email)) {
                      $errors[] = 'L\'email est requis.';
                  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                      $errors[] = 'L\'email n\'est pas valide.';
                  } elseif (email_exists($email)) {
                      $errors[] = 'Cet email est déjà utilisé.';
                  }
                  
                  if (empty($password)) {
                      $errors[] = 'Le mot de passe est requis.';
                  } elseif (strlen($password) < 8) {
                      $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
                  }
                  
                  if ($password !== $password_confirm) {
                      $errors[] = 'Les mots de passe ne correspondent pas.';
                  }
                  
                  if (!isset($_POST['terms']) || $_POST['terms'] != 'on') {
                      $errors[] = 'Vous devez accepter les conditions d\'utilisation.';
                  }
                  
                  if (empty($errors)) {
                      // Création de l'utilisateur
                      if (create_user($name, $email, $password, 'etudiant')) {
                          // Inscription réussie
                          set_message('Inscription réussie. Vous pouvez maintenant vous connecter.', 'success');
                          redirect('index.php?page=login');
                      } else {
                          set_message('Une erreur est survenue. Veuillez réessayer.', 'error');
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
          
          <form method="POST" action="index.php?page=register" class="space-y-4">
              <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
              
              <div>
                  <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                  <input type="text" id="name" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" required>
              </div>
              
              <div>
                  <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                  <input type="email" id="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" required>
              </div>
              
              <div>
                  <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                  <input type="password" id="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" required>
                  <p class="text-xs text-gray-500 mt-1">Le mot de passe doit contenir au moins 8 caractères.</p>
              </div>
              
              <div>
                  <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
                  <input type="password" id="password_confirm" name="password_confirm" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" required>
              </div>
              
              <div class="flex items-center">
                  <input type="checkbox" id="terms" name="terms" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" required>
                  <label for="terms" class="ml-2 block text-sm text-gray-700">J'accepte les <a href="#" class="text-primary hover:underline">conditions d'utilisation</a> et la <a href="#" class="text-primary hover:underline">politique de confidentialité</a>.</label>
              </div>
              
              <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white py-2 px-4 rounded-md">S'inscrire</button>
          </form>
          
          <div class="mt-6 text-center">
              <p class="text-sm text-gray-700">Vous avez déjà un compte ? <a href="index.php?page=login" class="text-primary hover:underline">Se connecter</a></p>
          </div>
      </div>
  </div>
</div>

