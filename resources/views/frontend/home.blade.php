@extends('frontend.layouts.app')

@section('title', 'PrintWorks - Run sales, stock, and branches from one cloud workspace')

@push('styles')
<style>
    .hero {
        padding: 80px 0 60px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .badge {
        display: inline-block;
        padding: 6px 16px;
        background: rgba(6, 182, 212, 0.1);
        border: 1px solid rgba(6, 182, 212, 0.2);
        color: var(--accent-cyan);
        border-radius: var(--radius-full);
        font-size: 0.85rem;
        font-weight: 600;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-bottom: 30px;
    }

    .hero-title {
        font-size: 3.8rem;
        font-weight: 800;
        line-height: 1.1;
        margin-bottom: 24px;
        max-width: 900px;
        letter-spacing: -1px;
    }

    .hero-desc {
        font-size: 1.15rem;
        color: var(--text-secondary);
        max-width: 700px;
        margin-bottom: 40px;
        line-height: 1.6;
    }

    .hero-actions {
        display: flex;
        gap: 16px;
        margin-bottom: 80px;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        margin-top: 40px;
    }

    .feature-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border-glass);
        padding: 40px 30px;
        border-radius: var(--radius-lg);
        transition: var(--transition);
        text-align: left;
    }

    .feature-card:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(6, 182, 212, 0.3);
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .feature-icon {
        width: 50px;
        height: 50px;
        background: rgba(6, 182, 212, 0.1);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent-cyan);
        font-size: 1.5rem;
        margin-bottom: 24px;
    }

    .feature-title {
        font-size: 1.3rem;
        margin-bottom: 12px;
        font-weight: 600;
    }

    .feature-desc {
        color: var(--text-secondary);
        font-size: 0.95rem;
        line-height: 1.6;
    }

    @media (max-width: 992px) {
        .hero-title { font-size: 3rem; }
        .features-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 768px) {
        .hero-title { font-size: 2.5rem; }
        .hero-actions { flex-direction: column; width: 100%; max-width: 300px; }
        .hero-actions .btn { width: 100%; }
        .features-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="container animate-fade-up">
    <section class="hero">
        <div class="badge">Software as a Service</div>
        
        <h1 class="hero-title">
            Run sales, stock, and branches from <span class="text-gradient-accent">one cloud workspace</span>
        </h1>
        
        <p class="hero-desc">
            PrintWorks is a subscription-friendly business suite: point of sale, inventory, purchases, and reporting—available securely in the browser, without installing heavy on-premise ERP servers.
        </p>
        
        <div class="hero-actions">
            <a href="/login" class="btn btn-primary" style="padding: 14px 36px; font-size: 1.05rem;">Login</a>
            <a href="/business/register" class="btn btn-outline" style="padding: 14px 36px; font-size: 1.05rem;">Register Now</a>
        </div>
        
        <h3 style="color: var(--text-secondary); font-weight: 500; font-size: 1.2rem; margin-top: 20px;">Built for teams that outgrow spreadsheets</h3>
        
        <div class="features-grid">
            <div class="feature-card delay-100 animate-fade-up">
                <div class="feature-icon">
                    <i class="fa-solid fa-cloud"></i>
                </div>
                <h3 class="feature-title">True cloud SaaS</h3>
                <p class="feature-desc">Sign in from the shop, warehouse, or office. Updates and backups are handled centrally—your data stays organized in one place.</p>
            </div>
            
            <div class="feature-card delay-200 animate-fade-up">
                <div class="feature-icon">
                    <i class="fa-solid fa-rotate"></i>
                </div>
                <h3 class="feature-title">POS that stays in sync</h3>
                <p class="feature-desc">Sell at the counter with live stock awareness. Reduce overselling and reconcile faster at the end of the day.</p>
            </div>
            
            <div class="feature-card delay-300 animate-fade-up">
                <div class="feature-icon">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <h3 class="feature-title">Multi-location ready</h3>
                <p class="feature-desc">Designed for growing chains: locations, users, and permissions scale with your business—not with server closets.</p>
            </div>
        </div>
    </section>
</div>
@endsection
