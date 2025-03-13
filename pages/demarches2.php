<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Clé Campus France - Accompagnement des étudiants internationaux</title>
    <meta name="description" content="Plateforme d'accompagnement des étudiants internationaux pour leurs études en France. Guide complet pour la procédure Campus France, l'installation et les démarches administratives.">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0163CB',
                        'primary-light': '#A0C4FF',
                        'primary-hover': '#014c9d',
                        'gray-light': '#E0E7FF',
                        'gray-dark': '#545E66',
                    },
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- <link rel="stylesheet" href="/css/style.css"> -->
    <link rel="stylesheet" href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/public/css/style.css">


    <!-- <script src="https://cdn.tailwindcss.com"></script> -->

    <script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.4.1.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="flex flex-col min-h-screen bg-white">
    <div class="fixed top-0 left-0 w-full z-50">

        <!-- Top Navigation -->
        <div class="bg-primary text-white py-2">
            <div class="container mx-auto px-4 flex justify-between items-center">
                <div class="flex items-center space-x-4">
                
                    <a href="index.php" class="font-bold text-xl">
                    <img src="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/public/images/Blanc_logo_MCCF.svg" alt="Logo" class="h-14">
    
                    <!-- <img src="/public/images/logo_MCCF.svg" alt="Logo" class="h-14"> -->
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if (is_logged_in()): ?>
                        <a href="index.php?page=profile" class="hover:text-primary-light">
                            <i class="fas fa-user mr-1"></i> Mon profil
                        </a>
                        <a href="index.php?page=logout" class="hover:text-primary-light">
                            <i class="fas fa-sign-out-alt mr-1"></i> Déconnexion
                        </a>
                    <?php else: ?>
                        <a href="index.php?page=login" class="hover:text-primary-light">
                            <i class="fas fa-user-plus mr-1"></i> Connexion
                        </a>
                        <!-- <a href="index.php?page=register" class="hover:text-primary-light">
                            <i class="fas fa-user-plus mr-1"></i> Inscription
                        </a> -->
                    <?php endif; ?>
                    <a href="index.php?page=forum" class="hover:text-primary-light">
                        <i class="fas fa-comments mr-1"></i> Forum
                    </a>
                    <div class="relative group">
                        <button class="flex items-center hover:text-primary-light">
                            <i class="fas fa-globe mr-1"></i> FR <i class="fas fa-chevron-down ml-1"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-24 bg-white rounded-md shadow-lg hidden group-hover:block z-10">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">FR</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">EN</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Navigation -->
        <nav class="bg-white shadow-md">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center py-4">
                    <div class="hidden md:flex space-x-6">
                    <a href="index.php" 
                        class="relative group text-gray-800 hover:text-primary font-medium 
                        <?php echo ($current_page == 'index') ? 'border-b-2 border-primary' : ''; ?>">
                        Accueil
                        <span class="absolute left-0 bottom-0 w-0 h-1 bg-primary transition-all duration-300 group-hover:w-full"></span>
                    </a>
                        
                        <a href="index.php?page=pre-candidature" class="relative group text-gray-800 hover:text-primary font-medium 
                        <?php echo (isset($_GET['page']) && $_GET['page'] == 'pre-candidature') ? 'border-b-2 border-primary' : ''; ?>">
                            Pré-candidature
                            <span class="absolute left-0 bottom-0 w-0 h-1 bg-primary transition-all duration-300 group-hover:w-full"></span>
                        </a>
                        
                        <a href="index.php?page=demarches" class="relative group text-gray-800 hover:text-primary font-medium 
                        <?php echo (isset($_GET['page']) && $_GET['page'] == 'demarches') ? 'border-b-2 border-primary' : ''; ?>">
                            Démarches
                            <span class="absolute left-0 bottom-0 w-0 h-1 bg-primary transition-all duration-300 group-hover:w-full"></span>
                        </a>

                        <a href="index.php?page=installation" class="relative group text-gray-800 hover:text-primary font-medium 
                        <?php echo (isset($_GET['page']) && $_GET['page'] == 'installation') ? 'border-b-2 border-primary' : ''; ?>">
                            Installation en France
                            <span class="absolute left-0 bottom-0 w-0 h-1 bg-primary transition-all duration-300 group-hover:w-full"></span>
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative hidden md:block">
                            <input type="text" placeholder="Rechercher..." class="pl-10 pr-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:border-primary">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <a href="index.php?page=contact" class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-full hidden md:block shadow-md transition duration-300 transform hover:scale-105 hover:bg-[#014c9d]">
                            Demander un accompagnement
                        </a>
                        <button class="md:hidden text-gray-800" id="mobile-menu-button">
                            <i class="fas fa-bars text-2xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Mobile Menu -->
                <div class="md:hidden hidden" id="mobile-menu">
                    <div class="flex flex-col space-y-3 py-4">
                        <a href="index.php" class="relative group text-gray-800 hover:text-primary font-medium 
                        <?php echo ($_SERVER['PHP_SELF'] == "/index.php") ? 'border-b-2 border-primary' : ''; ?>">
                            Accueil
                            <span class="absolute left-0 bottom-0 w-0 h-1 bg-primary transition-all duration-300 group-hover:w-full"></span>
                        </a>
                        
                        <a href="index.php?page=pre-candidature" class="relative group text-gray-800 hover:text-primary font-medium 
                        <?php echo (isset($_GET['page']) && $_GET['page'] == 'pre-candidature') ? 'border-b-2 border-primary' : ''; ?>">
                            Pré-candidature
                            <span class="absolute left-0 bottom-0 w-0 h-1 bg-primary transition-all duration-300 group-hover:w-full"></span>
                        </a>
                        
                        <a href="index.php?page=demarches" class="relative group text-gray-800 hover:text-primary font-medium 
                        <?php echo (isset($_GET['page']) && $_GET['page'] == 'demarches') ? 'border-b-2 border-primary' : ''; ?>">
                            Démarches
                            <span class="absolute left-0 bottom-0 w-0 h-1 bg-primary transition-all duration-300 group-hover:w-full"></span>
                        </a>

                        <a href="index.php?page=installation" class="relative group text-gray-800 hover:text-primary font-medium 
                        <?php echo (isset($_GET['page']) && $_GET['page'] == 'installation') ? 'border-b-2 border-primary' : ''; ?>">
                            Installation en France
                            <span class="absolute left-0 bottom-0 w-0 h-1 bg-primary transition-all duration-300 group-hover:w-full"></span>
                        </a>

                        <div class="relative">
                            <input type="text" placeholder="Rechercher..." class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:border-primary">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <a href="index.php?page=contact" class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-full text-center">
                            Demander un accompagnement
                        </a>
                    </div>
                </div>
            </div>
        </nav>

    </div>
    
    <!-- Main Content -->
    <main class="flex-grow">
        <?php echo display_message(); ?>

