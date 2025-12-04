<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Money Transfer - Send Money Fast & Secure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .feature-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 15px;
            padding: 30px;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            color: white;
            font-size: 2rem;
        }
        
        .btn-login {
            padding: 12px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .btn-login-user {
            background: white;
            color: #667eea;
            border: 2px solid white;
        }
        
        .btn-login-user:hover {
            background: transparent;
            color: white;
            border: 2px solid white;
            transform: scale(1.05);
        }
        
        .btn-login-agent {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-login-agent:hover {
            background: white;
            color: #667eea;
            transform: scale(1.05);
        }
        
        .stats-section {
            background: #f8f9fa;
            padding: 60px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .section-subtitle {
            font-size: 1.2rem;
            color: #6c757d;
            margin-bottom: 50px;
        }
            </style>
    </head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('home') }}" style="color: #667eea; font-size: 1.5rem;">
                <i class="bi bi-send-fill"></i> MoneyTransfer
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    @auth
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                    @else
                        <li class="nav-item me-2">
                            <a href="{{ route('login') }}" class="nav-link">Login</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('register') }}" class="btn btn-outline-primary btn-sm">Register</a>
                        </li>
                    @endauth
                    </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1 class="display-3 fw-bold mb-4">Send Money Fast & Secure</h1>
                    <p class="lead mb-4">Transfer money to anyone, anywhere in the world. Fast, secure, and reliable money transfer services at your fingertips.</p>
                    <div class="d-flex flex-column flex-sm-row gap-3 mb-4">
                        <a href="{{ route('login') }}" class="btn btn-login btn-login-user">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </a>
                    </div>
                    <div class="d-flex gap-4">
                        <a href="{{ route('register') }}" class="text-white text-decoration-none">
                            <i class="bi bi-person-plus me-1"></i> Create Account
                        </a>
                        <a href="{{ route('agents.register') }}" class="text-white text-decoration-none">
                            <i class="bi bi-briefcase me-1"></i> Become an Agent
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center mt-5 mt-lg-0">
                    <div class="position-relative">
                        <i class="bi bi-cash-coin" style="font-size: 15rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6 stat-item">
                    <div class="stat-number">100K+</div>
                    <div class="text-muted">Active Users</div>
                </div>
                <div class="col-md-3 col-6 stat-item">
                    <div class="stat-number">50+</div>
                    <div class="text-muted">Countries</div>
                </div>
                <div class="col-md-3 col-6 stat-item">
                    <div class="stat-number">$1M+</div>
                    <div class="text-muted">Transferred</div>
                </div>
                <div class="col-md-3 col-6 stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="text-muted">Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Why Choose Us?</h2>
                <p class="section-subtitle">Fast, secure, and reliable money transfer services</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Fast Transfers</h4>
                        <p class="text-muted">Send money instantly to anywhere in the world. Most transfers complete within minutes.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check-fill"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Secure & Safe</h4>
                        <p class="text-muted">Bank-level encryption and security measures to protect your money and personal information.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-globe"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Global Network</h4>
                        <p class="text-muted">Send money to over 50 countries with competitive exchange rates and low fees.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-phone-fill"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Easy to Use</h4>
                        <p class="text-muted">Simple and intuitive interface. Transfer money in just a few clicks from anywhere.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-shop"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Agent Network</h4>
                        <p class="text-muted">Pick up cash at thousands of agent locations worldwide for convenient money collection.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-headset"></i>
                        </div>
                        <h4 class="fw-bold mb-3">24/7 Support</h4>
                        <p class="text-muted">Our customer support team is available around the clock to assist you with any questions.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-4">Ready to Get Started?</h2>
            <p class="lead mb-4">Join thousands of satisfied customers sending money worldwide</p>
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a href="{{ route('register') }}" class="btn btn-light btn-lg px-5">
                    <i class="bi bi-person-plus me-2"></i>Create Free Account
                </a>
                <a href="{{ route('agents.register') }}" class="btn btn-outline-light btn-lg px-5">
                    <i class="bi bi-briefcase me-2"></i>Become an Agent
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="bi bi-send-fill"></i> MoneyTransfer</h5>
                    <p class="text-muted">Fast, secure, and reliable money transfer services worldwide.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6 class="mb-3">Quick Links</h6>
                    <div class="d-flex flex-column flex-md-row gap-3 justify-content-md-end">
                        <a href="{{ route('agents.map') }}" class="text-white text-decoration-none">
                            <i class="bi bi-geo-alt me-1"></i>Find Agents
                        </a>
                        <a href="{{ route('login') }}" class="text-white text-decoration-none">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </a>
                        <a href="{{ route('register') }}" class="text-white text-decoration-none">
                            <i class="bi bi-person-plus me-1"></i>Register
                        </a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center text-muted">
                <p class="mb-0">&copy; {{ date('Y') }} Money Transfer Application. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
