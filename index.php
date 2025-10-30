<?php
// index.php - Landing Page
require_once 'includes/auth.php';

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BK DRAMA - Discover Amazing Stories</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #1e293b 100%);
            background-attachment: fixed;
        }

        /* Animated gradient background */
        @keyframes gradient-shift {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        .animated-bg {
            background: linear-gradient(-45deg, #1e293b, #1e1b4b, #312e81, #1e3a8a);
            background-size: 400% 400%;
            animation: gradient-shift 15s ease infinite;
        }

        /* Glassmorphism effect */
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Poster hover effect */
        .poster-container {
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
            transition: transform 0.3s ease;
        }

        .poster-container:hover {
            transform: scale(1.05);
        }

        .poster-img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.5s ease;
        }

        .poster-container:hover .poster-img {
            transform: scale(1.1);
        }

        .poster-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, transparent 50%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .poster-container:hover .poster-overlay {
            opacity: 1;
        }

        /* Button glow effect */
        .btn-glow {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .btn-glow::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-glow:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-glow:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(234, 179, 8, 0.4);
        }

        /* Floating animation */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .float-animation {
            animation: float 3s ease-in-out infinite;
        }

        /* Shimmer effect */
        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }

            100% {
                background-position: 1000px 0;
            }
        }

        .shimmer {
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            background-size: 1000px 100%;
            animation: shimmer 2s infinite;
        }

        /* Genre button effects */
        .genre-btn {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .genre-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.4s, height 0.4s;
        }

        .genre-btn:hover::after {
            width: 300px;
            height: 300px;
        }

        .genre-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 25px rgba(234, 179, 8, 0.5);
        }

        /* Icon pulse animation */
        @keyframes pulse-ring {
            0% {
                transform: scale(0.9);
                opacity: 1;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.7;
            }

            100% {
                transform: scale(0.9);
                opacity: 1;
            }
        }

        .icon-pulse:hover {
            animation: pulse-ring 1s ease-in-out infinite;
        }

        /* Fade in animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        /* Text gradient */
        .text-gradient {
            background: linear-gradient(90deg, #fbbf24, #f59e0b, #fbbf24);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shimmer 3s linear infinite;
        }

        /* Particles background */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: rgba(234, 179, 8, 0.5);
            border-radius: 50%;
            animation: float-particle 10s infinite;
        }

        @keyframes float-particle {

            0%,
            100% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            100% {
                transform: translateY(-100vh) translateX(50px);
                opacity: 0;
            }
        }

        /* Scroll reveal */
        .scroll-reveal {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.8s ease;
        }

        .scroll-reveal.active {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>

<body class="animated-bg text-white min-h-screen overflow-x-hidden">

    <!-- Animated particles -->
    <div class="particles" id="particles"></div>

    <!-- Header with glassmorphism -->
    <header class="glass sticky top-0 z-50 backdrop-blur-xl shadow-2xl">
        <div class="container mx-auto px-4 py-4">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center space-x-6">
                    <h1 class="text-2xl font-black tracking-wider text-gradient">BK DRAMA</h1>
                    <!-- <nav class="hidden md:flex space-x-6 text-sm font-medium">
                        <a href="#" class="relative group">
                            <span class="hover:text-yellow-400 transition">Home</span>
                            <span
                                class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-400 group-hover:w-full transition-all duration-300"></span>
                        </a>
                        <a href="#" class="relative group">
                            <span class="hover:text-yellow-400 transition">All Dramas</span>
                            <span
                                class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-400 group-hover:w-full transition-all duration-300"></span>
                        </a>
                        <a href="#" class="relative group">
                            <span class="hover:text-yellow-400 transition">Trending</span>
                            <span
                                class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-400 group-hover:w-full transition-all duration-300"></span>
                        </a>
                    </nav> -->
                </div>

                <div class="flex items-center space-x-3">
                    <a href="./login.php"
                        class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-black px-5 py-2 text-sm font-bold rounded-lg btn-glow">
                        Login
                    </a>
                    <a href="./register.php"
                        class="bg-gradient-to-r from-yellow-400 to-yellow-500 text-black px-5 py-2 text-sm font-bold rounded-lg btn-glow">
                        Daftar
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8 md:py-12 relative z-10">
        <!-- Hero Section -->
        <div class="glass rounded-3xl p-6 md:p-10 shadow-2xl scroll-reveal mb-12 overflow-hidden relative">
            <div class="absolute inset-0 shimmer opacity-30"></div>

            <div class="flex flex-col md:flex-row gap-8 items-center relative z-10">
                <!-- Poster with effects -->
                <div class="md:w-2/5 float-animation">
                    <div class="poster-container shadow-2xl">
                        <img src="https://asianwiki.com/images/3/3c/Move_To_Heaven-mp1.jpeg"
                            alt="Poster drama Move to Heaven" class="poster-img" />
                        <div class="poster-overlay flex items-end p-6">
                            <div class="text-white">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    <span class="font-bold">9.1/10</span>
                                </div>
                                <p class="text-sm opacity-90">2021 • 10 Episodes</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="md:w-3/5 text-center md:text-left fade-in-up">
                    <div
                        class="inline-block px-4 py-1 bg-yellow-500/20 text-yellow-400 rounded-full text-xs font-bold mb-4">
                        ⭐ FEATURED DRAMA
                    </div>
                    <h2 class="text-4xl md:text-5xl font-black mb-4 tracking-tight leading-tight">
                        MOVE TO <span class="text-gradient">HEAVEN</span>
                    </h2>
                    <div class="flex flex-wrap gap-2 justify-center md:justify-start mb-6">
                        <span
                            class="px-3 py-1 bg-blue-500/20 text-blue-300 rounded-full text-xs font-semibold">Drama</span>
                        <span
                            class="px-3 py-1 bg-purple-500/20 text-purple-300 rounded-full text-xs font-semibold">Family</span>
                        <span
                            class="px-3 py-1 bg-pink-500/20 text-pink-300 rounded-full text-xs font-semibold">Emotional</span>
                    </div>
                    <p class="text-base md:text-lg leading-relaxed text-gray-300 mb-6">
                        Ikuti perjalanan Han Geu-ru, seorang pemuda dengan sindrom Asperger, yang mewarisi bisnis
                        "trauma cleaner" dari ayahnya. Bersama pamannya Cho Sang-gu, mantan narapidana, mereka
                        membersihkan rumah orang yang telah meninggal dan mengungkap cerita tersembunyi di balik setiap
                        kehidupan. Drama yang menyentuh hati tentang keluarga, kehilangan, dan menemukan makna dalam
                        setiap kehidupan.
                    </p>
                    <!-- <div class="flex flex-wrap gap-4 justify-center md:justify-start">
                        <button
                            class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-black px-8 py-3 rounded-lg font-bold text-sm flex items-center gap-2 btn-glow">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" />
                            </svg>
                            Watch Now
                        </button>
                        <button
                            class="glass px-8 py-3 rounded-lg font-bold text-sm flex items-center gap-2 hover:bg-white/10 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            My List
                        </button>
                    </div> -->
                </div>
            </div>
        </div>

        <!-- Browse by Genre -->
        <!-- <div class="scroll-reveal">
            <h3 class="text-2xl md:text-3xl font-bold mb-6 text-center">
                Browse by <span class="text-gradient">Genre</span>
            </h3>
            <div class="flex flex-wrap justify-center gap-4">
                <button
                    class=" bg-gradient-to-r from-purple-500 to-purple-600 px-8 py-3 rounded-xl font-bold text-sm shadow-lg">
                    ✨ FANTASI
                </button>
                <button
                    class=" bg-gradient-to-r from-pink-500 to-rose-600 px-8 py-3 rounded-xl font-bold text-sm shadow-lg">
                    💕 ROMANCE
                </button>
                <button
                    class=" bg-gradient-to-r from-red-600 to-red-700 px-8 py-3 rounded-xl font-bold text-sm shadow-lg">
                    👻 HOROR
                </button>
                <button
                    class=" bg-gradient-to-r from-gray-700 to-gray-800 px-8 py-3 rounded-xl font-bold text-sm shadow-lg">
                    🔪 THRILLER
                </button>
                <button
                    class=" bg-gradient-to-r from-yellow-500 to-yellow-600 px-8 py-3 rounded-xl font-bold text-sm shadow-lg">
                    😂 KOMEDI
                </button>
            </div>
        </div> -->

        <!-- Quick Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-12 scroll-reveal">
            <div class="glass p-6 rounded-2xl text-center hover:scale-105 transition">
                <div class="text-3xl font-black text-gradient mb-2">500+</div>
                <div class="text-sm text-gray-400">K-Dramas</div>
            </div>
            <div class="glass p-6 rounded-2xl text-center hover:scale-105 transition">
                <div class="text-3xl font-black text-gradient mb-2">1M+</div>
                <div class="text-sm text-gray-400">Active Users</div>
            </div>
            <div class="glass p-6 rounded-2xl text-center hover:scale-105 transition">
                <div class="text-3xl font-black text-gradient mb-2">4.8★</div>
                <div class="text-sm text-gray-400">Average Rating</div>
            </div>
            <div class="glass p-6 rounded-2xl text-center hover:scale-105 transition">
                <div class="text-3xl font-black text-gradient mb-2">24/7</div>
                <div class="text-sm text-gray-400">Streaming</div>
            </div>
        </div>
    </main>

    <script>
        // Create animated particles
        const particlesContainer = document.getElementById('particles');
        for (let i = 0; i < 30; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 10 + 's';
            particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
            particlesContainer.appendChild(particle);
        }

        // Scroll reveal animation
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.scroll-reveal').forEach(el => {
            observer.observe(el);
        });

        // Add ripple effect on buttons
        document.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', function (e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');

                this.appendChild(ripple);

                setTimeout(() => ripple.remove(), 600);
            });
        });
    </script>
</body>

</html>