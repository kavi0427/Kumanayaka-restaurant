/**
 * Main JavaScript file for Bella Vista Restaurant Website
 * Handles navigation, animations, form submissions, and interactive features
 */

// DOM Content Loaded Event
document.addEventListener('DOMContentLoaded', function() {
    initializeNavigation();
    initializeAnimations();
    initializeMenuFilters();
    initializeGalleryFilters();
    initializeContactForm();
    initializeGalleryModal();
    initializeSmoothScrolling();
});

/**
 * Navigation functionality
 */
function initializeNavigation() {
    const navbar = document.getElementById('navbar');
    const navToggle = document.getElementById('nav-toggle');
    const navMenu = document.getElementById('nav-menu');
    const navLinks = document.querySelectorAll('.nav-link');

    // Mobile menu toggle
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        // Close mobile menu when clicking on links
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navToggle.classList.remove('active');
                navMenu.classList.remove('active');
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!navbar.contains(e.target)) {
                navToggle.classList.remove('active');
                navMenu.classList.remove('active');
            }
        });
    }

    // Navbar scroll effect
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // Active link highlighting
    function updateActiveLink() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            }
        });
    }
    updateActiveLink();
}

/**
 * Intersection Observer for animations
 */
function initializeAnimations() {
    const animatedElements = document.querySelectorAll('.animate-fade-in');
    
    if (animatedElements.length === 0) return;

    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    animatedElements.forEach(element => {
        observer.observe(element);
    });
}

/**
 * Menu filtering functionality
 */
function initializeMenuFilters() {
    const filterButtons = document.querySelectorAll('.category-filters .filter-btn');
    const menuCategories = document.querySelectorAll('.menu-category');

    if (filterButtons.length === 0 || menuCategories.length === 0) return;

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetCategory = this.getAttribute('data-category');

            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            // Show/hide menu categories
            menuCategories.forEach(category => {
                const categoryName = category.getAttribute('data-category');
                
                if (targetCategory === 'all' || categoryName === targetCategory) {
                    category.style.display = 'block';
                    // Animate items
                    const menuItems = category.querySelectorAll('.menu-item');
                    menuItems.forEach((item, index) => {
                        setTimeout(() => {
                            item.style.opacity = '1';
                            item.style.transform = 'translateY(0)';
                        }, index * 100);
                    });
                } else {
                    category.style.display = 'none';
                }
            });
        });
    });

    // Initialize first load animation
    setTimeout(() => {
        const visibleItems = document.querySelectorAll('.menu-item');
        visibleItems.forEach((item, index) => {
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, index * 50);
        });
    }, 100);
}

/**
 * Gallery filtering functionality
 */
function initializeGalleryFilters() {
    const filterButtons = document.querySelectorAll('.gallery-filters .filter-btn');
    const galleryItems = document.querySelectorAll('.gallery-item');

    if (filterButtons.length === 0 || galleryItems.length === 0) return;

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetFilter = this.getAttribute('data-filter');

            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            // Filter gallery items
            galleryItems.forEach((item, index) => {
                const itemCategory = item.getAttribute('data-category');
                
                if (targetFilter === 'all' || itemCategory === targetFilter) {
                    item.style.display = 'block';
                    // Animate item appearance
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'scale(1)';
                    }, index * 50);
                } else {
                    item.style.display = 'none';
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.8)';
                }
            });
        });
    });

    // Initialize gallery items
    galleryItems.forEach(item => {
        item.style.transition = 'all 0.3s ease';
        item.style.opacity = '1';
        item.style.transform = 'scale(1)';
    });
}

/**
 * Contact form handling
 */
function initializeContactForm() {
    const contactForm = document.getElementById('contactForm');
    const formMessage = document.getElementById('formMessage');

    if (!contactForm) return;

    // Set minimum date to today
    const dateInput = document.getElementById('date');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }

    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Get form data
        const formData = new FormData(this);
        
        // Show loading state
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.textContent = 'Sending...';
        submitButton.disabled = true;

        // Simulate form submission (replace with actual PHP endpoint)
        fetch('php/contact-handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showFormMessage('Thank you! Your reservation request has been sent successfully. We will contact you soon to confirm your booking.', 'success');
                contactForm.reset();
            } else {
                showFormMessage(data.message || 'There was an error sending your message. Please try again or call us directly.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showFormMessage('There was an error sending your message. Please try again or call us directly at (555) 123-4567.', 'error');
        })
        .finally(() => {
            // Reset button state
            submitButton.textContent = originalText;
            submitButton.disabled = false;
        });
    });

    function showFormMessage(message, type) {
        if (!formMessage) return;
        
        formMessage.textContent = message;
        formMessage.className = `form-message ${type}`;
        formMessage.style.display = 'block';

        // Hide message after 5 seconds
        setTimeout(() => {
            formMessage.style.display = 'none';
        }, 5000);

        // Scroll to message
        formMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Real-time form validation
    const requiredFields = contactForm.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        field.addEventListener('blur', function() {
            validateField(this);
        });

        field.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                validateField(this);
            }
        });
    });

    function validateField(field) {
        const isValid = field.checkValidity();
        
        if (isValid) {
            field.classList.remove('error');
            field.style.borderColor = 'hsl(var(--primary))';
        } else {
            field.classList.add('error');
            field.style.borderColor = 'hsl(0, 100%, 50%)';
        }

        return isValid;
    }
}

