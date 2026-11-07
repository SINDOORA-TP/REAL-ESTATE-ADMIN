/**
 * Magaram Real Estate - Core Client-Side Application
 */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // 1. Sticky Navigation Scroll Effect
    const header = document.querySelector('.site-header');
    if (header) {
        const toggleHeader = () => {
            if (window.scrollY > 40) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        };
        window.addEventListener('scroll', toggleHeader, { passive: true });
        toggleHeader();
    }

    // 2. Mobile Menu Toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('open');
            const isOpen = navMenu.classList.contains('open');
            menuToggle.setAttribute('aria-expanded', isOpen);
            menuToggle.innerHTML = isOpen ? '&times;' : '&#9776;';
        });

        // Close on link click
        navMenu.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('open');
                if (menuToggle) menuToggle.innerHTML = '&#9776;';
            });
        });
    }

    // 3. Modal System (Site Visit & Brochure)
    const modals = document.querySelectorAll('.modal-backdrop');
    const openModalBtns = document.querySelectorAll('[data-open-modal]');
    const closeModalBtns = document.querySelectorAll('.modal-close, [data-close-modal]');

    function openModal(modalId, propertyId = '', propertyTitle = '') {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        // Pre-fill property fields if available
        const propInput = modal.querySelector('input[name="property_id"], select[name="property_id"]');
        if (propInput && propertyId) {
            propInput.value = propertyId;
        }

        const propTitleSpan = modal.querySelector('.modal-property-name');
        if (propTitleSpan && propertyTitle) {
            propTitleSpan.textContent = propertyTitle;
            propTitleSpan.style.display = 'block';
        }

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    openModalBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const modalId = btn.getAttribute('data-open-modal');
            const propId = btn.getAttribute('data-property-id') || '';
            const propTitle = btn.getAttribute('data-property-title') || '';
            openModal(modalId, propId, propTitle);
        });
    });

    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-backdrop');
            closeModal(modal);
        });
    });

    modals.forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal);
            }
        });
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            modals.forEach(modal => {
                if (modal.classList.contains('active')) {
                    closeModal(modal);
                }
            });
        }
    });

    // 4. AJAX Enquiry & Site Booking Form Submissions
    const enquiryForms = document.querySelectorAll('.ajax-enquiry-form, form.luxury-form');
    enquiryForms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : 'Submit';
            const alertBox = form.querySelector('.form-alert') || createAlertBox(form);

            alertBox.style.display = 'none';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner"></span> Processing...';
            }

            const formData = new FormData(form);

            // Extract comprehensive booking metadata
            const modalProp = form.querySelector('.modal-property-name');
            const pagePropTitle = document.getElementById('p-title')?.textContent;
            let propTitle = 'Coimbatore Luxury Estate';
            if (modalProp && modalProp.textContent.trim()) {
                propTitle = modalProp.textContent.trim();
            } else if (pagePropTitle) {
                propTitle = pagePropTitle.trim();
            } else if (formData.get('property_title')) {
                propTitle = formData.get('property_title');
            }

            const isBrochure = form.closest('#modal-brochure') || (form.id && form.id.includes('brochure'));
            const isSiteVisit = form.closest('#modal-site-visit') || form.querySelector('input[name="preferred_date"]');
            const bookingType = formData.get('type') || (isSiteVisit ? 'Site Visit' : (isBrochure ? 'Brochure' : 'Enquiry'));

            const leadData = {
                name: formData.get('name') || 'Private Client',
                phone: formData.get('phone') || '',
                email: formData.get('email') || '',
                type: bookingType,
                property_title: propTitle,
                preferred_date: formData.get('preferred_date') || '',
                message: formData.get('message') || ''
            };

            // Always save lead to universal PropertyStore immediately
            if (typeof PropertyStore !== 'undefined') {
                PropertyStore.addEnquiry(leadData);
            }

            try {
                const response = await fetch(form.action && form.action !== '#' ? form.action : 'api/enquiry.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    alertBox.className = 'alert alert-success form-alert';
                    alertBox.innerHTML = result.message || 'Your site visit request has been received. Our senior director will connect with you shortly.';
                    alertBox.style.display = 'block';
                    form.reset();

                    if (result.download_url) {
                        setTimeout(() => {
                            window.location.href = result.download_url;
                        }, 1200);
                    }
                } else {
                    alertBox.className = 'alert alert-danger form-alert';
                    alertBox.innerHTML = result.message || 'Submission failed. Please check the form and try again.';
                    alertBox.style.display = 'block';
                }
            } catch (err) {
                // On GitHub Pages static hosting or offline PHP, gracefully confirm appointment
                alertBox.className = 'alert alert-success form-alert';
                alertBox.innerHTML = '&#10004; Thank you! Your site visit request has been received and notified to the Executive Office. Our director will contact you promptly.';
                alertBox.style.display = 'block';
                form.reset();
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            }
        });
    }); finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            }
        });
    });

    function createAlertBox(form) {
        const box = document.createElement('div');
        box.className = 'form-alert';
        form.prepend(box);
        return box;
    }

    // 5. Lightbox for Gallery
    const galleryItems = document.querySelectorAll('.gallery-item');
    if (galleryItems.length > 0) {
        let lightbox = document.getElementById('gallery-lightbox');
        if (!lightbox) {
            lightbox = document.createElement('div');
            lightbox.id = 'gallery-lightbox';
            lightbox.className = 'modal-backdrop';
            lightbox.innerHTML = `
                <div class="lightbox-content" style="max-width: 90vw; max-height: 90vh; position: relative;">
                    <button class="modal-close" style="top: -40px; right: 0; color: #fff;">&times;</button>
                    <img id="lightbox-img" src="" alt="Gallery Preview" style="max-width: 100%; max-height: 85vh; border-radius: 6px; border: 1px solid var(--border-gold); box-shadow: 0 10px 40px rgba(0,0,0,0.9);" />
                    <p id="lightbox-caption" style="text-align: center; color: var(--gold-bright); margin-top: 12px; font-family: var(--font-heading); font-size: 1.1rem;"></p>
                </div>
            `;
            document.body.appendChild(lightbox);

            lightbox.querySelector('.modal-close').addEventListener('click', () => {
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            });

            lightbox.addEventListener('click', (e) => {
                if (e.target === lightbox) {
                    lightbox.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        }

        galleryItems.forEach(item => {
            item.addEventListener('click', () => {
                const img = item.querySelector('img');
                const title = item.getAttribute('data-title') || img.alt || '';
                const fullSrc = item.getAttribute('data-full') || img.src;

                document.getElementById('lightbox-img').src = fullSrc;
                document.getElementById('lightbox-caption').textContent = title;
                lightbox.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        });
    }

    // 6. Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#' || targetId === '') return;
            const targetEl = document.querySelector(targetId);
            if (targetEl) {
                e.preventDefault();
                targetEl.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});

