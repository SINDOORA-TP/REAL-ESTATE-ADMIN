/**
 * Magaram Real Estate - Sparkle & Particle Physics Engine
 * Design Spec Section 5:
 *  1. Hero gold dust: 60-80 floating gold particles drifting upward, twinkling through opacity & scale.
 *  2. Four-point star glints: randomly flashing 4-pointed micro stars.
 *  3. Cursor trail: subtle fading gold sparks following desktop pointer.
 *  4. Accessibility: respects prefers-reduced-motion, cuts count 50% on mobile, pauses on tab blur.
 */

(function () {
    'use strict';

    // Check user preference for reduced motion
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) {
        return; // Sparkle disabled for accessibility
    }

    const canvas = document.getElementById('hero-sparkle-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let width = (canvas.width = canvas.parentElement.offsetWidth);
    let height = (canvas.height = canvas.parentElement.offsetHeight);

    const isMobile = window.innerWidth < 768;
    const particleCount = isMobile ? 35 : 75;
    const particles = [];
    let animationFrameId = null;
    let isTabVisible = true;

    // Palette: Between Antique Gold #C6A15B and High-Gold #E5C98B
    const goldPalette = [
        { r: 198, g: 161, b: 91 },   // #C6A15B
        { r: 218, g: 182, b: 115 },  // #DAB673
        { r: 229, g: 201, b: 139 },  // #E5C98B
        { r: 245, g: 228, b: 180 },  // Shimmer accent
    ];

    function randomRange(min, max) {
        return Math.random() * (max - min) + min;
    }

    // Helper to draw a delicate 4-pointed star
    function drawFourPointStar(ctx, cx, cy, spikes, outerRadius, innerRadius, color, alpha) {
        let rot = (Math.PI / 2) * 3;
        let x = cx;
        let y = cy;
        let step = Math.PI / spikes;

        ctx.save();
        ctx.beginPath();
        ctx.moveTo(cx, cy - outerRadius);

        for (let i = 0; i < spikes; i++) {
            x = cx + Math.cos(rot) * outerRadius;
            y = cy + Math.sin(rot) * outerRadius;
            ctx.lineTo(x, y);
            rot += step;

            x = cx + Math.cos(rot) * innerRadius;
            y = cy + Math.sin(rot) * innerRadius;
            ctx.lineTo(x, y);
            rot += step;
        }

        ctx.lineTo(cx, cy - outerRadius);
        ctx.closePath();
        ctx.fillStyle = `rgba(${color.r}, ${color.g}, ${color.b}, ${alpha})`;
        ctx.shadowColor = `rgba(229, 201, 139, ${alpha * 0.8})`;
        ctx.shadowBlur = outerRadius * 2;
        ctx.fill();
        ctx.restore();
    }

    class GoldParticle {
        constructor() {
            this.reset(true);
        }

        reset(initial = false) {
            this.x = randomRange(0, width);
            this.y = initial ? randomRange(0, height) : height + randomRange(10, 40);
            this.radius = randomRange(0.8, 2.2);
            this.vy = randomRange(0.25, 0.75); // Slow upward drift
            this.vx = randomRange(-0.15, 0.15); // Gentle horizontal sway
            this.baseAlpha = randomRange(0.2, 0.75);
            this.alpha = this.baseAlpha;
            this.twinkleSpeed = randomRange(0.015, 0.04);
            this.twinklePhase = randomRange(0, Math.PI * 2);
            this.color = goldPalette[Math.floor(Math.random() * goldPalette.length)];
            // 15% chance to be a 4-point star glint
            this.isStar = Math.random() < 0.18;
            this.starGlint = 0;
            this.starGlintSpeed = randomRange(0.02, 0.06);
        }

        update() {
            this.y -= this.vy;
            this.x += this.vx;
            this.twinklePhase += this.twinkleSpeed;

            // Twinkle through opacity and scale
            const sinVal = Math.sin(this.twinklePhase);
            this.alpha = Math.max(0.08, this.baseAlpha + sinVal * 0.35);

            if (this.isStar) {
                this.starGlint += this.starGlintSpeed;
                if (this.starGlint > Math.PI * 2) {
                    this.starGlint = 0;
                }
            }

            // Recycle particle if off-screen top or side
            if (this.y < -20 || this.x < -20 || this.x > width + 20) {
                this.reset(false);
            }
        }

        draw() {
            if (this.isStar && Math.sin(this.starGlint) > 0.4) {
                const glintScale = Math.sin(this.starGlint);
                const outer = this.radius * 2.8 * glintScale;
                const inner = outer * 0.3;
                drawFourPointStar(ctx, this.x, this.y, 4, outer, inner, this.color, this.alpha);
            } else {
                ctx.save();
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(${this.color.r}, ${this.color.g}, ${this.color.b}, ${this.alpha})`;
                ctx.shadowColor = `rgba(${this.color.r}, ${this.color.g}, ${this.color.b}, 0.5)`;
                ctx.shadowBlur = this.radius * 2;
                ctx.fill();
                ctx.restore();
            }
        }
    }

    // Initialize particles
    for (let i = 0; i < particleCount; i++) {
        particles.push(new GoldParticle());
    }

    // Main animation loop
    function animate() {
        if (!isTabVisible) return;

        ctx.clearRect(0, 0, width, height);

        for (let i = 0; i < particles.length; i++) {
            particles[i].update();
            particles[i].draw();
        }

        // Draw cursor trail particles if any
        updateCursorTrail();

        animationFrameId = requestAnimationFrame(animate);
    }

    // Handle Window Resize
    function handleResize() {
        width = canvas.width = canvas.parentElement.offsetWidth;
        height = canvas.height = canvas.parentElement.offsetHeight;
    }

    window.addEventListener('resize', handleResize, { passive: true });

    // Page Visibility API - Pause loop when tab is hidden to conserve GPU/battery
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            isTabVisible = false;
            if (animationFrameId) cancelAnimationFrame(animationFrameId);
        } else {
            isTabVisible = true;
            animationFrameId = requestAnimationFrame(animate);
        }
    });

    // Cursor Trail (Desktop Only - Optional Subtle Sparkle)
    const cursorParticles = [];
    if (!isMobile) {
        let lastCursorSpawn = 0;
        window.addEventListener('mousemove', (e) => {
            const now = performance.now();
            if (now - lastCursorSpawn > 45) { // Throttle spark emission
                lastCursorSpawn = now;
                // Only spawn if mouse is near or inside hero
                const rect = canvas.getBoundingClientRect();
                if (e.clientY <= rect.bottom + 100) {
                    cursorParticles.push({
                        x: e.clientX,
                        y: e.clientY - rect.top,
                        vx: (Math.random() - 0.5) * 0.8,
                        vy: Math.random() * -1 - 0.4,
                        size: randomRange(1.2, 2.5),
                        life: 1.0,
                        decay: randomRange(0.02, 0.04),
                        color: goldPalette[Math.floor(Math.random() * goldPalette.length)]
                    });
                    if (cursorParticles.length > 25) {
                        cursorParticles.shift();
                    }
                }
            }
        }, { passive: true });
    }

    function updateCursorTrail() {
        for (let i = cursorParticles.length - 1; i >= 0; i--) {
            const p = cursorParticles[i];
            p.x += p.vx;
            p.y += p.vy;
            p.life -= p.decay;

            if (p.life <= 0) {
                cursorParticles.splice(i, 1);
                continue;
            }

            ctx.save();
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.size * p.life, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(${p.color.r}, ${p.color.g}, ${p.color.b}, ${p.life * 0.7})`;
            ctx.shadowColor = `rgba(229, 201, 139, ${p.life * 0.6})`;
            ctx.shadowBlur = 4;
            ctx.fill();
            ctx.restore();
        }
    }

    // Start particle animation
    animationFrameId = requestAnimationFrame(animate);
})();

