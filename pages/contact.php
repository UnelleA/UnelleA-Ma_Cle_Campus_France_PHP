<!-- <div class="mt-[120px]"></div> -->
<div class="bg-gray-light py-12">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-primary mb-4">Contactez-nous</h1>
            <p class="text-xl text-gray-700 max-w-3xl mx-auto">Souhaitez-vous faire accompagner dans votre procédure ? N'hésitez pas à nous contacter.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <!-- Formulaire de contact -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-primary mb-6">Envoyez-nous un message</h2>
                
                <?php
                // Traitement du formulaire de contact
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    // Vérification du token CSRF
                    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                        set_message('Erreur de sécurité. Veuillez réessayer.', 'error');
                    } else {
                        $name = clean_input($_POST['name']);
                        $email = clean_input($_POST['email']);
                        $subject = clean_input($_POST['subject']);
                        $message = clean_input($_POST['message']);
                        
                        // Validation des champs
                        $errors = [];
                        
                        if (empty($name)) {
                            $errors[] = 'Le nom est requis.';
                        }
                        
                        if (empty($email)) {
                            $errors[] = 'L\'email est requis.';
                        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = 'L\'email n\'est pas valide.';
                        }
                        
                        if (empty($subject)) {
                            $errors[] = 'Le sujet est requis.';
                        }
                        
                        if (empty($message)) {
                            $errors[] = 'Le message est requis.';
                        }
                        
                        if (empty($errors)) {
                            // Enregistrement du message dans la base de données
                            $stmt = $pdo->prepare("INSERT INTO messages (name, email, subject, message, created_at) VALUES (:name, :email, :subject, :message, NOW())");
                            $stmt->bindParam(':name', $name);
                            $stmt->bindParam(':email', $email);
                            $stmt->bindParam(':subject', $subject);
                            $stmt->bindParam(':message', $message);
                            
                            if ($stmt->execute()) {
                                // Message envoyé avec succès
                                set_message('Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.', 'success');
                                redirect('index.php?page=contact');
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
                
                // Génération d'un nouveau token CSRF
                $csrf_token = generate_csrf_token();
                ?>
                
                <form method="POST" action="index.php?page=contact" class="space-y-4">
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
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Sujet</label>
                        <select id="subject" name="subject" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" required>
                            <option value="">Sélectionnez un sujet</option>
                            <option value="Pré-candidature">Pré-candidature</option>
                            <option value="Procédure Campus France">Procédure Campus France</option>
                            <option value="Visa étudiant">Visa étudiant</option>
                            <option value="Installation en France">Installation en France</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea id="message" name="message" rows="5" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" required></textarea>
                    </div>
                    
                    <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white py-2 px-4 rounded-md">Envoyer le message</button>
                </form>
            </div>
            
            <!-- Informations de contact -->
            <div>
                <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
                    <h2 class="text-2xl font-bold text-primary mb-6">Nos coordonnées</h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="bg-primary-light p-3 rounded-full mr-4">
                                <i class="fas fa-map-marker-alt text-primary"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold">Adresse</h3>
                                <p class="text-gray-700">123 Avenue de France, 75013 Paris, France</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="bg-primary-light p-3 rounded-full mr-4">
                                <i class="fas fa-envelope text-primary"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold">Email</h3>
                                <p class="text-gray-700">contact@macampusfrance.com</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="bg-primary-light p-3 rounded-full mr-4">
                                <i class="fas fa-phone text-primary"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold">Téléphone</h3>
                                <p class="text-gray-700">+33 1 23 45 67 89</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="bg-primary-light p-3 rounded-full mr-4">
                                <i class="fas fa-clock text-primary"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold">Horaires d'ouverture</h3>
                                <p class="text-gray-700">Lundi - Vendredi: 9h00 - 18h00</p>
                                <p class="text-gray-700">Samedi: 10h00 - 14h00</p>
                                <p class="text-gray-700">Dimanche: Fermé</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-primary mb-6">Suivez-nous</h2>
                    
                    <div class="flex space-x-4">
                        <a href="#" class="bg-primary-light p-3 rounded-full text-primary hover:bg-primary hover:text-white transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="bg-primary-light p-3 rounded-full text-primary hover:bg-primary hover:text-white transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="bg-primary-light p-3 rounded-full text-primary hover:bg-primary hover:text-white transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="bg-primary-light p-3 rounded-full text-primary hover:bg-primary hover:text-white transition-colors">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="bg-primary-light p-3 rounded-full text-primary hover:bg-primary hover:text-white transition-colors">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Google Map -->
        <div class="mt-12">
            <div class="bg-white rounded-lg shadow-lg p-4">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2625.9519875881!2d2.3744514!3d48.8583698!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e67201d4220cfd%3A0x10790f97c7ca5289!2s123%20Av.%20de%20France%2C%2075013%20Paris%2C%20France!5e0!3m2!1sfr!2sfr!4v1620000000000!5m2!1sfr!2sfr" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </div>
</div>

