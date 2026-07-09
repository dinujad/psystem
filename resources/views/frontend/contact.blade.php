@extends('frontend.layouts.app')

@section('title', 'Contact Us - PrintWorks')

@push('styles')
<style>
    .contact-section {
        padding: 60px 0;
    }
    
    .contact-header {
        text-align: center;
        max-width: 800px;
        margin: 0 auto 60px;
    }
    
    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .contact-info {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    
    .info-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border-glass);
        padding: 30px;
        border-radius: var(--radius-lg);
        display: flex;
        gap: 20px;
    }
    
    .info-icon {
        width: 50px;
        height: 50px;
        background: rgba(6, 182, 212, 0.1);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent-cyan);
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    
    .info-details h3 {
        font-size: 1.2rem;
        margin-bottom: 8px;
    }
    
    .info-details p {
        color: var(--text-secondary);
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    .contact-form-container {
        background: var(--bg-card);
        border: 1px solid var(--border-glass);
        padding: 40px;
        border-radius: var(--radius-lg);
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-size: 0.9rem;
        color: var(--text-secondary);
        font-weight: 500;
    }
    
    .form-control {
        width: 100%;
        padding: 14px 16px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--border-glass);
        border-radius: 8px;
        color: var(--text-primary);
        font-family: inherit;
        font-size: 1rem;
        transition: var(--transition);
        outline: none;
    }
    
    .form-control:focus {
        border-color: var(--accent-cyan);
        background: rgba(255, 255, 255, 0.05);
        box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.1);
    }
    
    textarea.form-control {
        resize: vertical;
        min-height: 120px;
    }

    @media (max-width: 768px) {
        .contact-grid { grid-template-columns: 1fr; }
        .contact-form-container { padding: 30px 20px; }
    }
</style>
@endpush

@section('content')
<div class="container animate-fade-up">
    <div class="contact-section">
        <div class="contact-header">
            <span class="section-subtitle">Get In Touch</span>
            <h1 class="section-title">We'd love to <span class="text-gradient-accent">hear from you</span></h1>
            <p class="section-desc">Whether you have a question about features, trials, pricing, need a demo, or anything else, our team is ready to answer all your questions.</p>
        </div>

        <div class="contact-grid">
            <div class="contact-info">
                <div class="info-card delay-100 animate-fade-up">
                    <div class="info-icon">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div class="info-details">
                        <h3>Headquarters</h3>
                        <p>123 Business Avenue, Tech District<br>Innovation City, 10010</p>
                    </div>
                </div>
                
                <div class="info-card delay-200 animate-fade-up">
                    <div class="info-icon">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <div class="info-details">
                        <h3>Email Us</h3>
                        <p>Support: support@nexapos.cloud<br>Sales: sales@nexapos.cloud</p>
                    </div>
                </div>
                
                <div class="info-card delay-300 animate-fade-up">
                    <div class="info-icon">
                        <i class="fa-brands fa-whatsapp"></i>
                    </div>
                    <div class="info-details">
                        <h3>WhatsApp Direct Connect</h3>
                        <p>Reach us instantly via WhatsApp for rapid assistance and live chat.<br>
                        <a href="#" style="color: var(--accent-cyan); margin-top: 5px; display: inline-block; font-weight: 500;">Chat Now &rarr;</a></p>
                    </div>
                </div>
            </div>

            <div class="contact-form-container delay-200 animate-fade-up">
                <form action="#" method="POST" onsubmit="event.preventDefault(); alert('Inquiry received. We will get back to you soon!');">
                    <div class="form-group">
                        <label class="form-label" for="name">Full Name</label>
                        <input type="text" id="name" class="form-control" placeholder="John Doe" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" id="email" class="form-control" placeholder="john@example.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="subject">Subject</label>
                        <select id="subject" class="form-control" required style="appearance: none;">
                            <option value="" disabled selected>Select an option</option>
                            <option value="sales">Sales Inquiry</option>
                            <option value="support">Technical Support</option>
                            <option value="demo">Request a Demo</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="message">Message</label>
                        <textarea id="message" class="form-control" placeholder="How can we help you?" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px;">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
