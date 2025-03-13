</main>
        
        <!-- Footer -->
        <footer class="bg-gray-800 text-white py-8 mt-12">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- Logo et description -->
                    <div class="md:col-span-1">
                        <a href="index.php" class="inline-block mb-4">
                            <img src="/public/images/Blanc_logo_MCCF.svg" alt="Logo" class="h-14">
                        </a>
                        <p class="text-gray-400 text-sm">
                            Ma Clé Campus France vous accompagne dans toutes vos démarches pour étudier en France.
                        </p>
                    </div>
                    
                    <!-- Liens rapides -->
                    <div class="md:col-span-1">
                        <h3 class="text-lg font-semibold mb-4">Liens rapides</h3>
                        <ul class="space-y-2">
                            <li><a href="index.php" class="text-gray-400 hover:text-white transition">Accueil</a></li>
                            <li><a href="index.php?page=pre-candidature" class="text-gray-400 hover:text-white transition">Pré-candidature</a></li>
                            <li><a href="index.php?page=demarches" class="text-gray-400 hover:text-white transition">Démarches</a></li>
                            <li><a href="index.php?page=installation" class="text-gray-400 hover:text-white transition">Installation</a></li>
                            <li><a href="index.php?page=forum" class="text-gray-400 hover:text-white transition">Forum</a></li>
                        </ul>
                    </div>
                    
                    <!-- Contact -->
                    <div class="md:col-span-1">
                        <h3 class="text-lg font-semibold mb-4">Contact</h3>
                        <ul class="space-y-2">
                            <li class="flex items-start">
                                <i class="fas fa-map-marker-alt mt-1 mr-2 text-primary-light"></i>
                                <span class="text-gray-400">123 Avenue des Études, 75001 Paris, France</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-phone mr-2 text-primary-light"></i>
                                <a href="tel:+33123456789" class="text-gray-400 hover:text-white transition">+33 1 23 45 67 89</a>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-envelope mr-2 text-primary-light"></i>
                                <a href="mailto:contact@maclecampusfrance.fr" class="text-gray-400 hover:text-white transition">contact@maclecampusfrance.fr</a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Newsletter -->
                    <div class="md:col-span-1">
                        <h3 class="text-lg font-semibold mb-4">Restez informé</h3>
                        <p class="text-gray-400 text-sm mb-4">
                            Inscrivez-vous à notre newsletter pour recevoir les dernières actualités.
                        </p>
                        <form class="space-y-2">
                            <div class="relative">
                                <input type="email" placeholder="Votre email" class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-primary">
                            </div>
                            <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white py-2 px-4 rounded-md transition">
                                S'abonner
                            </button>
                        </form>
                    </div>
                </div>
                
                <hr class="border-gray-700 my-6">
                
                <!-- Bas de page -->
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-400 text-sm mb-4 md:mb-0">
                        &copy; <?php echo date('Y'); ?> Ma Clé Campus France. Tous droits réservés.
                    </p>
                    
                    <!-- Réseaux sociaux -->
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
            </div>
        </footer>
        
        <!-- Script pour le menu mobile -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const mobileMenuButton = document.getElementById('mobile-menu-button');
                const mobileMenu = document.getElementById('mobile-menu');
                
                if (mobileMenuButton && mobileMenu) {
                    mobileMenuButton.addEventListener('click', function() {
                        mobileMenu.classList.toggle('hidden');
                    });
                    
                    // Fermer le menu mobile en cliquant en dehors
                    document.addEventListener('click', function(event) {
                        if (!mobileMenuButton.contains(event.target) && !mobileMenu.contains(event.target) && !mobileMenu.classList.contains('hidden')) {
                            mobileMenu.classList.add('hidden');
                        }
                    });
                }
            });
        </script>
        

    </body>
</html>
