/**
 * Main Application JavaScript
 * Handles core functionality and initialization
 */

class RaffleApp {
    constructor() {
        this.raffles = [];
        this.categories = [];
        this.specialRaffles = {};
        this.groupedRaffles = {};
        this.modal = null;
        this.grid = null;
        this.catBtns = [];
        this.catBar = null;
        this.underline = null;
        this.fadeLeft = null;
        this.fadeRight = null;
        this.wishlistManager = null;
        
        this.init();
    }
    
    init() {
        // Initialize DOM hooks
        this.grid = document.getElementById('raffle-grid');
        this.catBtns = [...document.querySelectorAll('.category-btn')];
        this.catBar = document.getElementById('category-bar');
        this.modal = document.getElementById('product-modal');
        this.underline = this.catBar?.querySelector('.cat-underline');
        this.fadeLeft = this.catBar?.querySelector('.fade-left');
        this.fadeRight = this.catBar?.querySelector('.fade-right');
        
        // Initialize wishlist manager
        if (window.WishlistManager) {
            this.wishlistManager = new WishlistManager();
        }
        
        // Initialize banner slider
        this.initializeBannerSlider();
        
        // Bind events
        this.bindEvents();
        
        // Initial render
        this.render('All');
    }
    
    setData(raffles, categories, specialRaffles, groupedRaffles) {
        this.raffles = raffles;
        this.categories = categories;
        this.specialRaffles = specialRaffles;
        this.groupedRaffles = groupedRaffles;
    }
    
