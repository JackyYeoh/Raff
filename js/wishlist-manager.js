/**
 * Wishlist Manager Module
 * Handles all wishlist functionality including add/remove, persistence, and UI updates
 */

class WishlistManager {
    constructor() {
        this.wishlist = [];
        this.currentUser = null;
        this.init();
    }
    
    init() {
        this.loadWishlist();
        this.updateWishlistCount();
        this.bindWishlistEvents();
    }
    
    setCurrentUser(user) {
        this.currentUser = user;
    }
    
    loadWishlist() {
        if (this.currentUser) {
            // Load from server if user is logged in
            fetch('api/wishlist.php?action=get')
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data) {
                        this.wishlist = result.data;
                        localStorage.setItem('user_wishlist', JSON.stringify(result.data));
                        this.updateWishlistCount(result.data.length);
                    }
                })
                .catch(error => {
                    console.warn('Could not load wishlist from server:', error);
                    this.loadFromLocalStorage();
                });
        } else {
            this.loadFromLocalStorage();
        }
    }
    
    loadFromLocalStorage() {
        const stored = localStorage.getItem('user_wishlist');
        this.wishlist = stored ? JSON.parse(stored) : [];
        this.updateWishlistCount(this.wishlist.length);
    }
    
    updateWishlistCount(count = null) {
        const actualCount = count !== null ? count : this.wishlist.length;
        const countElement = document.getElementById('wishlist-count');
        if (countElement) {
            countElement.textContent = actualCount;
            countElement.style.display = actualCount === 0 ? 'none' : 'block';
        }
    }
    
    bindWishlistEvents() {
        // Wishlist button in navbar
        const wishlistBtn = document.getElementById('wishlist-btn');
        if (wishlistBtn) {
            wishlistBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.showWishlist();
            });
        }
        
        // Global wishlist toggle function
        window.toggleWishlist = (raffleId, raffleTitle) => {
            this.toggleWishlist(raffleId, raffleTitle);
        };
    }
    
    toggleWishlist(raffleId, raffleTitle) {
        const isWishlisted = this.wishlist.includes(raffleId);
        
        if (isWishlisted) {
            this.removeFromWishlist(raffleId, raffleTitle);
        } else {
            this.addToWishlist(raffleId, raffleTitle);
        }
    }
    
    addToWishlist(raffleId, raffleTitle) {
        if (this.wishlist.includes(raffleId)) {
            return; // Already in wishlist
        }
        
        this.wishlist.push(raffleId);
        localStorage.setItem('user_wishlist', JSON.stringify(this.wishlist));
        this.updateWishlistCount();
        
        // Update heart icon
        this.updateHeartIcon(raffleId, true);
        
        // Show notification
        this.showNotification(`Added "${raffleTitle}" to wishlist`, 'success');
        
        // Save to server if user is logged in
        if (this.currentUser) {
            this.saveToServer();
        }
    }
    
    removeFromWishlist(raffleId, raffleTitle) {
        const index = this.wishlist.indexOf(raffleId);
        if (index === -1) {
            return; // Not in wishlist
        }
        
        this.wishlist.splice(index, 1);
        localStorage.setItem('user_wishlist', JSON.stringify(this.wishlist));
        this.updateWishlistCount();
        
        // Update heart icon
        this.updateHeartIcon(raffleId, false);
        
        // Show notification
        this.showNotification(`Removed "${raffleTitle}" from wishlist`, 'info');
        
        // Save to server if user is logged in
        if (this.currentUser) {
            this.saveToServer();
        }
    }
    
    updateHeartIcon(raffleId, isWishlisted) {
        const heartBtns = document.querySelectorAll(`.wishlist-btn[data-id="${raffleId}"]`);
        heartBtns.forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                if (isWishlisted) {
                    icon.className = 'fa-solid fa-heart text-red-500 text-lg transition-all duration-300';
                    btn.title = 'Remove from wishlist';
                } else {
                    icon.className = 'fa-regular fa-heart text-gray-400 hover:text-red-500 text-lg transition-all duration-300';
                    btn.title = 'Add to wishlist';
                }
            }
        });
    }
    
    saveToServer() {
        if (!this.currentUser) return;
        
        fetch('api/wishlist.php?action=save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                wishlist: this.wishlist
            })
        })
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                console.warn('Could not save wishlist to server:', result.message);
            }
        })
        .catch(error => {
            console.warn('Could not save wishlist to server:', error);
        });
    }
    
    showWishlist() {
        const raffles = window.raffleApp?.raffles || [];
        const wishlistRaffles = raffles.filter(r => this.wishlist.includes(r.id));
        
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm';
        modal.innerHTML = `
            <div class="bg-white rounded-2xl shadow-ps-lg max-w-4xl w-full max-h-[80vh] mx-4 relative flex flex-col">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-heart text-red-500 text-xl"></i>
                        <h2 class="font-heading text-2xl font-bold text-ps-text">Your Wishlist</h2>
                        <span class="bg-ps-blue/10 text-ps-blue px-3 py-1 rounded-full text-sm font-semibold">
                            ${wishlistRaffles.length} items
                        </span>
                    </div>
                    <button onclick="this.closest('.fixed').remove()" class="text-ps-blue hover:text-ps-pink text-2xl">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-6">
                    ${wishlistRaffles.length > 0 ? `
                        <div class="grid gap-4">
                            ${wishlistRaffles.map(r => `
                                <div class="bg-gray-50 rounded-xl p-4 flex items-center gap-3">
                                    <img src="${r.image_url || 'images/placeholder.jpg'}" alt="${r.title}" class="w-16 h-16 rounded-lg object-cover">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-gray-900 truncate">${r.title}</h4>
                                        <p class="text-sm text-gray-600">${r.category}</p>
                                        <p class="text-xs text-gray-500">${r.sold}/${r.total} sold</p>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <button onclick="window.wishlistManager.removeFromWishlist(${r.id}, '${r.title}'); this.closest('.fixed').remove(); window.wishlistManager.showWishlist();" class="text-red-500 hover:text-red-700">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        <button onclick="window.wishlistManager.viewRaffle(${r.id}); this.closest('.fixed').remove();" class="text-ps-blue hover:text-ps-light">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    ` : `
                        <div class="text-center py-8">
                            <i class="fa-regular fa-heart text-6xl text-gray-300 mb-4"></i>
                            <h4 class="text-xl font-bold text-gray-600 mb-2">Your wishlist is empty</h4>
                            <p class="text-gray-500 mb-4">Start adding items you love by clicking the heart icon on any raffle!</p>
                            <button onclick="this.closest('.fixed').remove()" class="bg-ps-blue text-white px-6 py-2 rounded-lg hover:bg-ps-light transition">
                                Browse Raffles
                            </button>
                        </div>
                    `}
                </div>
                
                <div class="border-t border-gray-200 p-4 flex justify-between items-center">
                    <span class="text-sm text-gray-600">${wishlistRaffles.length} items in wishlist</span>
                    <button onclick="this.closest('.fixed').remove()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                        Close
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
        
        // Close on escape key
        const handleKeydown = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', handleKeydown);
            }
        };
        document.addEventListener('keydown', handleKeydown);
    }
    
    viewRaffle(raffleId) {
        const raffle = window.raffleApp?.raffles?.find(r => r.id === raffleId);
        if (raffle && window.raffleApp) {
            window.raffleApp.openProductModal(raffle);
        }
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 translate-x-full opacity-0`;
        
        const bgColor = type === 'success' ? 'bg-green-500' : 
                       type === 'error' ? 'bg-red-500' : 
                       'bg-blue-500';
        
        notification.classList.add(bgColor, 'text-white');
        notification.innerHTML = `
            <div class="flex items-center gap-3">
                <i class="fa-solid ${type === 'success' ? 'fa-check' : type === 'error' ? 'fa-exclamation-triangle' : 'fa-info-circle'}"></i>
                <span class="font-medium">${message}</span>
                <button onclick="this.closest('div').remove()" class="ml-2 text-white/80 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full', 'opacity-0');
        }, 100);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
    
    getWishlistCount() {
        return this.wishlist.length;
    }
    
    isWishlisted(raffleId) {
        return this.wishlist.includes(raffleId);
    }
    
    clearWishlist() {
        this.wishlist = [];
        localStorage.setItem('user_wishlist', JSON.stringify(this.wishlist));
        this.updateWishlistCount();
        
        // Update all heart icons
        const heartBtns = document.querySelectorAll('.wishlist-btn');
        heartBtns.forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = 'fa-regular fa-heart text-gray-400 hover:text-red-500 text-lg transition-all duration-300';
                btn.title = 'Add to wishlist';
            }
        });
        
        // Save to server if user is logged in
        if (this.currentUser) {
            this.saveToServer();
        }
    }
}

// Initialize the wishlist manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.wishlistManager = new WishlistManager();
    window.WishlistManager = WishlistManager;
}); 