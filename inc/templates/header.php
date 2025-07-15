<?php
// Header template component
// Usage: include this file after setting $currentUser variable
?>

<nav class="bg-white shadow-ps border-b border-gray-100 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="<?php echo BASE_URL; ?>/" class="flex items-center gap-2 hover:opacity-80 transition">
                    <div class="w-8 h-8 bg-gradient-to-r from-ps-blue to-ps-light rounded-lg flex items-center justify-center">
                        <i class="fa-solid fa-ticket text-white text-lg"></i>
                    </div>
                    <span class="font-heading text-xl font-bold text-ps-text">RaffLah!</span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="<?php echo BASE_URL; ?>/" class="text-ps-text hover:text-ps-blue transition font-medium">Home</a>
                <a href="<?php echo BASE_URL; ?>/loyalty-store.php" class="text-ps-text hover:text-ps-blue transition font-medium">Loyalty Store</a>
                <a href="<?php echo BASE_URL; ?>/dashboard.php" class="text-ps-text hover:text-ps-blue transition font-medium">Dashboard</a>
            </div>

            <!-- Right Side Actions -->
            <div class="flex items-center space-x-4">
                <!-- Wishlist Button -->
                <button id="wishlist-btn" class="relative p-2 text-ps-text hover:text-ps-blue transition">
                    <i class="fa-solid fa-heart text-xl"></i>
                    <span id="wishlist-count" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center hidden">0</span>
                </button>

                <!-- Search Button -->
                <button class="p-2 text-ps-text hover:text-ps-blue transition">
                    <i class="fa-solid fa-search text-xl"></i>
                </button>

                <!-- User Account -->
                <?php if ($currentUser): ?>
                    <div class="relative">
                        <button id="userDropdown" class="flex items-center space-x-2 text-ps-text hover:text-ps-blue transition">
                            <div class="w-8 h-8 bg-gradient-to-r from-ps-blue to-ps-light rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-user text-white text-sm"></i>
                            </div>
                            <span class="hidden md:block font-medium"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </button>
                        
                        <div id="userDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                            <a href="<?php echo BASE_URL; ?>/dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fa-solid fa-dashboard w-4 mr-2"></i>Dashboard
                            </a>
                            <a href="<?php echo BASE_URL; ?>/loyalty-store.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fa-solid fa-coins w-4 mr-2"></i>Loyalty Store
                            </a>
                            <div class="border-t border-gray-200 my-2"></div>
                            <button onclick="logout()" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fa-solid fa-sign-out-alt w-4 mr-2"></i>Logout
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="flex items-center space-x-2">
                        <button onclick="openLoginModal()" class="bg-ps-blue text-white px-4 py-2 rounded-lg hover:bg-ps-light transition font-medium">
                            Login
                        </button>
                        <button onclick="openRegisterModal()" class="text-ps-blue hover:text-ps-light transition font-medium">
                            Register
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" class="md:hidden p-2 text-ps-text hover:text-ps-blue transition">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-gray-200 py-4">
            <div class="flex flex-col space-y-2">
                <a href="<?php echo BASE_URL; ?>/" class="text-ps-text hover:text-ps-blue transition font-medium py-2">Home</a>
                <a href="<?php echo BASE_URL; ?>/loyalty-store.php" class="text-ps-text hover:text-ps-blue transition font-medium py-2">Loyalty Store</a>
                <a href="<?php echo BASE_URL; ?>/dashboard.php" class="text-ps-text hover:text-ps-blue transition font-medium py-2">Dashboard</a>
                
                <?php if (!$currentUser): ?>
                    <div class="flex flex-col space-y-2 pt-2 border-t border-gray-200">
                        <button onclick="openLoginModal()" class="bg-ps-blue text-white px-4 py-2 rounded-lg hover:bg-ps-light transition font-medium">
                            Login
                        </button>
                        <button onclick="openRegisterModal()" class="text-ps-blue hover:text-ps-light transition font-medium py-2">
                            Register
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Menu Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
    }
});
</script> 