    bindEvents() {
        // Modal close button
        const closeBtn = document.getElementById('modal-close');
        closeBtn?.addEventListener('click', () => this.closeModal());
        
        // Category buttons
        this.catBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const category = btn.dataset.category;
                this.setActiveCategory(btn);
                this.render(category);
            });
        });
        
        // Brand modal events
        const brandModal = document.getElementById('brand-modal');
        const brandModalClose = document.getElementById('brand-modal-close');
        brandModalClose?.addEventListener('click', () => {
            brandModal?.classList.add('hidden');
        });
        
        // Close modals on backdrop click
        this.modal?.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.closeModal();
            }
        });
        
        brandModal?.addEventListener('click', (e) => {
            if (e.target === brandModal) {
                brandModal.classList.add('hidden');
            }
        });
        
        // Escape key to close modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
                brandModal?.classList.add('hidden');
            }
        });
        
        // Responsive category bar
        this.setupResponsiveCategoryBar();
    }
    
    setupResponsiveCategoryBar() {
        if (!this.catBar) return;
        
        const updateFadeEffects = () => {
            const scrollLeft = this.catBar.scrollLeft;
            const scrollWidth = this.catBar.scrollWidth;
            const clientWidth = this.catBar.clientWidth;
            
            if (this.fadeLeft) {
                this.fadeLeft.style.opacity = scrollLeft > 0 ? '1' : '0';
            }
            if (this.fadeRight) {
                this.fadeRight.style.opacity = scrollLeft < scrollWidth - clientWidth ? '1' : '0';
            }
        };
        
        this.catBar.addEventListener('scroll', updateFadeEffects);
        window.addEventListener('resize', updateFadeEffects);
        setTimeout(updateFadeEffects, 100);
    }
    
    setActiveCategory(activeBtn) {
        this.catBtns.forEach(btn => btn.classList.remove('active'));
        activeBtn.classList.add('active');
        
        // Update underline position
        if (this.underline) {
            const rect = activeBtn.getBoundingClientRect();
            const barRect = this.catBar.getBoundingClientRect();
            this.underline.style.transform = `translateX(${rect.left - barRect.left}px)`;
            this.underline.style.width = `${rect.width}px`;
        }
    }
    
    render(category) {
        if (!this.grid) return;
        
        // Show loading state
        this.grid.innerHTML = Array(8).fill('<div class="animate-pulse bg-gray-200 rounded-3xl aspect-[3/4]"></div>').join('');
        
        setTimeout(() => {
            if (this.specialRaffles[category]) {
                // Special categories - show all items in grid
                const items = this.specialRaffles[category].filter(r => r.sold < r.total);
                this.grid.innerHTML = items.map(r => this.renderCard(r)).join('');
            } else {
                // Regular categories - show by brands
                this.grid.innerHTML = this.renderBrandSections(category);
            }
            this.bindProductCards();
        }, 150);
    }
    
    renderCard(raffle) {
        const pct = Math.round((raffle.sold / raffle.total) * 100);
        const remain = raffle.total - raffle.sold;
        const almostOut = remain > 0 && remain <= 50;
        const isUrgent = raffle.hours_remaining <= 24;
        const isSellingFast = pct >= 70;
        const isEarlyBird = pct < 30;
        
        // Enhanced badge system (blue/yellow/red only)
        let badges = [];
        if (raffle.badges && raffle.badges.length > 0) {
            badges = raffle.badges.map(badge => {
                const colors = {
                    red: 'bg-red-500 text-white',
                    yellow: 'bg-yellow-400 text-gray-900',
                    blue: 'bg-ps-blue text-white',
                };
                // Only allow red, yellow, blue
                let colorClass = colors[badge.color] || colors.blue;
                // URGENT always red, RM1 ONLY always yellow
                if (badge.text === 'URGENT' || badge.text === 'Closing Soon') colorClass = colors.red;
                if (badge.text === 'RM1 ONLY!') colorClass = colors.yellow;
                return `<span class="liquid-badge px-3 py-1 rounded-full text-xs font-bold shadow-sm ${colorClass} mb-1 flex items-center gap-1">${badge.text}</span>`;
            });
        }
        
        // RM1 Strategy triggers (blue/yellow/red only)
        const strategyTriggers = [];
        if (isUrgent) strategyTriggers.push('<span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-semibold flex items-center gap-1"><i class="fa-solid fa-clock"></i> Closing Soon</span>');
        if (isSellingFast) strategyTriggers.push('<span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-semibold flex items-center gap-1"><i class="fa-solid fa-bolt"></i> Selling Fast</span>');
        if (isEarlyBird) strategyTriggers.push('<span class="bg-yellow-50 text-yellow-700 px-2 py-1 rounded-full text-xs font-semibold flex items-center gap-1"><i class="fa-solid fa-seedling"></i> Early Bird</span>');
        if (almostOut) strategyTriggers.push(`<span class="bg-ps-blue/10 text-ps-blue px-2 py-1 rounded-full text-xs font-semibold flex items-center gap-1"><i class="fa-solid fa-fire"></i> Only ${remain} left</span>`);
        
        // Check if this raffle is in user's wishlist
        const wishlist = JSON.parse(localStorage.getItem('user_wishlist') || '[]');
        const isWishlisted = wishlist.includes(raffle.id);
        const heartIcon = isWishlisted ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
        const heartColor = isWishlisted ? 'text-red-500' : 'text-gray-400 hover:text-red-500';
        
        return `
        <article class="raffle-card liquid-glass-card floating-card group relative flex flex-col rounded-3xl overflow-hidden">
            <!-- Heart Icon (Top Right) -->
            <button class="wishlist-btn absolute top-4 right-4 z-20 bg-white/80 backdrop-blur-sm hover:bg-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg transition-all duration-300 transform hover:scale-110" 
                    data-id="${raffle.id}" 
                    data-raffle-title="${raffle.title}"
                    onclick="window.raffleApp.wishlistManager?.toggleWishlist(${raffle.id}, '${raffle.title}')"
                    title="${isWishlisted ? 'Remove from wishlist' : 'Add to wishlist'}">
                <i class="${heartIcon} ${heartColor} text-lg transition-all duration-300"></i>
            </button>
            
            <!-- Badges -->
            ${badges.length > 0 ? `<div class="absolute top-4 left-4 z-10 flex flex-col gap-1">${badges.slice(0, 2).join('')}</div>` : ''}
            
            <!-- Image -->
            <div class="relative flex items-center justify-center mt-8 mb-4">
                <div class="liquid-image-container h-24 w-24 rounded-2xl grid place-content-center">
                    <div class="product-img-container" data-product-id="${raffle.id}">
                        <img src="${raffle.image_url || 'images/placeholder.jpg'}" alt="${raffle.title}" onerror="this.onerror=null;this.src='images/placeholder.jpg';" class="product-img object-cover h-20 w-20 rounded-xl transition-transform duration-200 group-hover:scale-105">
                        <span class="info-icon-overlay"><i class="fa-solid fa-circle-info"></i></span>
                    </div>
                </div>
            </div>
            
            <!-- Body -->
            <div class="flex-1 flex flex-col px-4 w-full">
                <p class="text-xs text-ps-silver mb-1 tracking-wide text-center font-medium">${raffle.brand_name}</p>
                <h3 class="text-lg font-heading font-extrabold text-ps-text text-center leading-snug mb-2 line-clamp-2">${raffle.title}</h3>
                
                <!-- Strategy Triggers -->
                ${strategyTriggers.length > 0 ? `<div class="flex flex-wrap gap-1 justify-center mb-2">${strategyTriggers.join('')}</div>` : ''}
                
                <!-- Progress Bar -->
                <div class="flex items-center gap-2 mb-1">
                    <div class="liquid-progress flex-1 h-3 rounded-full overflow-hidden relative">
                        <div class="h-full bg-gradient-to-r from-ps-blue to-ps-light rounded-full shadow-ps transition-all duration-700" style="width:${pct}%"></div>
                    </div>
                    <span class="text-xs font-bold text-ps-blue ml-2 min-w-[32px]">${pct}%</span>
                </div>
                <p class="text-xs text-ps-silver mb-1 text-center font-medium">${raffle.sold} of ${raffle.total} sold${almostOut ? `<span class="ml-2 text-ps-blue font-bold">Only ${remain} left!</span>` : ''}</p>
                
                <!-- Social Proof -->
                ${raffle.social_proof_enabled ? `<div class="text-center mb-3"><span class="text-xs text-ps-blue bg-ps-blue/10 rounded-full px-2 py-1 font-semibold flex items-center gap-1 justify-center"><i class="fa-solid fa-users"></i> ${Math.floor(Math.random() * 50) + 10} people viewing</span></div>` : ''}
            </div>
            
            <!-- Quick Buy & Custom Buy -->
            <div class="w-full px-4 py-3 bg-slate-50/60 backdrop-blur border-t border-slate-100">
                <div class="flex gap-2">
                    <input type="number" id="qty-${raffle.id}" value="1" min="1" max="${remain}" class="w-16 text-center text-sm font-bold text-ps-blue border border-ps-blue/30 rounded-xl py-1 focus:ring-2 focus:ring-ps-blue/30 outline-none"/>
                    <button onclick="window.raffleApp.customBuyTickets(${raffle.id}, document.getElementById('qty-${raffle.id}').value, '${raffle.title}')" class="glass-button flex-1 flex items-center justify-center gap-1 bg-ps-blue text-white rounded-xl font-heading font-bold text-sm py-1 shadow hover:bg-ps-light/90 transition"><i class="fa-solid fa-ticket"></i> Buy</button>
                </div>
            </div>
        </article>`;
    }
    
    renderBrandSections(category) {
        if (!this.groupedRaffles[category]) {
            return '<div class="col-span-full text-center text-gray-500 py-8">No raffles available in this category</div>';
        }
        
        let html = '';
        const brands = this.groupedRaffles[category];
        
        // Check if this category has brand layout disabled (will have "All Items" group)
        const hasAllItemsGroup = brands.hasOwnProperty('All Items');
        
        Object.keys(brands).forEach(brand => {
            const brandData = brands[brand];
            const brandRaffles = brandData.raffles.filter(r => r.sold < r.total);
            if (brandRaffles.length === 0) return;
            
            if (hasAllItemsGroup && brand === 'All Items') {
                // For disabled brand layout, show all items in a simple grid without brand headers or view more
                html += `
                    <div class="col-span-full">
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6 lg:gap-8">
                            ${brandRaffles.map(r => this.renderCard(r)).join('')}
                        </div>
                    </div>
                `;
            } else {
                // For enabled brand layout, show with brand headers and view more functionality
                const displayItems = brandRaffles.slice(0, 5);
                const hasMore = brandRaffles.length > 4;
                const isFeatured = brandData.featured;
                
                html += `
                    <div class="col-span-full mb-8">
                        <!-- Brand Header -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                ${this.getBrandImage(brand, brandRaffles[0])}
                                <h3 class="font-heading text-xl font-bold text-ps-text">${brand}</h3>
                                ${isFeatured ? '<span class="bg-yellow-400 text-yellow-900 px-2 py-1 rounded-full text-xs font-semibold">⭐ Featured</span>' : ''}
                                <span class="bg-ps-blue/10 text-ps-blue px-2 py-1 rounded-full text-xs font-semibold">
                                    ${brandRaffles.length} item${brandRaffles.length > 1 ? 's' : ''}
                                </span>
                            </div>
                            ${hasMore ? `
                                <button onclick="window.raffleApp.openBrandModal('${category}', '${brand}')" 
                                        class="text-ps-blue hover:text-ps-light font-semibold text-sm flex items-center gap-1 transition">
                                    View More <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            ` : ''}
                        </div>
                        
                        <!-- Brand Items Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6 lg:gap-8">
                            ${displayItems.map(r => this.renderCard(r)).join('')}
                        </div>
                    </div>
                `;
            }
        });
        
        return html;
    }
    
    getBrandImage(brandName, raffleData) {
        const brandImageUrl = raffleData?.brand_image_url || '';
        
        if (brandImageUrl) {
            return `<img src="/raffle-demo/${brandImageUrl}" alt="${brandName} logo" class="w-8 h-8 object-contain rounded-lg border border-gray-200">`;
        } else {
            return `<div class="w-8 h-8 bg-gray-100 rounded-lg border border-gray-200 flex items-center justify-center">
                <i class="fa-solid fa-building text-gray-400 text-sm"></i>
            </div>`;
        }
    }
    
    bindProductCards() {
        const productCards = document.querySelectorAll('.product-img-container');
        productCards.forEach(card => {
            card.addEventListener('click', () => {
                const productId = parseInt(card.dataset.productId);
                const raffle = this.raffles.find(r => r.id === productId);
                if (raffle) {
                    this.openProductModal(raffle);
                }
            });
        });
    }
    
    openProductModal(raffle) {
        if (!this.modal) return;
        
        // Populate modal with raffle data
        document.getElementById('modal-img').src = raffle.image_url || 'images/placeholder.jpg';
        document.getElementById('modal-title').textContent = raffle.title;
        document.getElementById('modal-desc').textContent = raffle.description || '';
        document.getElementById('modal-price').textContent = `RM${raffle.price.toFixed(2)}`;
        document.getElementById('modal-sold').textContent = `${raffle.sold} of ${raffle.total} sold`;
        
        // Update category info
        const categorySpan = document.getElementById('modal-category').querySelector('span');
        const categoryIcon = document.getElementById('modal-category-icon');
        categorySpan.textContent = raffle.category;
        categoryIcon.className = `fa-solid ${raffle.category_icon || 'fa-tag'}`;
        
        // Show modal
        this.modal.classList.remove('hidden');
    }
    
    closeModal() {
        if (this.modal) {
            this.modal.classList.add('hidden');
        }
    }
    
    openBrandModal(category, brand) {
        const brandModal = document.getElementById('brand-modal');
        if (!brandModal) return;
        
        const brandData = this.groupedRaffles[category]?.[brand];
        if (!brandData) return;
        
        // Update modal title and count
        document.getElementById('brand-modal-title').textContent = `${brand} Products`;
        document.getElementById('brand-modal-count').textContent = `${brandData.raffles.length} items`;
        
        // Populate grid
        const grid = document.getElementById('brand-modal-grid');
        grid.innerHTML = brandData.raffles.map(r => this.renderCard(r)).join('');
        
        // Show modal
        brandModal.classList.remove('hidden');
        
        // Bind product cards in modal
        this.bindProductCards();
    }
    
    customBuyTickets(raffleId, quantity, title) {
        const qty = parseInt(quantity);
        if (qty <= 0) return;
        
        // This would normally integrate with the payment system
        console.log(`Buying ${qty} tickets for raffle ${raffleId}: ${title}`);
        // For now, just show a placeholder alert
        alert(`Added ${qty} tickets for "${title}" to cart!`);
    }
    
    initializeBannerSlider() {
        const slides = document.querySelectorAll('.banner-slide');
        const dots = document.querySelectorAll('.slide-dot');
        const prevBtn = document.getElementById('prevSlide');
        const nextBtn = document.getElementById('nextSlide');
        
        if (slides.length <= 1) return;
        
        let currentSlide = 0;
        let slideInterval;
        
        const showSlide = (index) => {
            // Hide all slides
            slides.forEach(slide => {
                slide.classList.remove('active', 'fade-in');
                slide.classList.add('fade-out');
            });
            
            // Remove active class from all dots
            dots.forEach(dot => dot.classList.remove('active'));
            
            // Show current slide
            slides[index].classList.remove('fade-out');
            slides[index].classList.add('active', 'fade-in');
            
            // Activate current dot
            if (dots[index]) {
                dots[index].classList.add('active');
            }
            
            currentSlide = index;
        };
        
        const nextSlide = () => {
            const next = (currentSlide + 1) % slides.length;
            showSlide(next);
        };
        
        const prevSlide = () => {
            const prev = (currentSlide - 1 + slides.length) % slides.length;
            showSlide(prev);
        };
        
        // Event listeners for navigation
        prevBtn?.addEventListener('click', () => {
            prevSlide();
            resetInterval();
        });
        
        nextBtn?.addEventListener('click', () => {
            nextSlide();
            resetInterval();
        });
        
        // Event listeners for dots
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
                resetInterval();
            });
        });
        
        // Auto-advance slides
        const startInterval = () => {
            slideInterval = setInterval(nextSlide, 5000);
        };
        
        const resetInterval = () => {
            clearInterval(slideInterval);
            startInterval();
        };
        
        // Start auto-advance
        startInterval();
        
        // Pause on hover
        const slider = document.getElementById('bannerSlider');
        if (slider) {
            slider.addEventListener('mouseenter', () => {
                clearInterval(slideInterval);
            });
            
            slider.addEventListener('mouseleave', () => {
                startInterval();
            });
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                prevSlide();
                resetInterval();
            } else if (e.key === 'ArrowRight') {
                nextSlide();
                resetInterval();
            }
        });
    }
}

// Initialize the app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.raffleApp = new RaffleApp();
}); 