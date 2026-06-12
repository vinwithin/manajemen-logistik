<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRZ Group - Sistem Manajemen Distribusi Pakan</title>

    <!-- SEO Meta Tags -->
    <meta name="description"
        content="Sistem Manajemen Distribusi Pakan Terintegrasi dengan GPS Tracking Real-time. Kelola Purchase Order, Gudang, Lansir, dan Pembayaran dengan mudah dan efisien.">
    <meta name="keywords"
        content="sistem distribusi pakan, manajemen gudang, GPS tracking, purchase order, lansir pakan, HRZ Group">
    <meta name="author" content="HRZ Group">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="HRZ Group - Sistem Manajemen Distribusi Pakan">
    <meta property="og:description"
        content="Sistem Manajemen Distribusi Pakan Terintegrasi dengan GPS Tracking Real-time. Kelola Purchase Order, Gudang, Lansir, dan Pembayaran dengan mudah dan efisien.">
    <meta property="og:site_name" content="HRZ Group">
    <meta property="og:locale" content="id_ID">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:url" content="{{ url('/') }}">
    <meta name="twitter:title" content="HRZ Group - Sistem Manajemen Distribusi Pakan">
    <meta name="twitter:description"
        content="Sistem Manajemen Distribusi Pakan Terintegrasi dengan GPS Tracking Real-time. Kelola Purchase Order, Gudang, Lansir, dan Pembayaran dengan mudah dan efisien.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #183447;
            --secondary-color: #1f8fb8;
            --accent-color: #d96c3b;
            --success-color: #3ca37a;
            --ink-color: #17212b;
            --muted-color: #64748b;
            --light-bg: #f4f8f9;
            --border-color: #dbe7ec;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            color: var(--ink-color);
            background: #ffffff;
        }

        /* Navbar Styles */
        .navbar-landing {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid rgba(219, 231, 236, 0.8);
            padding: 0.85rem 0;
            transition: all 0.3s ease;
        }

        .navbar-landing.scrolled {
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 12px 30px rgba(24, 52, 71, 0.08);
        }

        .navbar-brand {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--primary-color) !important;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: 0;
        }

        .navbar-brand i {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            background: linear-gradient(135deg, var(--secondary-color), var(--success-color));
            font-size: 1.2rem;
            box-shadow: 0 10px 24px rgba(31, 143, 184, 0.24);
        }

        .nav-link {
            color: var(--primary-color) !important;
            font-weight: 600;
            margin: 0 6px;
            padding: 0.5rem 0.75rem !important;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-link:hover {
            color: var(--secondary-color) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 4px;
            left: 0.75rem;
            width: 0;
            height: 2px;
            background: var(--secondary-color);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .btn-login {
            background: transparent;
            border: 2px solid var(--secondary-color);
            color: var(--secondary-color);
            padding: 9px 22px;
            border-radius: 8px;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: var(--secondary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        /* Hero Section */
        .hero-section {
            min-height: calc(100vh - 76px);
            display: flex;
            align-items: center;
            background:
                radial-gradient(circle at 80% 20%, rgba(60, 163, 122, 0.28), transparent 30%),
                linear-gradient(135deg, #102636 0%, var(--primary-color) 48%, #1f8fb8 100%);
            color: white;
            padding: 96px 0 72px;
            position: relative;
            overflow: hidden;
            margin-top: 76px;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.055) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.055) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.9), transparent);
            opacity: 0.8;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: clamp(3rem, 7vw, 5.8rem);
            font-weight: 800;
            margin-bottom: 1.25rem;
            line-height: 1;
            text-shadow: 0 16px 40px rgba(0, 0, 0, 0.28);
        }

        .hero-subtitle {
            font-size: clamp(1.08rem, 2vw, 1.35rem);
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 650px;
            line-height: 1.7;
        }

        .btn-hero {
            padding: 14px 28px;
            font-size: 1rem;
            border-radius: 8px;
            font-weight: 800;
            transition: all 0.3s ease;
            box-shadow: 0 16px 34px rgba(0, 0, 0, 0.18);
        }

        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .hero-visual {
            position: relative;
            z-index: 1;
            min-height: 360px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .hero-visual::before,
        .hero-visual::after {
            content: '';
            position: absolute;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 8px;
            transform: rotate(-8deg);
        }

        .hero-visual::before {
            width: 78%;
            height: 70%;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 28px 70px rgba(0, 0, 0, 0.22);
        }

        .hero-visual::after {
            width: 54%;
            height: 48%;
            transform: rotate(8deg) translate(38px, 16px);
            background: rgba(255, 255, 255, 0.06);
        }

        .hero-visual i {
            position: relative;
            z-index: 2;
            font-size: clamp(9rem, 18vw, 15rem);
            color: rgba(255, 255, 255, 0.88);
            filter: drop-shadow(0 24px 38px rgba(0, 0, 0, 0.28));
        }

        .truck-scene {
            position: relative;
            z-index: 3;
            width: min(100%, 620px);
            height: 280px;
        }

        .truck-road {
            position: absolute;
            left: 8%;
            right: 8%;
            bottom: 60px;
            height: 8px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.24);
            overflow: hidden;
        }

        .truck-road::before {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(90deg,
                    rgba(255, 255, 255, 0.85) 0 42px,
                    transparent 42px 76px);
            animation: roadMove 0.9s linear infinite;
        }

        .truck-wrap {
            position: absolute;
            left: 50%;
            bottom: 20px;
            width: min(92%, 440px);
            transform: translateX(-50%);
            animation: truckDrive 2.6s ease-in-out infinite;
            filter: drop-shadow(0 24px 26px rgba(0, 0, 0, 0.32));
        }

        .truck-image {
            display: block;
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        .smoke {
            position: absolute;
            right: 8%;
            bottom: 98px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.7);
            filter: blur(1px);
            animation: smokePuff 2.2s ease-out infinite;
        }

        .smoke:nth-child(2) {
            animation-delay: 0.45s;
            width: 18px;
            height: 18px;
            bottom: 108px;
        }

        .smoke:nth-child(3) {
            animation-delay: 0.9s;
            width: 26px;
            height: 26px;
            bottom: 92px;
        }

        @keyframes truckDrive {

            0%,
            100% {
                transform: translateX(-50%) translateY(0);
            }

            50% {
                transform: translateX(-50%) translateY(-8px);
            }
        }

        @keyframes roadMove {
            to {
                transform: translateX(-76px);
            }
        }

        @keyframes smokePuff {
            0% {
                opacity: 0;
                transform: translate(0, 0) scale(0.5);
            }

            18% {
                opacity: 0.75;
            }

            100% {
                opacity: 0;
                transform: translate(96px, -34px) scale(1.8);
            }
        }

        /* Features Section */
        .features-section {
            padding: 88px 0;
            background:
                linear-gradient(180deg, #ffffff 0%, var(--light-bg) 100%);
        }

        .feature-card {
            background: white;
            border-radius: 8px;
            padding: 32px 28px;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            box-shadow: 0 14px 34px rgba(24, 52, 71, 0.07);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary-color), var(--success-color), var(--accent-color));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 22px 48px rgba(24, 52, 71, 0.12);
            border-color: rgba(31, 143, 184, 0.28);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon {
            width: 58px;
            height: 58px;
            background: linear-gradient(135deg, rgba(31, 143, 184, 0.14), rgba(60, 163, 122, 0.16));
            border: 1px solid rgba(31, 143, 184, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 22px;
            font-size: 1.55rem;
            color: var(--secondary-color);
        }

        .feature-title {
            font-size: 1.18rem;
            font-weight: 800;
            margin-bottom: 12px;
            color: var(--primary-color);
        }

        .feature-description {
            color: var(--muted-color);
            line-height: 1.7;
            margin-bottom: 0;
        }

        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #34495e 100%);
            color: white;
            padding: 60px 0;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* CTA Section */
        .cta-section {
            padding: 80px 0;
            background: white;
        }

        .cta-box {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            color: white;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .cta-text {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.95;
        }

        /* Footer */
        .footer {
            background: #112433;
            color: white;
            padding: 54px 0 22px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s;
            width: fit-content;
        }

        .footer-links a:hover {
            color: white;
        }

        .footer h5,
        .footer h6 {
            font-weight: 800;
        }

        /* Animations */
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

        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .navbar-nav {
                align-items: flex-start !important;
                padding-top: 1rem;
                gap: 0.35rem;
            }

            .nav-item.ms-3 {
                margin-left: 0 !important;
                margin-top: 0.5rem;
            }

            .hero-section {
                min-height: auto;
                padding-top: 84px;
            }

            .hero-visual {
                min-height: 260px;
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 3rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .btn-hero {
                width: 100%;
                justify-content: center;
            }

            .features-section {
                padding: 64px 0;
            }

            .truck-scene {
                height: 220px;
            }

            .truck-wrap {
                width: min(94%, 360px);
            }

            .smoke {
                right: 3%;
            }

            .stat-number {
                font-size: 2rem;
            }

            .cta-title {
                font-size: 2rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .truck-wrap,
            .truck-road::before,
            .smoke {
                animation: none;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-landing navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="{{ route('landing') }}">
                <i class="fas fa-truck-loading"></i>
                <span>HRZ Group</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Fitur</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Kontak</a>
                    </li>
                    <li class="nav-item ms-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-login">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-login">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </a>
                        @endauth
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 hero-content animate-fade-in-up">
                    <h1 class="hero-title">HRZ Group</h1>
                    <p class="hero-subtitle">
                        Sistem Manajemen Distribusi Pakan Terintegrasi dengan GPS Tracking Real-time
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="{{ route('login') }}" class="btn btn-light btn-hero">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                        <a href="#features" class="btn btn-outline-light btn-hero">
                            <i class="fas fa-info-circle me-2"></i>Pelajari Lebih Lanjut
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 text-center mt-5 mt-lg-0">
                    <div class="hero-visual" aria-hidden="true">
                        <div class="truck-scene">

                            <div class="truck-wrap">
                                <img class="truck-image" src="{{ asset('jpg/truck1.png') }}" alt="">
                            </div>
                            <span class="smoke"></span>
                            <span class="smoke"></span>
                            <span class="smoke"></span>
                            <div class="truck-road"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Fitur</h2>
                <p class="lead text-muted">Solusi untuk manajemen distribusi pakan Anda</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <h3 class="feature-title">Purchase Order</h3>
                        <p class="feature-description">
                            Kelola PO dengan mudah, tracking status kendaraan, dan manajemen penerima multi-pakan
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <h3 class="feature-title">Manajemen Gudang</h3>
                        <p class="feature-description">
                            Stok real-time, mutasi otomatis, dan lansir gudang terintegrasi dengan sistem
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <h3 class="feature-title">GPS Tracking</h3>
                        <p class="feature-description">
                            Pantau lokasi kendaraan secara real-time dengan integrasi GPS Idtrack
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h3 class="feature-title">Pembayaran Supplier</h3>
                        <p class="feature-description">
                            Down payment, pelunasan, dan rekap pembayaran OA per kendaraan
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Laporan & Analisis</h3>
                        <p class="feature-description">
                            Rekap PO, lansir, rugi laba, dan export ke Excel/PDF dengan mudah
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h3 class="feature-title">Multi-User & Role</h3>
                        <p class="feature-description">
                            Manajemen user dengan role & permission, multi-CV, dan audit trail lengkap
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Footer -->
    <footer id="contact" class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-4 mb-md-0">
                    <h5 class="mb-3">HRZ Group</h5>
                    <p class="text-white-50">
                        Sistem Manajemen Distribusi Pakan yang modern, efisien, dan terintegrasi.
                    </p>
                </div>
                <div class="col-md-3 mb-4 mb-md-0">
                    <h6 class="mb-3">Menu</h6>
                    <div class="footer-links d-flex flex-column gap-2">
                        <a href="{{ route('login') }}">Login</a>
                        <a href="#features">Fitur</a>
                        <a href="{{ route('gps.map') }}">GPS Tracking</a>
                    </div>
                </div>
                <div class="col-md-3">
                    <h6 class="mb-3">Kontak</h6>
                    <div class="footer-links d-flex flex-column gap-2">
                        <a href="mailto:info@hrzgroup.com">
                            <i class="fas fa-envelope me-2"></i>hrz.company123@gmail.com
                        </a>
                        <a href="tel:+62123456789">
                            <i class="fas fa-phone me-2"></i>+62 813-7225-5937
                        </a>
                    </div>
                </div>
            </div>
            <hr class="my-4 bg-white opacity-25">
            <div class="text-center text-white-50">
                <p class="mb-0">&copy; {{ date('Y') }} HRZ Group. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-landing');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const navbarHeight = document.querySelector('.navbar-landing').offsetHeight;
                    const targetPosition = target.offsetTop - navbarHeight;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });

                    // Close mobile menu if open
                    const navbarCollapse = document.querySelector('.navbar-collapse');
                    if (navbarCollapse.classList.contains('show')) {
                        bootstrap.Collapse.getInstance(navbarCollapse).hide();
                    }
                }
            });
        });

        // Animate on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.feature-card, .stat-item').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.6s ease-out';
            observer.observe(el);
        });
    </script>
</body>

</html>
