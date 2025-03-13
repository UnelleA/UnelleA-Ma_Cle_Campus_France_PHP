
<!-- Espacement pour le contenu principal -->
<!-- <div class="mt-[px]"></div> -->
<div class="bg-gray-light py-12">
    <div class="container mx-auto px-4">
        <!-- <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-primary mb-4">Bienvenue sur Ma Clé Campus France</h1>
            <p class="text-xl text-gray-700 max-w-3xl mx-auto">Votre guide complet pour réussir vos études en France. Nous vous accompagnons à chaque étape de votre projet d'études.</p>
        </div> -->
        

<!-- Section Carousel -->
<section class="relative">
    <div class="sliderAx h-auto relative">
        <!-- Slide 1 -->
        <div id="slider-1" class="carousel-slide active-slide container items-center max-w-7xl mx-auto px-6 py-20">
            <div class="flex flex-wrap items-center">
                <div class="w-full md:w-1/2">
                    <div class="overflow-hidden rounded-lg shadow-lg">
                        <img src="/public/images/carousel1.jpg" alt="Étudiant heureux" class="w-full object-cover">
                    </div>
                </div>
                <div class="w-full md:w-1/2 md:pl-10 mt-6 md:mt-0">
                    <h1 class="text-4xl font-bold text-gray-800 md:text-5xl">
                        Découvrez <span class="text-blue-600">votre avenir</span> avec <br> <span class="text-primary">Ma Clé Campus France</span>
                    </h1>
                    <p class="mt-6 text-lg text-gray-600">
                        Accédez à une multitude de ressources pour faciliter vos démarches et réussir vos études en France.
                    </p>
                    <a href="index.php?page=contact" class="custom-button">Contactez-nous</a>
                </div>
            </div>
        </div>

        <!-- Slide 2 -->
        <div id="slider-2" class="carousel-slide container items-center max-w-7xl mx-auto px-6 py-16 hidden">
            <div class="flex flex-wrap items-center">
                <div class="w-full md:w-1/2">
                    <div class="overflow-hidden rounded-lg shadow-lg">
                        <img src="/public/images/carousel4.jpg" alt="Étudiant heureux" class="w-full object-cover">
                    </div>
                </div>
                <div class="w-full md:w-1/2 md:pl-10">
                    <h1 class="text-4xl font-bold text-gray-800 md:text-5xl">
                        Une <span class="text-blue-600">plateforme complète</span> pour <br> <span class="text-primary">réaliser vos projets</span>
                    </h1>
                    <p class="mt-6 text-lg text-gray-600">
                        Obtenez des conseils, des guides et un accompagnement personnalisé tout au long de vos démarches.
                    </p>
                    <a href="index.php?page=contact" class="custom-button">Forum d'aide</a>
                </div>
            </div>
        </div>

        <!-- Slide 3 -->
        <div id="slider-3" class="carousel-slide container items-center max-w-7xl mx-auto px-6 py-20 hidden">
            <div class="flex flex-wrap items-center">
                <div class="w-full md:w-1/2">
                    <div class="overflow-hidden rounded-lg shadow-lg">
                        <img src="/public/images/carousel3.jpg" alt="Étudiant heureux" class="w-full object-cover">
                    </div>
                </div>
                <div class="w-full md:w-1/2 md:pl-10 mt-6 md:mt-0">
                    <h1 class="text-4xl font-bold text-gray-800 md:text-5xl">
                        Préparer votre dossier <span class="text-blue-600">avec</span><br> <span class="text-primary">Ma Clé Campus France</span>
                    </h1>
                    <p class="mt-6 text-lg text-gray-600">
                        Nous vous aidons à préparer tous les documents nécessaires pour votre candidature Campus France et votre demande de visa.
                    </p>
                    <a href="index.php?page=contact" class="custom-button">Contactez-nous</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Boutons de contrôle -->
    <div class="flex justify-center space-x-4 mt-6">
        <button id="sButton1" onclick="sliderButton(0)" class="w-3 h-3 bg-blue-500 rounded-full"></button>
        <button id="sButton2" onclick="sliderButton(1)" class="w-3 h-3 bg-gray-400 rounded-full"></button>
        <button id="sButton3" onclick="sliderButton(2)" class="w-3 h-3 bg-gray-400 rounded-full"></button>
    </div>
