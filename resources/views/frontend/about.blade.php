@extends('frontend.layouts.app')

@section('title', 'About Us - PrintWorks')

@push('styles')
<style>
    .about-section {
        padding: 60px 0;
    }
    
    .about-header {
        text-align: center;
        max-width: 800px;
        margin: 0 auto 60px;
    }
    
    .about-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
    }
    
    .about-text {
        font-size: 1.1rem;
        color: var(--text-secondary);
        line-height: 1.8;
    }
    
    .about-text p {
        margin-bottom: 20px;
    }
    
    .about-image {
        position: relative;
        border-radius: var(--radius-lg);
        overflow: hidden;
        border: 1px solid var(--border-glass);
    }
    
    .about-image::after {
        content: '';
        display: block;
        padding-bottom: 75%;
    }
    
    .img-placeholder {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(6, 182, 212, 0.2) 0%, rgba(139, 92, 246, 0.1) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: rgba(255, 255, 255, 0.2);
    }
    
    .values-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        margin-top: 80px;
    }
    
    .value-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border-glass);
        padding: 40px 30px;
        border-radius: var(--radius-lg);
        text-align: center;
    }
    
    .value-card i {
        font-size: 2.5rem;
        color: var(--accent-cyan);
        margin-bottom: 20px;
    }

    @media (max-width: 992px) {
        .about-content { grid-template-columns: 1fr; }
        .values-grid { grid-template-columns: repeat(2, 1fr); }
    }
    
    @media (max-width: 768px) {
        .values-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="container animate-fade-up">
    <div class="about-section">
        <div class="about-header">
            <span class="section-subtitle">Our Story</span>
            <h1 class="section-title">Empowering businesses to <span class="text-gradient-accent">scale without limits</span></h1>
        </div>

        <div class="about-content">
            <div class="about-text">
                <p>
                    PrintWorks was built out of a simple observation: modern businesses outgrow basic tools fast, but traditional ERP systems are too clunky, expensive, and require significant on-premise hardware to maintain.
                </p>
                <p>
                    We set out to create a true cloud-native solution that bridges the gap. A subscription-friendly suite that offers the power of a full-fledged ERP with the simplicity and accessibility of modern SaaS platforms.
                </p>
                <p>
                    Today, PrintWorks serves thousands of businesses worldwide, helping them manage their sales, multi-location stock, purchases, and reporting from a single, secure cloud workspace.
                </p>
            </div>
            <div class="about-image">
                <div class="img-placeholder">
                    <i class="fa-solid fa-users-rectangle"></i>
                </div>
            </div>
        </div>

        <div class="values-grid">
            <div class="value-card delay-100 animate-fade-up">
                <i class="fa-solid fa-lightbulb"></i>
                <h3 style="font-size: 1.3rem; margin-bottom: 15px;">Innovation First</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem;">We constantly iterate on our platform, bringing enterprise-grade technology to businesses of all sizes.</p>
            </div>
            <div class="value-card delay-200 animate-fade-up">
                <i class="fa-solid fa-shield-halved"></i>
                <h3 style="font-size: 1.3rem; margin-bottom: 15px;">Secure & Reliable</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem;">Your data is your most valuable asset. We employ strict security protocols and automated backups to keep it safe.</p>
            </div>
            <div class="value-card delay-300 animate-fade-up">
                <i class="fa-solid fa-hands-holding-circle"></i>
                <h3 style="font-size: 1.3rem; margin-bottom: 15px;">Customer Centric</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem;">Our support team works around the clock to ensure you get the most out of your PrintWorks workspace.</p>
            </div>
        </div>
    </div>
</div>
@endsection