/**
 * Gallery modal functionality
 */
function initializeGalleryModal() {
    const galleryItems = document.querySelectorAll('.gallery-item');
    const galleryModal = document.getElementById('galleryModal');
    const modalClose = document.getElementById('modalClose');
    const modalTitle = document.getElementById('modalTitle');
    const modalDescription = document.getElementById('modalDescription');

    if (!galleryModal) return;

    // Open modal when clicking gallery items
    galleryItems.forEach(item => {
        item.addEventListener('click', function() {
            const overlay = this.querySelector('.gallery-overlay');
            if (overlay) {
                const title = overlay.querySelector('h3')?.textContent || 'Gallery Image';
                const description = overlay.querySelector('p')?.textContent || 'Beautiful moment captured at Bella Vista';
                
                modalTitle.textContent = title;
                modalDescription.textContent = description;
            }

            galleryModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            // Animate modal appearance
            setTimeout(() => {
                galleryModal.style.opacity = '1';
            }, 10);
        });
    });

    // Close modal
    function closeModal() {
        galleryModal.style.opacity = '0';
        setTimeout(() => {
            galleryModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }, 300);
    }

    if (modalClose) {
        modalClose.addEventListener('click', closeModal);
    }

    // Close modal when clicking outside content
    galleryModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && galleryModal.style.display === 'block') {
            closeModal();
        }
    });
}

/**
 * Smooth scrolling for anchor links
 */
function initializeSmoothScrolling() {
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                
                const offsetTop = targetElement.offsetTop - 80; // Account for fixed navbar
                
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Hero scroll button
    const heroScroll = document.querySelector('.hero-scroll');
    if (heroScroll) {
        heroScroll.addEventListener('click', function() {
            const featuresSection = document.getElementById('features');
            if (featuresSection) {
                const offsetTop = featuresSection.offsetTop - 80;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    }
}

/**
 * Utility functions
 */

// Format phone number input
function formatPhoneNumber(phoneNumber) {
    const cleaned = phoneNumber.replace(/\D/g, '');
    const match = cleaned.match(/^(\d{3})(\d{3})(\d{4})$/);
    if (match) {
        return '(' + match[1] + ') ' + match[2] + '-' + match[3];
    }
    return phoneNumber;
}

// Validate email format
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Format date for display
function formatDate(dateString) {
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Debounce function for performance optimization
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Loading state management
function showLoading(element, text = 'Loading...') {
    element.style.opacity = '0.6';
    element.style.pointerEvents = 'none';
    if (element.textContent !== undefined) {
        element.dataset.originalText = element.textContent;
        element.textContent = text;
    }
}

function hideLoading(element) {
    element.style.opacity = '1';
    element.style.pointerEvents = 'auto';
    if (element.dataset.originalText) {
        element.textContent = element.dataset.originalText;
        delete element.dataset.originalText;
    }
}

// Error handling for images
document.addEventListener('error', function(e) {
    if (e.target.tagName === 'IMG') {
        e.target.style.display = 'none';
        console.warn('Image failed to load:', e.target.src);
    }
}, true);

// Performance monitoring
if ('performance' in window) {
    window.addEventListener('load', function() {
        setTimeout(() => {
            const perfData = performance.getEntriesByType('navigation')[0];
            if (perfData && perfData.loadEventEnd > 0) {
                console.log('Page load time:', Math.round(perfData.loadEventEnd - perfData.navigationStart) + 'ms');
            }
        }, 0);
    });
}

// Service Worker registration for PWA capabilities (optional)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        // Uncomment to enable service worker
        // navigator.serviceWorker.register('/sw.js')
        //     .then(registration => console.log('SW registered'))
        //     .catch(error => console.log('SW registration failed'));
    });
}