</section>
<div class="mt-[120px]"></div>
<!-- Section Nos Services -->
<section class="bg-white py-20">
    <div class="container mx-auto px-6">
        <h2 class="text-3xl font-bold text-center text-gray-800">
            Nos Services
        </h2>
        <p class="text-center text-gray-600 mt-4">
            Nous accompagnons les étudiants à chaque étape de leur parcours en France.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mt-12">
            <?php
            // Tableau des services avec les couleurs du projet
            $services = [
                ['title' => 'Pré-candidature', 'desc' => 'Aide à la préparation et soumission de votre dossier.', 'color' => 'bg-[#0163CB]'],
                ['title' => 'Démarches', 'desc' => 'Guidance pour les formalités administratives et juridiques.', 'color' => 'bg-[#A0C4FF]'],
                ['title' => 'Installation en France', 'desc' => 'Conseils pour le logement et la vie étudiante.', 'color' => 'bg-[#545E66]']
            ];

            foreach ($services as $service):
            ?>
                <div class="bg-[#E0E7FF] p-8 rounded-lg shadow-lg text-center transform transition-transform duration-300 hover:scale-105">
                    <div class="<?php echo $service['color']; ?> text-white w-16 h-16 mx-auto rounded-full flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16l-4-4m0 0l4-4m-4 4h16" />
                        </svg>
                    </div>
                    <h3 class="mt-6 text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($service['title']); ?></h3>
                    <p class="mt-4 text-gray-600"><?php echo htmlspecialchars($service['desc']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div class="mt-[120px]"></div>
<!-- ======================== Section Chiffres Clés (Avec Animation) ======================== -->
<section class="bg-gray-100 py-20">
    <div class="container mx-auto px-6">
        <h2 class="text-3xl font-bold text-center text-gray-800">
            Nos Chiffres Clés
        </h2>
        <p class="text-center text-gray-600 mt-4">
            Découvrez notre impact en quelques chiffres.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mt-10 text-center">
            <div class="p-6 bg-white rounded-lg shadow-lg">
                <h3 class="text-4xl font-bold text-[#0163CB] counter" data-target="10000">0</h3>
                <p class="mt-2 text-gray-600">Étudiants accompagnés</p>
            </div>

            <div class="p-6 bg-white rounded-lg shadow-lg">
                <h3 class="text-4xl font-bold text-[#0163CB] counter" data-target="98">0</h3>
                <p class="mt-2 text-gray-600">De satisfaction</p>
            </div>

            <div class="p-6 bg-white rounded-lg shadow-lg">
                <h3 class="text-4xl font-bold text-[#0163CB] counter" data-target="50">0</h3>
                <p class="mt-2 text-gray-600">Partenaires et universités</p>
            </div>
        </div>
    </div>
</section>




<div class="mt-[120px]"></div>

<!-- Section Étudier en France -->
<section class="">
    <div class="relative flex flex-col items-center mx-auto lg:flex-row-reverse lg:max-w-5xl xl:max-w-6xl">
        <!-- Image Column -->
        <div class="w-full h-64 lg:w-1/2 lg:h-auto">
            <img class="h-full w-full object-cover rounded-lg shadow-lg" src="/public/images/etudier-en-france.jpg" alt="Étudier en France">
        </div>

        <!-- Text Column -->
        <div class="max-w-lg bg-white md:max-w-2xl md:z-10 md:shadow-lg md:absolute md:top-0 md:mt-48 lg:w-3/5 lg:left-0 lg:mt-20 lg:ml-20 xl:mt-24 xl:ml-12 p-12">
        <h2 class="text-3xl font-bold text-primary mb-4">Pourquoi choisir Ma Clé Campus France ?</h2>
        <p class="text-gray-700 mb-4">Nous sommes une équipe d'experts qui connaît parfaitement les procédures Campus France et les démarches administratives pour étudier en France.</p>
                <ul class="space-y-2">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-primary mt-1 mr-2"></i>
                        <span>Accompagnement personnalisé à chaque étape</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-primary mt-1 mr-2"></i>
                        <span>Conseils pour optimiser vos chances d'obtenir un visa</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-primary mt-1 mr-2"></i>
                        <span>Aide à la rédaction de CV et lettres de motivation</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-primary mt-1 mr-2"></i>
                        <span>Préparation à l'entretien Campus France</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-primary mt-1 mr-2"></i>
                        <span>Assistance pour trouver un logement en France</span>
                    </li>
                </ul>
            <div class="mt-8">
                <a href="index.php?page=contact" class="custom-button">Nous contacter</a>
            </div>
        </div>
    </div>
</section>

<div class="mt-[380px]"></div>
        <!-- Testimonials Section -->
        <div class="mb-16">
            <h2 class="text-3xl font-bold text-primary text-center mb-8">Ce que disent nos étudiants accompagnés</h2>
            
            <div class="relative" id="testimonials-carousel">
                <div class="carousel-container overflow-hidden">
                    <div class="carousel-track flex transition-transform duration-500" id="testimonials-track">
                        <!-- Testimonial 1 -->
                        <div class="carousel-item w-full md:w-1/2 flex-shrink-0 px-4">
                            <div class="bg-white rounded-lg shadow-lg p-6 h-full">
                                <div class="flex items-center mb-4">
                                    <img src="https://randomuser.me/api/portraits/women/32.jpg" alt="Sophie M." class="w-16 h-16 rounded-full mr-4">
                                    <div>
                                        <h4 class="font-bold">Sophie M.</h4>
                                        <p class="text-sm text-gray-600">Étudiante en Master à Paris</p>
                                    </div>
                                </div>
                                <p class="text-gray-700 italic">"Grâce à Ma Clé Campus France, j'ai pu obtenir mon visa étudiant sans difficulté. Leur accompagnement m'a été précieux pour préparer mon dossier et réussir mon entretien Campus France. Je les recommande vivement !"</p>
                                <div class="mt-4 text-primary">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Testimonial 2 -->
                        <div class="carousel-item w-full md:w-1/2 flex-shrink-0 px-4">
                            <div class="bg-white rounded-lg shadow-lg p-6 h-full">
                                <div class="flex items-center mb-4">
                                    <img src="https://randomuser.me/api/portraits/men/45.jpg" alt="Jean K." class="w-16 h-16 rounded-full mr-4">
                                    <div>
                                        <h4 class="font-bold">Jean K.</h4>
                                        <p class="text-sm text-gray-600">Étudiant en Licence à Lyon</p>
                                    </div>
                                </div>
                                <p class="text-gray-700 italic">"Le processus Campus France peut sembler compliqué, mais avec l'aide de Ma Clé Campus France, tout est devenu plus simple. Ils m'ont guidé pas à pas et ont répondu à toutes mes questions. Un grand merci à toute l'équipe !"</p>
                                <div class="mt-4 text-primary">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Testimonial 3 -->
                        <div class="carousel-item w-full md:w-1/2 flex-shrink-0 px-4">
                            <div class="bg-white rounded-lg shadow-lg p-6 h-full">
                                <div class="flex items-center mb-4">
                                    <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Amina T." class="w-16 h-16 rounded-full mr-4">
                                    <div>
                                        <h4 class="font-bold">Amina T.</h4>
                                        <p class="text-sm text-gray-600">Étudiante en Doctorat à Montpellier</p>
                                    </div>
                                </div>
                                <p class="text-gray-700 italic">"J'ai bénéficié des conseils de Ma Clé Campus France pour mon installation en France. Ils m'ont aidée à trouver un logement et à ouvrir un compte bancaire. Leur assistance a rendu mon arrivée en France beaucoup plus facile."</p>
                                <div class="mt-4 text-primary">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Carousel Controls -->
                <button class="absolute top-1/2 left-2 transform -translate-y-1/2 bg-white rounded-full p-2 shadow-md text-primary hover:text-primary-hover focus:outline-none" id="prev-testimonial">
                    <i class="fas fa-chevron-left text-xl"></i>
                </button>
                <button class="absolute top-1/2 right-2 transform -translate-y-1/2 bg-white rounded-full p-2 shadow-md text-primary hover:text-primary-hover focus:outline-none" id="next-testimonial">
                    <i class="fas fa-chevron-right text-xl"></i>
                </button>
                
                <!-- Carousel Indicators -->
                <div class="flex justify-center mt-6" id="testimonial-indicators">
                    <button class="h-3 w-3 rounded-full bg-primary mx-1 active" data-index="0"></button>
                    <button class="h-3 w-3 rounded-full bg-gray-300 mx-1" data-index="1"></button>
                    <button class="h-3 w-3 rounded-full bg-gray-300 mx-1" data-index="2"></button>
                </div>
            </div>
        </div>

        <div class="mt-[120px]"></div>
        <!-- CTA Section -->
        <div class="bg-primary text-white rounded-lg p-8 text-center">
            <h2 class="text-3xl font-bold mb-4">Prêt à commencer votre aventure en France ?</h2>
            <p class="text-xl mb-6 max-w-3xl mx-auto">Contactez-nous dès aujourd'hui pour bénéficier de notre accompagnement personnalisé et réaliser votre rêve d'étudier en France.</p>
            <a href="index.php?page=contact" class="inline-block bg-white text-primary hover:bg-gray-100 px-8 py-3 rounded-full font-bold">Demander un accompagnement</a>
        </div>
    </div>
</div>
<!-- <div class="mt-[120px]"></div> -->


<script>

// ======================== Script d'Animation des Chiffres ======================== 

    document.addEventListener("DOMContentLoaded", function () {
        const counters = document.querySelectorAll(".counter");
        const speed = 200; // Plus la valeur est basse, plus l'animation est rapide

        const animateCounters = () => {
            counters.forEach(counter => {
                const target = +counter.getAttribute("data-target");
                let count = 0;

                const updateCounter = () => {
                    count += Math.ceil(target / speed);
                    counter.innerText = Math.min(count, target); // Empêche de dépasser la cible

                    if (count < target) {
                        requestAnimationFrame(updateCounter); // Animation plus fluide
                    }
                };

                updateCounter();
            });
        };

        // Observer pour détecter l'entrée dans le viewport
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    observer.disconnect(); // Empêche de relancer l'animation plusieurs fois
                }
            });
        });

        counters.forEach(counter => {
            observer.observe(counter);
        });
    });


