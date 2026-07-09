@extends('frontend.layouts.app')

@section('title', 'Pricing - PrintWorks')

@push('styles')
<style>
    .pricing-header {
        text-align: center;
        padding: 60px 0 40px;
    }
    
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        margin-top: 40px;
        max-width: 1000px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .pricing-card {
        background: var(--bg-card);
        border: 1px solid var(--border-glass);
        border-radius: var(--radius-lg);
        padding: 40px 30px;
        position: relative;
        transition: var(--transition);
        display: flex;
        flex-direction: column;
    }
    
    .pricing-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
    }
    
    .pricing-card.popular {
        border-color: var(--accent-cyan);
        box-shadow: 0 0 20px rgba(6, 182, 212, 0.15);
    }
    
    .popular-badge {
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--accent-cyan);
        color: #000;
        padding: 4px 12px;
        border-radius: var(--radius-full);
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .plan-name {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .plan-price {
        font-size: 3rem;
        font-family: var(--font-display);
        font-weight: 800;
        margin-bottom: 20px;
        color: var(--text-primary);
    }
    
    .plan-price span {
        font-size: 1rem;
        color: var(--text-secondary);
        font-weight: 400;
    }
    
    .plan-desc {
        color: var(--text-secondary);
        font-size: 0.95rem;
        margin-bottom: 30px;
        flex-grow: 1;
    }
    
    .plan-features {
        margin-bottom: 30px;
    }
    
    .plan-feature {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 15px;
        font-size: 0.95rem;
        color: var(--text-primary);
    }
    
    .plan-feature i {
        color: var(--accent-cyan);
    }

    @media (max-width: 992px) {
        .pricing-grid { grid-template-columns: repeat(2, 1fr); max-width: 700px; }
    }
    
    @media (max-width: 768px) {
        .pricing-grid { grid-template-columns: 1fr; max-width: 400px; }
    }
</style>
@endpush

@section('content')
<div class="container animate-fade-up">
    <div class="pricing-header">
        <span class="section-subtitle">Flexible Plans</span>
        <h1 class="section-title">Simple, transparent <span class="text-gradient-accent">pricing</span></h1>
        <p class="section-desc">Choose the plan that best fits your business size and needs. Upgrade or downgrade at any time.</p>
    </div>

    <div class="pricing-grid">
        <!-- Starter Plan -->
        <div class="pricing-card">
            <h3 class="plan-name">Starter</h3>
            <div class="plan-price">$29<span>/mo</span></div>
            <p class="plan-desc">Perfect for small single-store businesses just getting started.</p>
            
            <ul class="plan-features">
                <li class="plan-feature"><i class="fa-solid fa-check"></i> 1 Location</li>
                <li class="plan-feature"><i class="fa-solid fa-check"></i> 2 Users</li>
                <li class="plan-feature"><i class="fa-solid fa-check"></i> Basic POS</li>
                <li class="plan-feature"><i class="fa-solid fa-check"></i> Standard Inventory</li>
                <li class="plan-feature"><i class="fa-solid fa-check"></i> Email Support</li>
            </ul>
            
            <a href="/business/register" class="btn btn-outline" style="width: 100%;">Get Started</a>
        </div>

        <!-- Professional Plan -->
        <div class="pricing-card popular" style="transform: scale(1.05);">
            <div class="popular-badge">Most Popular</div>
            <h3 class="plan-name">Professional</h3>
            <div class="plan-price">$79<span>/mo</span></div>
            <p class="plan-desc">Ideal for growing businesses with multiple locations.</p>
            
            <ul class="plan-features">
                <li class="plan-feature"><i class="fa-solid fa-check"></i> Up to 5 Locations</li>
                <li class="plan-feature"><i class="fa-solid fa-check"></i> 10 Users</li>
                <li class="plan-feature"><i class="fa-solid fa-check"></i> Advanced POS Features</li>
                <li class="plan-feature"><i class="fa-solid fa-check"></i> Advanced Inventory</li>
                <li class="plan-feature"><i class="fa-solid fa-check"></i> Accounting Module</li>
                <li class="plan-feature"><i class="fa-solid fa-check"></i> Priority Support</li>
            </ul>
            
            <a href="/business/register" class="btn btn-primary" style="width: 100%;">Start Free Trial</a>
        </div>

        <!-- Enterprise Plan -->
        <div class="pricing-card">
            <h3 class="plan-name">Enterprise</h3>
            <div class="plan-price">$199<span>/mo</span></div>
            <p class="plan-desc">For large operations requiring unlimited scale.</p>
            
            <ul class="plan-features">
                <li class="plan-feature"><i class="fa-solid fa-check"></i> Unlimited Locations</li>
                <li class="plan-feature"><i class="fa-solid fa-check"></i> Unlimited Users</li>
                <li class="plan-feature"><i class="fa-solid fa-check"></i> API Access</li>
                <li class="plan-feature"><i class="fa-solid fa-check"></i> Custom Roles</li>
                <li class="plan-feature"><i class="fa-solid fa-check"></i> Dedicated Account Manager</li>
            </ul>
            
            <a href="/contact" class="btn btn-outline" style="width: 100%;">Contact Sales</a>
        </div>
    </div>
</div>
@endsection
