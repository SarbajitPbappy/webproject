<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HostelEase — Premium Student Hostel with Modern Amenities, 24/7 Security, and Smart Management">
    <title>HostelEase — Premium Student Hostel & Accommodation</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --accent: #06b6d4;
            --dark: #0f172a;
            --light: #f8fafc;
            --font: 'Inter', sans-serif;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--font); background: #fff; overflow-x: hidden; }

        /* ── NAVBAR ── */
        .landing-nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            padding: 1rem 2rem;
            display: flex; align-items: center; justify-content: space-between;
            transition: all 0.4s ease;
        }
        .landing-nav.scrolled {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(12px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            padding: 0.75rem 2rem;
        }
        .nav-logo { display: flex; align-items: center; gap: 0.75rem; text-decoration: none; }
        .nav-logo-icon { font-size: 2rem; color: #fff; }
        .landing-nav.scrolled .nav-logo-icon { color: var(--primary); }
        .nav-logo-text { font-size: 1.5rem; font-weight: 800; color: #fff; letter-spacing: -0.03em; }
        .landing-nav.scrolled .nav-logo-text { color: var(--dark); }
        .nav-links { display: flex; align-items: center; gap: 2rem; }
        .nav-links a { color: rgba(255,255,255,0.9); text-decoration: none; font-weight: 500; font-size: 0.9rem; transition: 0.2s; }
        .landing-nav.scrolled .nav-links a { color: #475569; }
        .nav-links a:hover { color: #fff; }
        .landing-nav.scrolled .nav-links a:hover { color: var(--primary); }
        .btn-nav-login {
            padding: 0.5rem 1.5rem; border-radius: 50px;
            background: rgba(255,255,255,0.2); border: 1.5px solid rgba(255,255,255,0.5);
            color: #fff; font-weight: 600; font-size: 0.875rem; text-decoration: none;
            transition: all 0.3s;
        }
        .landing-nav.scrolled .btn-nav-login {
            background: var(--primary); border-color: var(--primary); color: #fff;
        }
        .btn-nav-login:hover { background: rgba(255,255,255,0.3); color: #fff; transform: translateY(-1px); }

        /* ── HERO ── */
        .hero-section {
            min-height: 100vh;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4338ca 75%, #6366f1 100%);
            display: flex; align-items: center; position: relative; overflow: hidden;
        }
        .hero-bg-pattern {
            position: absolute; inset: 0;
            background-image: radial-gradient(circle at 20% 50%, rgba(99,102,241,0.3) 0%, transparent 60%),
                              radial-gradient(circle at 80% 20%, rgba(139,92,246,0.2) 0%, transparent 50%),
                              radial-gradient(circle at 60% 80%, rgba(6,182,212,0.15) 0%, transparent 45%);
        }
        .hero-grid {
            position: absolute; inset: 0; opacity: 0.04;
            background-image: linear-gradient(rgba(255,255,255,0.5) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.5) 1px, transparent 1px);
            background-size: 60px 60px;
        }
        .hero-content { position: relative; z-index: 2; }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px; padding: 0.4rem 1rem;
            color: rgba(255,255,255,0.9); font-size: 0.8rem; font-weight: 500;
            margin-bottom: 1.5rem; backdrop-filter: blur(8px);
        }
        .hero-badge i { color: #fbbf24; }
        .hero-title {
            font-size: clamp(2.5rem, 6vw, 4.5rem);
            font-weight: 900; color: #fff; line-height: 1.1;
            letter-spacing: -0.04em; margin-bottom: 1.5rem;
        }
        .hero-title .accent { color: #a5b4fc; }
        .hero-subtitle {
            font-size: 1.2rem; color: rgba(255,255,255,0.75);
            line-height: 1.7; margin-bottom: 2.5rem; max-width: 540px;
        }
        .hero-cta { display: flex; gap: 1rem; flex-wrap: wrap; }
        .btn-hero-primary {
            padding: 0.9rem 2.5rem; border-radius: 50px;
            background: #fff; color: var(--primary-dark);
            font-weight: 700; font-size: 1rem; text-decoration: none;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.5rem;
        }
        .btn-hero-primary:hover { transform: translateY(-3px); box-shadow: 0 15px 40px rgba(0,0,0,0.3); color: var(--primary-dark); }
        .btn-hero-secondary {
            padding: 0.9rem 2.5rem; border-radius: 50px;
            background: transparent; border: 2px solid rgba(255,255,255,0.4);
            color: #fff; font-weight: 600; font-size: 1rem; text-decoration: none;
            transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.5rem;
        }
        .btn-hero-secondary:hover { background: rgba(255,255,255,0.1); color: #fff; transform: translateY(-3px); }

        /* Hero image / mockup */
        .hero-visual {
            position: relative; z-index: 2;
        }
        .hero-card-float {
            background: rgba(255,255,255,0.12); backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.2); border-radius: 20px;
            padding: 1.5rem; color: #fff; margin-bottom: 1rem;
        }
        .hero-img-container {
            border-radius: 24px; overflow: hidden;
            box-shadow: 0 30px 80px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.1);
            position: relative;
        }
        .hero-img-container img { width: 100%; display: block; }

        /* Floating stat badges */
        .float-badge {
            position: absolute;
            background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);
            border-radius: 14px; padding: 0.75rem 1.25rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            display: flex; align-items: center; gap: 0.75rem;
            animation: float 3s ease-in-out infinite;
        }
        .float-badge:nth-child(2) { animation-delay: 1s; }
        .float-badge:nth-child(3) { animation-delay: 2s; }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }
        .float-badge .badge-icon { font-size: 1.5rem; }
        .float-badge .badge-val { font-size: 1.1rem; font-weight: 800; color: var(--dark); line-height: 1; }
        .float-badge .badge-lbl { font-size: 0.7rem; color: #64748b; }

        /* ── STATS BAR ── */
        .stats-bar {
            background: var(--dark); padding: 3rem 0;
        }
        .stat-item { text-align: center; padding: 1rem; }
        .stat-number {
            font-size: 3rem; font-weight: 900; line-height: 1;
            background: linear-gradient(135deg, #818cf8, #a78bfa);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-label { color: #94a3b8; margin-top: 0.5rem; font-size: 0.9rem; }
        .stat-divider { width: 1px; background: rgba(255,255,255,0.1); align-self: stretch; margin: 1rem 0; }

        /* ── SECTIONS ── */
        section { padding: 5rem 0; }
        .section-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2);
            border-radius: 50px; padding: 0.4rem 1rem;
            color: var(--primary); font-size: 0.8rem; font-weight: 600;
            margin-bottom: 1rem;
        }
        .section-title { font-size: 2.5rem; font-weight: 800; color: var(--dark); letter-spacing: -0.03em; margin-bottom: 1rem; }
        .section-subtitle { font-size: 1.1rem; color: #64748b; max-width: 560px; }

        /* ── FEATURES ── */
        .feature-card {
            padding: 2rem; border-radius: 20px;
            background: #fff; border: 1px solid #e2e8f0;
            transition: all 0.3s; height: 100%;
        }
        .feature-card:hover { transform: translateY(-8px); box-shadow: 0 20px 50px rgba(99,102,241,0.12); border-color: var(--primary); }
        .feature-icon {
            width: 56px; height: 56px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 1.25rem;
        }
        .feature-card h5 { font-size: 1.1rem; font-weight: 700; color: var(--dark); margin-bottom: 0.5rem; }
        .feature-card p { color: #64748b; font-size: 0.9rem; line-height: 1.7; }

        /* ── ROOMS SHOWCASE ── */
        .room-card {
            border-radius: 20px; overflow: hidden;
            border: 1px solid #e2e8f0; transition: all 0.3s; background: #fff;
        }
        .room-card:hover { transform: translateY(-6px); box-shadow: 0 20px 50px rgba(0,0,0,0.12); }
        .room-img { width: 100%; height: 220px; object-fit: cover; }
        .room-img-placeholder {
            width: 100%; height: 220px;
            display: flex; align-items: center; justify-content: center;
            font-size: 4rem;
        }
        .room-body { padding: 1.5rem; }
        .room-type-badge { font-size: 0.7rem; font-weight: 600; padding: 0.3em 0.8em; border-radius: 50px; }
        .room-title { font-size: 1.1rem; font-weight: 700; margin: 0.5rem 0; color: var(--dark); }
        .room-price { font-size: 1.5rem; font-weight: 800; color: var(--primary); }
        .room-amenities { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.75rem; }
        .room-amenity { font-size: 0.75rem; color: #64748b; background: #f1f5f9; padding: 0.25rem 0.6rem; border-radius: 50px; }

        /* ── ROLES SECTION ── */
        .roles-section { background: linear-gradient(135deg, #f8fafc, #f1f5f9); }
        .role-card {
            background: #fff; border-radius: 20px; padding: 2.5rem;
            border: 2px solid #e2e8f0; transition: all 0.3s; height: 100%;
        }
        .role-card:hover { border-color: var(--primary); box-shadow: 0 15px 40px rgba(99,102,241,0.12); }
        .role-icon-large {
            width: 72px; height: 72px; border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; margin-bottom: 1.5rem;
        }
        .role-card h4 { font-size: 1.3rem; font-weight: 800; color: var(--dark); margin-bottom: 0.25rem; }
        .role-card .role-badge-text { font-size: 0.75rem; font-weight: 600; padding: 0.2em 0.7em; border-radius: 50px; display: inline-block; margin-bottom: 1rem; }
        .role-feature-list { list-style: none; padding: 0; margin: 0; }
        .role-feature-list li {
            display: flex; align-items: flex-start; gap: 0.75rem;
            padding: 0.4rem 0; font-size: 0.875rem; color: #475569;
        }
        .role-feature-list li i { color: var(--primary); margin-top: 2px; flex-shrink: 0; }

        /* ── TESTIMONIALS ── */
        .testimonials-section { background: #fff; }
        .testimonial-card {
            background: #f8fafc; border-radius: 20px; padding: 2rem;
            border: 1px solid #e2e8f0; height: 100%;
        }
        .testimonial-stars { color: #f59e0b; margin-bottom: 1rem; }
        .testimonial-text { color: #475569; font-style: italic; line-height: 1.7; margin-bottom: 1.5rem; }
        .testimonial-author { display: flex; align-items: center; gap: 0.75rem; }
        .testimonial-avatar {
            width: 48px; height: 48px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: 1.1rem;
        }
        .testimonial-name { font-weight: 700; color: var(--dark); font-size: 0.9rem; }
        .testimonial-meta { font-size: 0.75rem; color: #94a3b8; }

        /* ── CTA ── */
        .cta-section {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 6rem 0; text-align: center; color: #fff;
        }
        .cta-section h2 { font-size: 3rem; font-weight: 900; letter-spacing: -0.03em; margin-bottom: 1rem; }
        .cta-section p { font-size: 1.2rem; opacity: 0.85; margin-bottom: 2rem; }

        /* ── FOOTER ── */
        .landing-footer { background: var(--dark); color: #94a3b8; padding: 3rem 0 2rem; }
        .footer-brand { color: #fff; font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem; }
        .footer-link { color: #94a3b8; text-decoration: none; font-size: 0.875rem; display: block; margin-bottom: 0.5rem; }
        .footer-link:hover { color: #fff; }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.08); margin-top: 2rem; padding-top: 1.5rem; }

        /* scroll animation */
        @media (max-width: 991px) {
            .hero-visual { margin-top: 3rem; }
            .nav-links { display: none; }
        }
    </style>
</head>
<body>

<!-- ─── NAVBAR ─────────────────────────────────── -->
<nav class="landing-nav" id="mainNav">
    <a href="<?php echo BASE_URL; ?>" class="nav-logo">
        <i class="bi bi-building nav-logo-icon"></i>
        <span class="nav-logo-text">HostelEase</span>
    </a>
    <div class="nav-links">
        <a href="#features">Features</a>
        <a href="#rooms">Rooms</a>
        <a href="#roles">Who We Serve</a>
        <a href="#testimonials">Reviews</a>
    </div>
    <a href="<?php echo BASE_URL; ?>?url=auth/login" class="btn-nav-login">
        <i class="bi bi-box-arrow-in-right me-1"></i> Login
    </a>
</nav>

<!-- ─── HERO ──────────────────────────────────── -->
<section class="hero-section" id="hero">
    <div class="hero-bg-pattern"></div>
    <div class="hero-grid"></div>
    <div class="container py-5 mt-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 hero-content" data-aos="fade-right">
                <div class="hero-badge">
                    <i class="bi bi-star-fill"></i>
                    <span>Bangladesh's #1 Smart Hostel System</span>
                </div>
                <h1 class="hero-title">
                    Your <span class="accent">Home Away</span><br>From Home
                </h1>
                <p class="hero-subtitle">
                    HostelEase provides premium student accommodation with world-class amenities, 24/7 security, and a smart digital management system — because students deserve more.
                </p>
                <div class="hero-cta">
                    <a href="<?php echo BASE_URL; ?>?url=auth/register" class="btn-hero-primary">
                        <i class="bi bi-person-plus"></i> Apply Now
                    </a>
                    <a href="<?php echo BASE_URL; ?>?url=auth/login" class="btn-hero-secondary">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                </div>
            </div>

            <div class="col-lg-6 hero-visual" data-aos="fade-left" data-aos-delay="200">
                <div class="position-relative">
                    <!-- Main hero image -->
                    <div class="hero-img-container">
                        <img src="https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=700&q=80&auto=format&fit=crop"
                             alt="Modern Hostel Building" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div style="display:none; height:380px; background: linear-gradient(135deg, rgba(99,102,241,0.3), rgba(139,92,246,0.3)); align-items:center; justify-content:center; font-size:5rem; color:rgba(255,255,255,0.5);">🏢</div>
                    </div>
                    <!-- Floating badges -->
                    <div class="float-badge" style="bottom: -20px; left: -20px;">
                        <div class="badge-icon">🎓</div>
                        <div>
                            <div class="badge-val"><?php echo number_format($stats['students'] > 50 ? $stats['students'] : 100); ?>+</div>
                            <div class="badge-lbl">Active Students</div>
                        </div>
                    </div>
                    <div class="float-badge" style="top: 20px; right: -15px; animation-delay: 1.2s;">
                        <div class="badge-icon">⭐</div>
                        <div>
                            <div class="badge-val">4.9/5</div>
                            <div class="badge-lbl">Student Rating</div>
                        </div>
                    </div>
                    <div class="float-badge" style="bottom: 80px; right: -20px; animation-delay: 0.6s;">
                        <div class="badge-icon">🔒</div>
                        <div>
                            <div class="badge-val">24/7</div>
                            <div class="badge-lbl">Security</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── STATS BAR ─────────────────────────────── -->
<div class="stats-bar">
    <div class="container">
        <div class="row text-center g-3">
            <div class="col-6 col-md-3" data-aos="zoom-in">
                <div class="stat-item">
                    <div class="stat-number" data-target="<?php echo max($stats['students'], 100); ?>"><?php echo max($stats['students'], 100); ?>+</div>
                    <div class="stat-label"><i class="bi bi-mortarboard me-1"></i>Happy Students</div>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="100">
                <div class="stat-item">
                    <div class="stat-number"><?php echo max($stats['floors'], 10); ?></div>
                    <div class="stat-label"><i class="bi bi-building me-1"></i>Floors</div>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="200">
                <div class="stat-item">
                    <div class="stat-number"><?php echo max($stats['rooms'], 50); ?>+</div>
                    <div class="stat-label"><i class="bi bi-door-open me-1"></i>Rooms</div>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="300">
                <div class="stat-item">
                    <div class="stat-number"><?php echo max($stats['staff'], 62); ?>+</div>
                    <div class="stat-label"><i class="bi bi-people me-1"></i>Team Members</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── FEATURES ─────────────────────────────── -->
<section id="features">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="section-badge"><i class="bi bi-stars"></i> Premium Amenities</div>
            <h2 class="section-title">Everything You Need,<br>All in One Place</h2>
            <p class="section-subtitle mx-auto">We provide a safe, comfortable, and enriching environment where students can focus on academics and personal growth.</p>
        </div>
        <div class="row g-4">
            <?php
            $features = [
                ['🛡️','bg-primary-subtle text-primary','24/7 Security','CCTV surveillance, biometric access, and round-the-clock security personnel for your peace of mind.'],
                ['🍽️','bg-success-subtle text-success','Dining Hall','Nutritious, freshly prepared meals three times a day with special dietary accommodations available.'],
                ['📶','bg-info-subtle text-info','High-Speed WiFi','Blazing fast fiber-optic internet across all floors — perfect for research, streaming, and staying connected.'],
                ['🫧','bg-warning-subtle text-warning','Laundry Service','Modern washing machines and dryers available 24/7 with convenient pick-up and delivery options.'],
                ['📚','bg-danger-subtle text-danger','Study Rooms','Quiet, air-conditioned study rooms and group discussion spaces with whiteboards.'],
                ['🏋️','bg-secondary-subtle text-secondary','Fitness Center','Fully equipped gym with modern equipment, open from 5am to 11pm for resident students.'],
                ['🏥','bg-primary-subtle text-primary','Medical Care','On-call nurse and weekly doctor visits. Emergency medical assistance always available.'],
                ['🎮','bg-success-subtle text-success','Recreation Area','Common room with TV, games, table tennis, and social events to keep students refreshed.'],
            ];
            foreach ($features as $i => [$icon, $cls, $title, $desc]):
            ?>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?php echo ($i % 4) * 100; ?>">
                <div class="feature-card">
                    <div class="feature-icon <?php echo $cls; ?>"><?php echo $icon; ?></div>
                    <h5><?php echo $title; ?></h5>
                    <p><?php echo $desc; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ─── ROOMS SHOWCASE ────────────────────────── -->
<section id="rooms" style="background: #f8fafc;">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="section-badge"><i class="bi bi-door-open"></i> Room Categories</div>
            <h2 class="section-title">Choose Your Perfect Space</h2>
            <p class="section-subtitle mx-auto">From cozy single rooms to budget-friendly dormitories — we have the right room for every lifestyle and budget.</p>
        </div>
        <div class="row g-4">
            <?php
            $rooms_showcase = [
                ['Single Room','single','bg-primary','🛏️','Full independence and privacy','5,000','WiFi,Study Desk,AC,Attached Bath',
                 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=400&q=80'],
                ['Double Sharing','double','bg-success','🛋️','Share with a roommate, save more','3,500','WiFi,Study Desks,Ceiling Fan,Shared Bath',
                 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=400&q=80'],
                ['Triple Room','triple','bg-info','🏠','Great for friend groups','2,800','WiFi,3 Study Desks,Fan,Shared Bath',
                 'https://images.unsplash.com/photo-1540518614846-7eded433c457?w=400&q=80'],
                ['Dormitory','dormitory','bg-warning','🏩','Most economical option','1,800','WiFi,Lockers,Common Study Area',
                 'https://images.unsplash.com/photo-1520277739336-7bf67fb921be?w=400&q=80'],
            ];
            foreach ($rooms_showcase as $i => [$name, $type, $badge, $emoji, $tagline, $price, $amenities, $img]):
            ?>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?php echo $i * 100; ?>">
                <div class="room-card">
                    <div class="position-relative">
                        <img src="<?php echo $img; ?>" alt="<?php echo $name; ?>" class="room-img"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="room-img-placeholder <?php echo $badge; ?> bg-opacity-10" style="display:none;"><?php echo $emoji; ?></div>
                    </div>
                    <div class="room-body">
                        <span class="room-type-badge badge <?php echo $badge; ?>"><?php echo ucfirst($type); ?></span>
                        <div class="room-title"><?php echo $name; ?></div>
                        <small class="text-muted"><?php echo $tagline; ?></small>
                        <div class="room-price mt-2">৳<?php echo $price; ?> <small class="text-muted fs-6 fw-normal">/month</small></div>
                        <div class="room-amenities">
                            <?php foreach (explode(',', $amenities) as $a): ?>
                            <span class="room-amenity"><?php echo trim($a); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ─── ROLES SECTION ─────────────────────────── -->
<section id="roles" class="roles-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="section-badge"><i class="bi bi-people"></i> User Roles</div>
            <h2 class="section-title">Designed for Everyone</h2>
            <p class="section-subtitle mx-auto">HostelEase has a tailored experience for every stakeholder — from top-level administrators to the students they serve.</p>
        </div>
        <div class="row g-4">
            <?php
            $roles = [
                ['bi-shield-lock-fill','bg-danger-subtle text-danger','danger','Super Admin','Full System Control',
                 ['Manage all admin accounts','Access complete audit logs','Configure system settings','Generate financial reports','View all hostel data']],
                ['bi-person-badge-fill','bg-primary-subtle text-primary','primary','Admin / Warden','Hostel Operations',
                 ['Manage student records','Allocate & transfer rooms','Record student payments','Handle complaint tickets','Post hostel notices']],
                ['bi-mortarboard-fill','bg-success-subtle text-success','success','Student','Self-Service Portal',
                 ['View personal room info','Check payment history','Submit complaints','View hostel notices','Update own profile']],
                ['bi-wrench-adjustable','bg-warning-subtle text-warning','warning','Maintenance Staff','Task Management',
                 ['View assigned complaints','Update ticket status','Mark issues resolved','See maintenance schedule','Report completed tasks']],
            ];
            foreach ($roles as $i => [$icon, $iconCls, $color, $title, $subtitle, $features]):
            ?>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?php echo $i * 100; ?>">
                <div class="role-card">
                    <div class="role-icon-large <?php echo $iconCls; ?>">
                        <i class="bi <?php echo $icon; ?>"></i>
                    </div>
                    <h4><?php echo $title; ?></h4>
                    <span class="role-badge-text bg-<?php echo $color; ?>-subtle text-<?php echo $color; ?>"><?php echo $subtitle; ?></span>
                    <ul class="role-feature-list">
                        <?php foreach ($features as $f): ?>
                        <li><i class="bi bi-check-circle-fill"></i><?php echo $f; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ─── TESTIMONIALS ──────────────────────────── -->
<section id="testimonials" class="testimonials-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="section-badge"><i class="bi bi-chat-quote"></i> Testimonials</div>
            <h2 class="section-title">What Our Residents Say</h2>
        </div>
        <div class="row g-4">
            <?php
            $testimonials = [
                ['Rahman A.','Computer Science, Year 3','RA','The WiFi never goes down and the study rooms are a lifesaver during exam season. Best decision I made!'],
                ['Fatima K.','Medical Student, Year 2','FK','The management system makes everything so easy. I can pay my fees online and track my room status in real time.'],
                ['Tanvir H.','Engineering, Year 4','TH','I raised a maintenance complaint and it was fixed within 24 hours! The staff is incredibly responsive.'],
                ['Nadia S.','Business Admin, Year 1','NS','Moving in was smooth and the admin team was very helpful. The hostel feels safe and clean — just like home.'],
                ['Arif M.','Law Student, Year 3','AM','The dining hall food is actually good! And the gym keeps me healthy despite the hectic semester schedule.'],
                ['Priya D.','Architecture, Year 2','PD','The hostel notice system keeps me updated on everything happening. Very organized and professional management.'],
            ];
            foreach ($testimonials as $i => [$name, $meta, $initials, $text]):
            ?>
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?php echo ($i % 3) * 100; ?>">
                <div class="testimonial-card">
                    <div class="testimonial-stars">★★★★★</div>
                    <p class="testimonial-text">"<?php echo $text; ?>"</p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar"><?php echo $initials; ?></div>
                        <div>
                            <div class="testimonial-name"><?php echo $name; ?></div>
                            <div class="testimonial-meta"><?php echo $meta; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ─── CTA ───────────────────────────────────── -->
<section class="cta-section">
    <div class="container" data-aos="zoom-in">
        <h2>Ready to Join HostelEase?</h2>
        <p>Experience modern student living with premium amenities and smart digital management.</p>
        <a href="<?php echo BASE_URL; ?>?url=auth/register" class="btn-hero-primary">
            <i class="bi bi-person-plus"></i> Apply for a Room
        </a>
        <a href="<?php echo BASE_URL; ?>?url=auth/login" class="btn-hero-secondary" style="margin-left:1rem;">
            <i class="bi bi-box-arrow-in-right"></i> Login
        </a>
    </div>
</section>

<!-- ─── FOOTER ────────────────────────────────── -->
<footer class="landing-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="footer-brand"><i class="bi bi-building me-2"></i>HostelEase</div>
                <p style="font-size:0.875rem; color:#64748b; margin-top:0.5rem;">Smart Hostel Management — delivering premium student accommodation with modern amenities and intelligent digital management.</p>
            </div>
            <div class="col-lg-2">
                <div style="color:#fff; font-weight:600; margin-bottom:1rem;">Quick Links</div>
                <a href="#features" class="footer-link">Features</a>
                <a href="#rooms" class="footer-link">Rooms</a>
                <a href="#roles" class="footer-link">Roles</a>
                <a href="#testimonials" class="footer-link">Reviews</a>
            </div>
            <div class="col-lg-3">
                <div style="color:#fff; font-weight:600; margin-bottom:1rem;">Contact</div>
                <p style="font-size:0.875rem; color:#64748b;"><i class="bi bi-geo-alt me-2"></i>123 University Road, Dhaka 1000</p>
                <p style="font-size:0.875rem; color:#64748b;"><i class="bi bi-telephone me-2"></i>+880 1700-000000</p>
                <p style="font-size:0.875rem; color:#64748b;"><i class="bi bi-envelope me-2"></i>info@hostelease.com</p>
            </div>
            <div class="col-lg-3">
                <div style="color:#fff; font-weight:600; margin-bottom:1rem;">Access Portal</div>
                <a href="<?php echo BASE_URL; ?>?url=auth/login" class="btn btn-primary rounded-pill px-4 mt-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login to System
                </a>
            </div>
        </div>
        <div class="footer-bottom d-flex justify-content-between flex-wrap gap-2">
            <span>© <?php echo date('Y'); ?> HostelEase. All rights reserved.</span>
            <span>Built with ❤️ for students everywhere</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 700, once: true, offset: 60 });

    // Navbar scroll effect
    const nav = document.getElementById('mainNav');
    window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.scrollY > 60);
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            const target = document.querySelector(a.getAttribute('href'));
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
</script>
</body>
</html>