//    carousel
    var currentSlide = 0;
    var slides = document.querySelectorAll(".carousel-slide");
    var buttons = document.querySelectorAll("button[id^='sButton']");
    var slideInterval;

    function showSlide(index) {
        slides.forEach((slide, i) => {
            if (i === index) {
                $(slide).fadeIn(600).removeClass("hidden"); // Animation fluide + affichage
                buttons[i].classList.add("bg-blue-500");
                buttons[i].classList.remove("bg-gray-400");
            } else {
                $(slide).fadeOut(600, function() {
                    $(this).addClass("hidden"); // Masquer complètement après animation
                });
                buttons[i].classList.add("bg-gray-400");
                buttons[i].classList.remove("bg-blue-500");
            }
        });

        currentSlide = index;
    }

    function nextSlide() {
        var nextIndex = (currentSlide + 1) % slides.length;
        showSlide(nextIndex);
    }

    function sliderButton(index) {
        clearInterval(slideInterval); // Stopper le défilement auto après clic
        showSlide(index);
        startSlider(); // Redémarrer après interaction
    }

    function startSlider() {
        slideInterval = setInterval(nextSlide, 6000); // Changement toutes les 6s
    }

    $(document).ready(function () {
        slides.forEach((slide, i) => {
            if (i !== 0) $(slide).addClass("hidden"); // Masquer tous sauf le premier
        });

        showSlide(0);
        startSlider();
    });



    

    
    // Testimonials Carousel
    const testimonialsTrack = document.getElementById('testimonials-track');
    const testimonialItems = document.querySelectorAll('#testimonials-track .carousel-item');
    const testimonialIndicators = document.querySelectorAll('#testimonial-indicators button');
    let testimonialCurrentIndex = 0;
    const testimonialItemCount = testimonialItems.length;
    
    function moveTestimonialCarousel(index) {
        // Calculate the percentage to move based on the number of items visible
        const itemWidth = 100 / (window.innerWidth >= 768 ? 2 : 1);
        const translateValue = -index * itemWidth;
        
        testimonialsTrack.style.transform = `translateX(${translateValue}%)`;
        
        // Update indicators
        testimonialIndicators.forEach((indicator, i) => {
            if (i === index) {
                indicator.classList.add('bg-primary');
                indicator.classList.remove('bg-gray-300');
            } else {
                indicator.classList.remove('bg-primary');
                indicator.classList.add('bg-gray-300');
            }
        });
        
        testimonialCurrentIndex = index;
    }
    
    document.getElementById('prev-testimonial').addEventListener('click', () => {
        testimonialCurrentIndex = (testimonialCurrentIndex - 1 + testimonialItemCount) % testimonialItemCount;
        moveTestimonialCarousel(testimonialCurrentIndex);
    });
    
    document.getElementById('next-testimonial').addEventListener('click', () => {
        testimonialCurrentIndex = (testimonialCurrentIndex + 1) % testimonialItemCount;
        moveTestimonialCarousel(testimonialCurrentIndex);
    });
    
    testimonialIndicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => {
            moveTestimonialCarousel(index);
        });
    });
    
    // Auto-play carousels
    setInterval(() => {
        serviceCurrentIndex = (serviceCurrentIndex + 1) % serviceItemCount;
        moveServiceCarousel(serviceCurrentIndex);
    }, 5000);
    
    setInterval(() => {
        testimonialCurrentIndex = (testimonialCurrentIndex + 1) % testimonialItemCount;
        moveTestimonialCarousel(testimonialCurrentIndex);
    }, 6000);
    
    // Responsive adjustments
    window.addEventListener('resize', () => {
        moveServiceCarousel(serviceCurrentIndex);
        moveTestimonialCarousel(testimonialCurrentIndex);
    });
</script>

