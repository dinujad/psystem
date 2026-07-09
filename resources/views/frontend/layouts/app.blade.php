<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PrintWorks - Cloud Workspace')</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="@yield('meta_description', 'PrintWorks is a subscription-friendly business suite: point of sale, inventory, and reporting, available securely in the cloud.')">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Vanilla CSS -->
    <style>
        :root {
            /* Colors */
            --bg-deep: #0a0e17;
            --bg-card: #151a28;
            --bg-glass: rgba(21, 26, 40, 0.7);
            
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            
            --accent-cyan: #06b6d4;
            --accent-cyan-hover: #0891b2;
            --accent-purple: #8b5cf6;
            
            --border-glass: rgba(255, 255, 255, 0.08);
            --glow: 0 0 20px rgba(6, 182, 212, 0.4);
            
            /* Typography */
            --font-display: 'Outfit', sans-serif;
            --font-body: 'Inter', sans-serif;
            
            /* Utils */
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --radius-md: 12px;
            --radius-lg: 20px;
            --radius-full: 9999px;
            
            --container-max: 1200px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--bg-deep);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-display);
            line-height: 1.2;
            color: var(--text-primary);
        }

        a {
            text-decoration: none;
            color: inherit;
            transition: var(--transition);
        }

        ul {
            list-style: none;
        }

        /* Container */
        .container {
            width: 100%;
            max-width: var(--container-max);
            margin: 0 auto;
            padding: 0 5%;
        }

        /* Utilities */
        .text-gradient {
            background: linear-gradient(135deg, var(--text-primary) 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .text-gradient-accent {
            background: linear-gradient(135deg, var(--accent-cyan) 0%, var(--accent-purple) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: var(--radius-full);
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            outline: none;
        }

        .btn-primary {
            background-color: var(--accent-cyan);
            color: #000;
            box-shadow: 0 4px 14px rgba(6, 182, 212, 0.3);
        }

        .btn-primary:hover {
            background-color: var(--accent-cyan-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(6, 182, 212, 0.4);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--text-primary);
            border: 1px solid var(--border-glass);
            backdrop-filter: blur(8px);
        }

        .btn-outline:hover {
            background-color: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            padding: 20px 0;
            transition: var(--transition);
            background: transparent;
        }

        .navbar.scrolled {
            padding: 15px 0;
            background: var(--bg-glass);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-glass);
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand-logo {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .brand-logo i {
            color: var(--accent-cyan);
        }

        .nav-links {
            display: flex;
            gap: 32px;
            align-items: center;
        }

        .nav-link {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-secondary);
            position: relative;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--text-primary);
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -4px;
            left: 0;
            background-color: var(--accent-cyan);
            transition: var(--transition);
            border-radius: 2px;
        }
        
        .nav-link:hover::after, .nav-link.active::after {
            width: 100%;
        }

        .nav-actions {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        /* Mobile Menu */
        .mobile-toggle {
            display: none;
            cursor: pointer;
            font-size: 1.5rem;
            color: var(--text-primary);
        }

        /* Main Layout Elements */
        .page-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex-grow: 1;
            padding-top: 100px;
        }

        /* Background Effects */
        .bg-glow {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.15) 0%, rgba(139, 92, 246, 0.05) 50%, rgba(0, 0, 0, 0) 70%);
            border-radius: 50%;
            filter: blur(60px);
            z-index: -1;
            pointer-events: none;
        }
        .glow-top-right { top: -100px; right: -100px; }
        .glow-bottom-left { bottom: 10%; left: -200px; }

        /* Footer */
        .footer {
            background-color: var(--bg-card);
            border-top: 1px solid var(--border-glass);
            padding: 60px 0 30px;
            margin-top: 80px;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        .footer-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .footer-desc {
            color: var(--text-secondary);
            margin-bottom: 20px;
            max-width: 300px;
        }
        .footer-link {
            display: block;
            color: var(--text-secondary);
            margin-bottom: 12px;
            font-size: 0.95rem;
        }
        .footer-link:hover {
            color: var(--accent-cyan);
            transform: translateX(5px);
        }
        .footer-bottom {
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.05);
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-up {
            animation: fadeInUp 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
            opacity: 0;
        }
        .delay-100 { animation-delay: 100ms; }
        .delay-200 { animation-delay: 200ms; }
        .delay-300 { animation-delay: 300ms; }

        /* Section styles */
        .section-padding { padding: 100px 0; }
        .text-center { text-align: center; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-8 { margin-bottom: 2rem; }
        .mb-12 { margin-bottom: 3rem; }
        .mt-8 { margin-top: 2rem; }
        .section-subtitle {
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--accent-cyan);
            margin-bottom: 12px;
            display: block;
        }
        .section-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .section-desc {
            color: var(--text-secondary);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.8;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }
        
        @media (max-width: 768px) {
            .nav-links, .nav-actions { display: none; }
            .mobile-toggle { display: block; }
            .footer-grid { grid-template-columns: 1fr; gap: 30px; }
            .section-title { font-size: 2rem; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="page-wrapper">
        <div class="bg-glow glow-top-right"></div>
        <div class="bg-glow glow-bottom-left"></div>

        <!-- Navbar -->
        <nav class="navbar" id="navbar">
            <div class="container nav-container">
                <a href="{{ route('frontend.home') }}" class="brand-logo">
                    <i class="fa-solid fa-layer-group"></i> PrintWorks
                </a>
                
                <ul class="nav-links">
                    <li><a href="{{ route('frontend.home') }}" class="nav-link {{ request()->routeIs('frontend.home') ? 'active' : '' }}">Home</a></li>
                    <li><a href="{{ route('frontend.pricing') }}" class="nav-link {{ request()->routeIs('frontend.pricing') ? 'active' : '' }}">Pricing</a></li>
                    <li><a href="{{ route('frontend.about') }}" class="nav-link {{ request()->routeIs('frontend.about') ? 'active' : '' }}">About Us</a></li>
                    <li><a href="{{ route('frontend.contact') }}" class="nav-link {{ request()->routeIs('frontend.contact') ? 'active' : '' }}">Contact</a></li>
                </ul>
                
                <div class="nav-actions">
                    <a href="/login" class="btn btn-outline">Login</a>
                    <a href="/business/register" class="btn btn-primary">Register Now</a>
                </div>
                
                <div class="mobile-toggle">
                    <i class="fa-solid fa-bars"></i>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main>
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <div class="footer-grid">
                    <div>
                        <a href="{{ route('frontend.home') }}" class="brand-logo mb-4">
                            <i class="fa-solid fa-layer-group"></i> PrintWorks
                        </a>
                        <p class="footer-desc">The ultimate subscription-friendly cloud workspace for managing sales, stock, and multiple branches seamlessly.</p>
                        <div style="display:flex; gap:15px; margin-top:20px;">
                            <a href="#" style="color:var(--text-secondary); font-size:1.2rem;"><i class="fa-brands fa-facebook-f"></i></a>
                            <a href="#" style="color:var(--text-secondary); font-size:1.2rem;"><i class="fa-brands fa-twitter"></i></a>
                            <a href="#" style="color:var(--text-secondary); font-size:1.2rem;"><i class="fa-brands fa-instagram"></i></a>
                            <a href="#" style="color:var(--text-secondary); font-size:1.2rem;"><i class="fa-brands fa-linkedin-in"></i></a>
                        </div>
                    </div>
                    <div>
                        <h4 class="footer-title">Product</h4>
                        <a href="{{ route('frontend.home') }}" class="footer-link">Features</a>
                        <a href="{{ route('frontend.pricing') }}" class="footer-link">Pricing</a>
                        <a href="/login" class="footer-link">Login</a>
                        <a href="/business/register" class="footer-link">Sign Up</a>
                    </div>
                    <div>
                        <h4 class="footer-title">Company</h4>
                        <a href="{{ route('frontend.about') }}" class="footer-link">About Us</a>
                        <a href="{{ route('frontend.contact') }}" class="footer-link">Contact</a>
                        <a href="#" class="footer-link">Privacy Policy</a>
                        <a href="#" class="footer-link">Terms of Service</a>
                    </div>
                    <div>
                        <h4 class="footer-title">Newsletter</h4>
                        <p class="footer-desc">Subscribe to get the latest updates.</p>
                        <form style="display:flex; gap:10px;">
                            <input type="email" placeholder="Your email" style="padding:10px 15px; border-radius:8px; border:1px solid var(--border-glass); background:rgba(255,255,255,0.05); color:white; width:100%; outline:none;">
                            <button type="submit" class="btn btn-primary" style="padding:10px 15px;"><i class="fa-solid fa-paper-plane"></i></button>
                        </form>
                    </div>
                </div>
                <div class="footer-bottom">
                    &copy; {{ date('Y') }} PrintWorks. All rights reserved.
                </div>
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    <script>
        // Navbar Scroll Effect
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
