<?php 
include __DIR__ . '/../inc/header.php';

// Enhanced search and filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$category_filter = $_GET['category'] ?? 'all';
$brand_filter = $_GET['brand'] ?? 'all';
$price_range = $_GET['price_range'] ?? 'all';
$date_range = $_GET['date_range'] ?? 'all';
$sort_by = $_GET['sort_by'] ?? 'created_at';
$sort_order = $_GET['sort_order'] ?? 'DESC';
$tickets_filter = $_GET['tickets_filter'] ?? 'all';

// Build enhanced raffles query
$where_conditions = []; 
$params = [];

// Search in title, description, and ID
if ($search) {
    $where_conditions[] = "(r.title LIKE ? OR r.description LIKE ? OR r.id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Status filter
if ($status_filter && $status_filter !== 'all') {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
}

// Category filter
if ($category_filter && $category_filter !== 'all') {
    $where_conditions[] = "r.category_id = ?";
    $params[] = $category_filter;
}

// Brand filter
if ($brand_filter && $brand_filter !== 'all') {
    $where_conditions[] = "r.brand_id = ?";
    $params[] = $brand_filter;
}

// Price range filter (Updated for RM1 strategy)
if ($price_range && $price_range !== 'all') {
    switch ($price_range) {
        case 'rm1_only':
            $where_conditions[] = "r.ticket_price = 1.00";
            break;
        case 'under_5':
            $where_conditions[] = "r.ticket_price < 5";
            break;
        case '5_10':
            $where_conditions[] = "r.ticket_price >= 5 AND r.ticket_price <= 10";
            break;
        case 'over_10':
            $where_conditions[] = "r.ticket_price > 10";
            break;
    }
}

// Tickets sold filter
if ($tickets_filter && $tickets_filter !== 'all') {
    switch ($tickets_filter) {
        case 'no_sales':
            $where_conditions[] = "r.sold_tickets = 0";
            break;
        case 'low_sales':
            $where_conditions[] = "r.sold_tickets > 0 AND r.sold_tickets < (r.total_tickets * 0.25)";
            break;
        case 'medium_sales':
            $where_conditions[] = "r.sold_tickets >= (r.total_tickets * 0.25) AND r.sold_tickets < (r.total_tickets * 0.75)";
            break;
        case 'high_sales':
            $where_conditions[] = "r.sold_tickets >= (r.total_tickets * 0.75) AND r.sold_tickets < r.total_tickets";
            break;
        case 'sold_out':
            $where_conditions[] = "r.sold_tickets >= r.total_tickets";
            break;
    }
}

// Date range filter
if ($date_range && $date_range !== 'all') {
    switch ($date_range) {
        case 'today':
            $where_conditions[] = "DATE(r.created_at) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_conditions[] = "r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'quarter':
            $where_conditions[] = "r.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
            break;
    }
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Enhanced sorting options
$valid_sort_fields = ['created_at', 'title', 'ticket_price', 'sold_tickets', 'status', 'total_tickets'];
$sort_by = in_array($sort_by, $valid_sort_fields) ? $sort_by : 'created_at';
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';
$order_clause = "ORDER BY r.$sort_by $sort_order";

// Get raffles with category and brand names, including raffle_type (defaulting to 'standard' if not exists)
// Check if raffle_type column exists first
$columns_check = $pdo->query("SHOW COLUMNS FROM raffles LIKE 'raffle_type'");
$has_raffle_type = $columns_check->rowCount() > 0;

if ($has_raffle_type) {
    $stmt = $pdo->prepare("
        SELECT r.*, c.name as category_name, b.name as brand_name,
               COALESCE(r.raffle_type, 'standard') as raffle_type,
               GROUP_CONCAT(rt.tag_name) as tags
        FROM raffles r 
        LEFT JOIN categories c ON r.category_id = c.id 
        LEFT JOIN brands b ON r.brand_id = b.id 
        LEFT JOIN raffle_tags rt ON r.id = rt.raffle_id
        $where_clause 
        GROUP BY r.id
        $order_clause
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT r.*, c.name as category_name, b.name as brand_name,
               'standard' as raffle_type,
               GROUP_CONCAT(rt.tag_name) as tags
        FROM raffles r 
        LEFT JOIN categories c ON r.category_id = c.id 
        LEFT JOIN brands b ON r.brand_id = b.id 
        LEFT JOIN raffle_tags rt ON r.id = rt.raffle_id
        $where_clause 
        GROUP BY r.id
        $order_clause
    ");
}
$stmt->execute($params);
$raffles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get status counts
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM raffles GROUP BY status");
$status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$total_raffles_count = array_sum($status_counts);
$status_counts['all'] = $total_raffles_count;

// Get all categories for filter dropdown
$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all brands for form dropdowns, ordered by featured status first
$stmt = $pdo->query("SELECT id, name, is_featured FROM brands ORDER BY is_featured DESC, sort_order ASC, name ASC");
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pass raffles data to JavaScript
$raffles_for_js = [];
foreach ($raffles as $raffle) {
    if (isset($raffle['id']) && $raffle['id']) {
        $raffles_for_js[$raffle['id']] = $raffle;
    }
}
$raffles_json = json_encode($raffles_for_js);
if ($raffles_json === false) {
    $raffles_json = '{}';
}
?>

<header class="main-header">
    <div class="time-info">
        <div id="time"></div>
        <div id="date"></div>
    </div>
    <div class="user-info">
        <span>Hello, <strong>Admin</strong></span>
        <div class="profile-pic"></div>
    </div>
</header>

<div class="content-wrapper">
    <div class="main-column">
        <div class="page-header">
            <div>
                <h1 class="page-title">Manage Raffles</h1>
                <p style="color: var(--ps-text-light); margin-top: 5px; font-size: 14px;">
                    Create and manage raffles with dynamic category-brand filtering
                </p>
            </div>
            <div class="page-actions">
                <button type="button" id="add-raffle-btn" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Raffle
                </button>
            </div>
        </div>
        
        <!-- Workflow Status Indicator -->
        <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 1px solid #f59e0b; border-radius: var(--ps-radius-lg); padding: 20px; margin-bottom: 30px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                <div style="background: #f59e0b; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                    <i class="fas fa-trophy"></i>
                </div>
                <div>
                    <h3 style="color: #92400e; font-size: 16px; font-weight: 600; margin: 0;">RM1 Ticket Strategy Guide</h3>
                    <p style="color: #92400e; font-size: 14px; margin: 5px 0 0 0;">Create raffles with smart category-brand filtering</p>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div style="background: white; padding: 12px; border-radius: var(--ps-radius); border: 1px solid #fcd34d;">
                    <div style="font-weight: 600; color: #92400e; font-size: 14px;">🎯 RM1 Pricing</div>
                    <div style="font-size: 12px; color: #a16207; margin-top: 2px;">Low barrier entry increases participation by 150%</div>
                </div>
                <div style="background: white; padding: 12px; border-radius: var(--ps-radius); border: 1px solid #fcd34d;">
                    <div style="font-weight: 600; color: #92400e; font-size: 14px;">🚀 Quick Purchases</div>
                    <div style="font-size: 12px; color: #a16207; margin-top: 2px;">Users buy multiple tickets easily (RM1, RM3, RM5)</div>
                </div>
                <div style="background: white; padding: 12px; border-radius: var(--ps-radius); border: 1px solid #fcd34d;">
                    <div style="font-weight: 600; color: #92400e; font-size: 14px;">🏆 Gamification</div>
                    <div style="font-size: 12px; color: #a16207; margin-top: 2px;">Achievement badges boost retention by 80%</div>
                </div>
                <div style="background: white; padding: 12px; border-radius: var(--ps-radius); border: 1px solid #fcd34d;">
                    <div style="font-weight: 600; color: #92400e; font-size: 14px;">📊 Analytics</div>
                    <div style="font-size: 12px; color: #a16207; margin-top: 2px;">Track user behavior and optimize strategies</div>
                </div>
            </div>
        </div>
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="flash-message">
                <?php 
                echo $_SESSION['flash_message']; 
                unset($_SESSION['flash_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (empty($raffles)): ?>
            <div class="alert alert-warning">
                <h3>No Raffles Found</h3>
                <p>There are currently no raffles in the database. This could mean:</p>
                <ul>
                    <li>The database tables haven't been set up yet</li>
                    <li>No raffles have been created</li>
                    <li>There's an issue with the database connection</li>
                </ul>
                <p><a href="db-check.php" class="text-primary font-bold">Run Database Diagnostic</a> to check your database setup.</p>
            </div>
        <?php else: ?>
            <!-- Debug: Show first raffle structure -->
            <?php if (isset($_GET['debug'])): ?>
                <div style="background: #f0f0f0; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-family: monospace;">
                    <h4>Debug: First Raffle Structure:</h4>
                    <pre><?php print_r($raffles[0]); ?></pre>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- ENHANCED FILTER SYSTEM -->
        <?php include __DIR__ . '/components/raffle-filters.php'; ?>
        
        <!-- ENHANCED RAFFLE TABLE -->
        <?php include __DIR__ . '/components/raffle-table.php'; ?>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
const rafflesData = <?php echo $raffles_json; ?>;

// Time update
function updateTime() {
    const timeEl = document.getElementById('time');
    const dateEl = document.getElementById('date');
    const now = new Date();
    
    timeEl.innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    dateEl.innerText = now.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
}
updateTime();
setInterval(updateTime, 1000);

// Smart Edit System - Global state
let selectedRaffles = [];
let currentEditMode = 'single'; // 'single' or 'batch'
let raffleFieldStates = {}; // Track field uniformity

// Enhanced Filter Functionality
function initEnhancedFilters() {
    const toggleAdvancedBtn = document.getElementById('toggle-advanced-filters');
    const advancedFilters = document.getElementById('advanced-filters');
    const toggleMainFiltersBtn = document.getElementById('toggle-main-filters');
    const toggleMainFiltersExpandedBtn = document.getElementById('toggle-main-filters-expanded');
    const mainFiltersContainer = document.getElementById('main-filters-container');
    const filterHeaderCompact = document.getElementById('filter-header-compact');
    const filterHeaderExpanded = document.getElementById('filter-header-expanded');
    const clearFiltersBtn = document.getElementById('clear-filters-btn');
    const sortOrderBtn = document.getElementById('sort-order-btn');
    const sortOrderInput = document.getElementById('sort-order');
    
    // Toggle main filters container from compact header (button)
    if (toggleMainFiltersBtn && mainFiltersContainer) {
        toggleMainFiltersBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            // Show expanded state
            mainFiltersContainer.classList.remove('collapsed');
            filterHeaderCompact.style.display = 'none';
            filterHeaderExpanded.style.display = 'flex';
        });
    }
    
    // Make entire compact header clickable
    if (filterHeaderCompact && mainFiltersContainer) {
        filterHeaderCompact.addEventListener('click', function() {
            // Show expanded state
            mainFiltersContainer.classList.remove('collapsed');
            filterHeaderCompact.style.display = 'none';
            filterHeaderExpanded.style.display = 'flex';
        });
    }
    
    // Toggle main filters container from expanded header
    if (toggleMainFiltersExpandedBtn && mainFiltersContainer) {
        toggleMainFiltersExpandedBtn.addEventListener('click', function() {
            // Show compact state
            mainFiltersContainer.classList.add('collapsed');
            filterHeaderCompact.style.display = 'flex';
            filterHeaderExpanded.style.display = 'none';
        });
    }
    
    // Toggle advanced filters
    if (toggleAdvancedBtn && advancedFilters) {
        toggleAdvancedBtn.addEventListener('click', function() {
            if (advancedFilters.style.display === 'none') {
                advancedFilters.style.display = 'block';
                this.innerHTML = '<i class="fas fa-cog"></i> Hide Advanced';
                this.style.background = '#ef4444';
            } else {
                advancedFilters.style.display = 'none';
                this.innerHTML = '<i class="fas fa-cog"></i> Advanced';
                this.style.background = '#3b82f6';
            }
        });
    }
    
    // Clear all filters
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            const url = new URL(window.location);
            url.search = '';
            window.location.href = url.toString();
        });
    }
    
    // Sort order toggle
    if (sortOrderBtn && sortOrderInput) {
        sortOrderBtn.addEventListener('click', function() {
            const currentOrder = this.dataset.order;
            const newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
            
            sortOrderInput.value = newOrder;
            this.dataset.order = newOrder;
            this.innerHTML = `<i class="fas fa-sort-${newOrder === 'ASC' ? 'up' : 'down'}"></i>`;
            
            // Submit form
            document.getElementById('raffle-filters').submit();
        });
    }
    
    // Update active filters display
    updateActiveFiltersDisplay();
}

function updateActiveFiltersDisplay() {
    const activeFiltersSpan = document.getElementById('active-filters');
    const params = new URLSearchParams(window.location.search);
    const activeFilters = [];
    
    if (params.get('search')) activeFilters.push(`Search: "${params.get('search')}"`);
    if (params.get('status') && params.get('status') !== 'all') activeFilters.push(`Status: ${params.get('status')}`);
    if (params.get('category') && params.get('category') !== 'all') activeFilters.push('Category filter');
    if (params.get('brand') && params.get('brand') !== 'all') activeFilters.push('Brand filter');
    if (params.get('price_range') && params.get('price_range') !== 'all') activeFilters.push('Price filter');
    if (params.get('tickets_filter') && params.get('tickets_filter') !== 'all') activeFilters.push('Sales filter');
    if (params.get('date_range') && params.get('date_range') !== 'all') activeFilters.push('Date filter');
    
    if (activeFilters.length > 0 && activeFiltersSpan) {
        activeFiltersSpan.innerHTML = ' • Active filters: ' + activeFilters.join(', ');
    }
}

// Global functions for row and checkbox interactions (must be outside DOMContentLoaded)
function toggleRaffleRow(row, event) {
    // Only prevent toggle if clicking directly on the Edit button
    if (event && event.target.closest('.smart-row-edit-btn')) {
        return;
    }
    
    const checkbox = row.querySelector('.raffle-checkbox');
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        checkbox.dispatchEvent(new Event('change'));
    }
}

function toggleSelectAll(event) {
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    
    // If the click came from the checkbox itself, let it handle the change
    if (event && event.target === selectAllCheckbox) {
        return;
    }
    
    // Otherwise, toggle the checkbox programmatically
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = !selectAllCheckbox.checked;
        selectAllCheckbox.dispatchEvent(new Event('change'));
    }
}

// Make functions explicitly available on window object
window.toggleRaffleRow = toggleRaffleRow;
window.toggleSelectAll = toggleSelectAll;

    // Attach event listeners after DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing tag management...');
        
        // Initialize tag management system
        const tagManager = initTagManagement();
        console.log('Tag manager initialized:', tagManager);
        
        // Modal Functionality
        const modal = document.getElementById('edit-raffle-modal');
        const modalForm = document.getElementById('edit-raffle-form');
        const editButtons = document.querySelectorAll('.smart-row-edit-btn');
        const closeBtn = modal ? modal.querySelector('.modal-close-btn') : null;

    // Note: Edit button functionality is now handled in the smart row edit button section
    // This ensures proper modal opening and tag loading

    const closeModal = () => {
        if (modal) {
            modal.classList.remove('active');
            
            // Clear CKEditor content when closing
            if (window.editDescriptionEditor) {
                window.editDescriptionEditor.setData('');
            }
        }
    };

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    // Add event listener for secondary cancel button
    const cancelBtn = modal ? modal.querySelector('.modal-close-btn-secondary') : null;
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }
    
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    // Enhanced Filter Functionality
    const filterForm = document.getElementById('raffle-filters');
    if (filterForm) {
        // All filter inputs
        const filterInputs = [
            'status-filter',
            'category-filter', 
            'brand-filter',
            'price-range-filter',
            'tickets-filter',
            'date-range-filter',
            'sort-by'
        ];
        
        // Add change listeners to all filter inputs
        filterInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('change', () => filterForm.submit());
            }
        });

        // Search input with debounce
        const searchInput = document.getElementById('raffle-search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    filterForm.submit();
                }, 500); // Debounce search input by 500ms
            });
            
            // Clear search on Escape
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    searchInput.value = '';
                    filterForm.submit();
                }
            });
        }
    }
    
    // Initialize enhanced filters
    initEnhancedFilters();

    // Attach event listeners
    attachEventListeners();
    
    // Smart Edit System
    initSmartEditFeatures();
    
    // Add Raffle Modal Functionality
    initAddRaffleModal();
    
    // Smart Edit Modal Functionality
    initSmartEditModal();
    
    // Add event listeners to edit buttons to prevent row selection and ensure modal opening
    document.querySelectorAll('.smart-row-edit-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            // Note: The main edit functionality is handled above in the modal section
        });
    });
    
                // Note: Edit button functionality is now handled in the smart row edit button section
    // No need for retry logic as the buttons are properly targeted

    /* -- Image Uploader JS -- */
    const imageUploader = document.getElementById('image-uploader');
    const imageInput = document.getElementById('edit-raffle-image');
    const imagePreview = document.getElementById('image-preview');
    const uploadText = imageUploader ? imageUploader.querySelector('.upload-text') : null;

    if (imageUploader && imageInput) {
        imageUploader.addEventListener('click', () => imageInput.click());

        imageUploader.addEventListener('dragover', (e) => {
            e.preventDefault();
            imageUploader.classList.add('dragover');
        });

        imageUploader.addEventListener('dragleave', () => {
            imageUploader.classList.remove('dragover');
        });

        imageUploader.addEventListener('drop', (e) => {
            e.preventDefault();
            imageUploader.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                imageInput.files = files;
                previewImage(files[0]);
            }
        });

        imageInput.addEventListener('change', (e) => {
            const files = e.target.files;
            if (files.length > 0) {
                previewImage(files[0]);
            }
        });
    }

    function previewImage(file) {
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
                uploadText.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    }

    function resetImageUploader() {
        imageInput.value = ''; // Clear the file input
        imagePreview.src = '#';
        imagePreview.style.display = 'none';
        uploadText.style.display = 'block';
    }

    function openEditModal(raffle) {
        const modal = document.getElementById('edit-raffle-modal');
        const modalForm = document.getElementById('edit-raffle-form');
        
        if (!modal || !modalForm) {
            console.error('Edit modal not found');
            return;
        }
        
        // Update modal title with raffle ID
        const idDisplay = document.getElementById('edit-raffle-id-display');
        if (idDisplay) {
            idDisplay.textContent = `#${raffle.id}`;
        }
        
        // Populate form
        const idInput = modalForm.querySelector('#edit-raffle-id');
        if (idInput) idInput.value = raffle.id;
        const titleInput = modalForm.querySelector('#edit-raffle-title');
        if (titleInput) titleInput.value = raffle.title || '';
        // Set CKEditor content for description
        if (window.editDescriptionEditor) {
            window.editDescriptionEditor.setData(raffle.description || '');
        } else {
            const descInput = modalForm.querySelector('#edit-raffle-description');
            if (descInput) descInput.value = raffle.description || '';
        }
        const statusInput = modalForm.querySelector('#edit-raffle-status');
        if (statusInput) statusInput.value = raffle.status || 'draft';
        const categoryInput = modalForm.querySelector('#edit-raffle-category');
        if (categoryInput) categoryInput.value = raffle.category_id || '';
        const brandInput = modalForm.querySelector('#edit-raffle-brand');
        if (brandInput) brandInput.value = raffle.brand_id || '';
        const priceInput = modalForm.querySelector('#edit-raffle-price');
        if (priceInput) priceInput.value = raffle.ticket_price || '';
        const totalTicketsInput = modalForm.querySelector('#edit-raffle-total-tickets');
        if (totalTicketsInput) totalTicketsInput.value = raffle.total_tickets || '';
        const ticketsPerEntryInput = modalForm.querySelector('#edit-raffle-tickets-per-entry');
        if (ticketsPerEntryInput) ticketsPerEntryInput.value = raffle.tickets_per_entry || 1;
        // Handle draw date safely
        try {
            const drawDateInput = modalForm.querySelector('#edit-raffle-draw-date');
            if (drawDateInput) {
                if (raffle.draw_date && raffle.draw_date !== '0000-00-00 00:00:00' && raffle.draw_date !== null) {
                    const drawDate = new Date(raffle.draw_date.replace(' ', 'T'));
                    if (!isNaN(drawDate.getTime())) {
                        // Valid date
                        const localDate = new Date(drawDate.getTime() - drawDate.getTimezoneOffset() * 60000);
                        drawDateInput.value = localDate.toISOString().slice(0, 16);
                    } else {
                        drawDateInput.value = '';
                    }
                } else {
                    drawDateInput.value = '';
                }
            }
        } catch (error) {
            console.warn('Error parsing draw date:', raffle.draw_date, error);
            const drawDateInput = modalForm.querySelector('#edit-raffle-draw-date');
            if (drawDateInput) drawDateInput.value = '';
        }
        // Handle image preview
        const imagePreview = document.getElementById('image-preview');
        const uploadText = document.querySelector('#image-uploader .upload-text');
        if (imagePreview && uploadText) {
            if (raffle.image_url) {
                const imageUrl = `<?php echo BASE_URL; ?>/${raffle.image_url.startsWith('images/') ? '' : 'images/'}${raffle.image_url.split('/').pop()}`;
                imagePreview.src = imageUrl;
                imagePreview.style.display = 'block';
                uploadText.style.display = 'none';
            } else {
                imagePreview.style.display = 'none';
                uploadText.style.display = 'block';
            }
        }
        
        // Open modal
        modal.classList.add('active');
        
        // Load tags for this raffle
        if (tagManager && tagManager.loadRaffleTags) {
            console.log('Loading tags for raffle:', raffle.id);
            tagManager.loadRaffleTags(raffle.id);
        } else {
            console.error('Tag manager or loadRaffleTags not available');
        }
        
        console.log('Edit modal opened for raffle:', raffle.id);

        // Handle tags from database
        if (raffle.tags && typeof window.setEditModalTags === 'function') {
            // Convert comma-separated string to array
            let tagArr = raffle.tags ? raffle.tags.split(',').map(t => t.trim()).filter(Boolean) : [];
            window.setEditModalTags(tagArr);
        }
    }

    function closeEditModal() {
        document.getElementById('edit-modal').style.display = 'none';
        resetImageUploader();
    }

    function attachEventListeners() {
        // Additional event listeners can be added here if needed
    }
    
    // Functions are now defined globally above

    function initSmartEditFeatures() {
        const selectAllCheckbox = document.getElementById('select-all-checkbox');
        const raffleCheckboxes = document.querySelectorAll('.raffle-checkbox');
        const rowEditButtons = document.querySelectorAll('.smart-row-edit-btn');

        // Select all functionality
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                raffleCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                    updateRowVisualState(checkbox);
                });
                updateSelectAllState();
                updateSmartRowButtons();
            });
        }

        // Individual checkbox functionality
        raffleCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateRowVisualState(this);
                updateSelectAllState();
                updateSmartRowButtons();
            });
        });

        // Row edit button functionality
        rowEditButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                
                const selectedCount = document.querySelectorAll('.raffle-checkbox:checked').length;
                const currentRaffleId = this.dataset.raffleId;
                
                if (selectedCount === 0) {
                    // No selections - edit this specific raffle using regular edit modal
                    const raffle = rafflesData[currentRaffleId];
                    
                    if (raffle) {
                        // Open the regular edit modal with tags
                        openEditModal(raffle);
                    } else {
                        alert('Unable to edit raffle. Please try again.');
                    }
                } else if (selectedCount === 1) {
                    // One selection - check if it's the same raffle as the button clicked
                    const selectedRaffle = getSelectedRaffleData()[0];
                    
                    if (selectedRaffle && selectedRaffle.id == currentRaffleId) {
                        // Same raffle - edit using regular edit modal
                        openEditModal(selectedRaffle);
                    } else {
                        // Different raffle - use smart edit modal for consistency
                        selectedRaffles = getSelectedRaffleData();
                        currentEditMode = 'single';
                        openSmartEditModal();
                    }
                } else {
                    // Multiple selections - batch edit using smart edit modal
                    selectedRaffles = getSelectedRaffleData();
                    currentEditMode = 'batch';
                    
                    if (selectedRaffles.length === 0) {
                        alert('Unable to edit raffles. Please try again.');
                        return;
                    }
                    
                    openSmartEditModal();
                }
            });
        });

        function updateRowVisualState(checkbox) {
            const row = checkbox.closest('.raffle-row');
            if (row) {
                if (checkbox.checked) {
                    row.style.backgroundColor = '#e8f4fd';
                    // Use box-shadow instead of border/transform so width stays identical
                    row.style.boxShadow = 'inset 4px 0 0 #3b82f6';
                } else {
                    row.style.backgroundColor = '';
                    row.style.boxShadow = '';
                }
            }
        }

        function updateSelectAllState() {
            if (!selectAllCheckbox) return;
            
            const checkedBoxes = document.querySelectorAll('.raffle-checkbox:checked');
            const totalBoxes = raffleCheckboxes.length;
            
            if (checkedBoxes.length === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedBoxes.length === totalBoxes) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }

        function updateSmartRowButtons() {
            const selectedCount = document.querySelectorAll('.raffle-checkbox:checked').length;
            
            rowEditButtons.forEach(button => {
                const btnText = button.querySelector('.btn-text');
                
                if (selectedCount === 0) {
                    // No selections - show as normal edit buttons
                    button.style.background = '#6366f1';
                    button.style.transform = 'scale(1)';
                    button.style.boxShadow = '0 2px 4px rgba(99, 102, 241, 0.2)';
                    if (btnText) btnText.textContent = 'Edit';
                } else if (selectedCount === 1) {
                    // One selection - highlight the button for selected item, dim others
                    const raffleId = button.dataset.raffleId;
                    const isSelectedRaffle = document.querySelector(`.raffle-checkbox[data-raffle-id="${raffleId}"]`)?.checked;
                    
                    if (isSelectedRaffle) {
                        button.style.background = '#059669';
                        button.style.transform = 'scale(1.05)';
                        button.style.boxShadow = '0 4px 12px rgba(5, 150, 105, 0.3)';
                    } else {
                        button.style.background = '#9ca3af';
                        button.style.transform = 'scale(0.95)';
                        button.style.boxShadow = 'none';
                    }
                    if (btnText) btnText.textContent = 'Edit';
                } else {
                    // Multiple selections - all buttons become batch edit with purple/violet color
                    button.style.background = '#8b5cf6';
                    button.style.transform = 'scale(1.02)';
                    button.style.boxShadow = '0 4px 12px rgba(139, 92, 246, 0.3)';
                    if (btnText) btnText.textContent = 'Edit';
                }
            });
        }

        function getSelectedRaffleData() {
            const checkedBoxes = document.querySelectorAll('.raffle-checkbox:checked');
            return Array.from(checkedBoxes).map(checkbox => {
                const raffleId = checkbox.dataset.raffleId;
                return rafflesData[raffleId];
            }).filter(raffle => raffle);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+A to select all
            if (e.ctrlKey && e.key === 'a' && document.querySelectorAll('.raffle-checkbox').length > 0) {
                e.preventDefault();
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = true;
                    selectAllCheckbox.dispatchEvent(new Event('change'));
                }
            }
            
            // Escape to clear selection
            if (e.key === 'Escape') {
                raffleCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                    updateRowVisualState(checkbox);
                });
                if (selectAllCheckbox) selectAllCheckbox.checked = false;
                updateSelectAllState();
                updateSmartRowButtons();
            }
        });

            // Initialize button states
    updateSmartRowButtons();
}

// Tag Management System
function initTagManagement() {
    console.log('initTagManagement called');
    
    // Edit modal elements
    const addTagBtn = document.getElementById('add-tag-btn');
    const newTagInput = document.getElementById('new-tag-input');
    const tagTypeSelect = document.getElementById('tag-type-select');
    const tagsContainer = document.getElementById('raffle-tags-container');
    const popularTagsSuggestions = document.getElementById('popular-tags-suggestions');
    const popularTagsList = document.getElementById('popular-tags-list');
    
    // Add modal elements
    const addNewTagBtn = document.getElementById('add-new-tag-btn');
    const addNewTagInput = document.getElementById('add-new-tag-input');
    const addTagTypeSelect = document.getElementById('add-tag-type-select');
    const addTagsContainer = document.getElementById('add-raffle-tags-container');
    const addPopularTagsSuggestions = document.getElementById('add-popular-tags-suggestions');
    const addPopularTagsList = document.getElementById('add-popular-tags-list');
    
    // Smart edit modal elements
    const smartAddTagBtn = document.getElementById('smart-add-tag-btn');
    const smartNewTagInput = document.getElementById('smart-new-tag-input');
    const smartTagTypeSelect = document.getElementById('smart-tag-type-select');
    const smartTagsContainer = document.getElementById('smart-raffle-tags-container');
    const smartPopularTagsSuggestions = document.getElementById('smart-popular-tags-suggestions');
    const smartPopularTagsList = document.getElementById('smart-popular-tags-list');
    
    console.log('Tag elements found:', {
        // Edit modal
        addTagBtn: !!addTagBtn,
        newTagInput: !!newTagInput,
        tagTypeSelect: !!tagTypeSelect,
        tagsContainer: !!tagsContainer,
        popularTagsSuggestions: !!popularTagsSuggestions,
        popularTagsList: !!popularTagsList,
        // Add modal
        addNewTagBtn: !!addNewTagBtn,
        addNewTagInput: !!addNewTagInput,
        addTagTypeSelect: !!addTagTypeSelect,
        addTagsContainer: !!addTagsContainer,
        addPopularTagsSuggestions: !!addPopularTagsSuggestions,
        addPopularTagsList: !!addPopularTagsList,
        // Smart edit modal
        smartAddTagBtn: !!smartAddTagBtn,
        smartNewTagInput: !!smartNewTagInput,
        smartTagTypeSelect: !!smartTagTypeSelect,
        smartTagsContainer: !!smartTagsContainer,
        smartPopularTagsSuggestions: !!smartPopularTagsSuggestions,
        smartPopularTagsList: !!smartPopularTagsList
    });
    
    let currentRaffleId = null;
    let popularTags = [];
    
    // Load popular tags
    function loadPopularTags() {
        fetch('../api/tags.php?action=get_popular_tags')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    popularTags = data.data;
                    updatePopularTagsDisplay();
                }
            })
            .catch(error => console.error('Error loading popular tags:', error));
    }
    
    function updatePopularTagsDisplay() {
        if (popularTags.length > 0) {
            const tagNames = popularTags.slice(0, 8).map(tag => tag.tag_name).join(', ');
            
            // Update edit modal
            if (popularTagsList) {
                popularTagsList.textContent = tagNames;
            }
            if (popularTagsSuggestions) {
                popularTagsSuggestions.style.display = 'block';
            }
            
            // Update add modal
            if (addPopularTagsList) {
                addPopularTagsList.textContent = tagNames;
            }
            if (addPopularTagsSuggestions) {
                addPopularTagsSuggestions.style.display = 'block';
            }
            
            // Update smart edit modal
            if (smartPopularTagsList) {
                smartPopularTagsList.textContent = tagNames;
            }
            if (smartPopularTagsSuggestions) {
                smartPopularTagsSuggestions.style.display = 'block';
            }
        }
    }
    
    // Load tags for a raffle (edit modal)
    function loadRaffleTags(raffleId) {
        currentRaffleId = raffleId;
        
        // Update both edit modal and smart modal containers
        const containers = [tagsContainer, smartTagsContainer].filter(Boolean);
        
        containers.forEach(container => {
            if (container) {
                container.innerHTML = '<div style="color: #6b7280; font-size: 14px;">Loading tags...</div>';
            }
        });
        
        fetch(`../api/tags.php?action=get_raffle_tags&raffle_id=${raffleId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update both containers
                    if (tagsContainer) {
                        renderTags(data.data, tagsContainer);
                    }
                    if (smartTagsContainer) {
                        renderTags(data.data, smartTagsContainer);
                    }
                } else {
                    containers.forEach(container => {
                        if (container) {
                            container.innerHTML = '<div style="color: #6b7280; font-size: 14px;">No tags yet</div>';
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error loading raffle tags:', error);
                containers.forEach(container => {
                    if (container) {
                        container.innerHTML = '<div style="color: #ef4444; font-size: 14px;">Error loading tags</div>';
                    }
                });
            });
    }
    
    // Initialize add modal tags (for new raffles)
    function initAddModalTags() {
        if (addTagsContainer) {
            addTagsContainer.innerHTML = '<span style="color: #6b7280; font-size: 14px;">No tags yet. Add some to improve discoverability!</span>';
        }
    }
    
    // Render tags in the container
    function renderTags(tags, container = tagsContainer) {
        if (!container) return;
        
        if (tags.length === 0) {
            container.innerHTML = '<div style="color: #6b7280; font-size: 14px;">No tags yet. Add some to improve discoverability!</div>';
            return;
        }
        
        container.innerHTML = tags.map(tag => `
            <div class="tag-item" data-tag="${tag.tag_name}" style="
                display: inline-flex; align-items: center; gap: 6px; 
                background: ${getTagColor(tag.tag_type)}; 
                color: white; padding: 6px 12px; border-radius: 20px; 
                font-size: 12px; font-weight: 600; cursor: pointer;
                transition: all 0.2s ease;
            " onclick="removeTag('${tag.tag_name}')">
                <span>${tag.tag_name}</span>
                <i class="fas fa-times" style="font-size: 10px; opacity: 0.8;"></i>
            </div>
        `).join('');
    }
    
    function getTagColor(tagType) {
        switch (tagType) {
            case 'category': return '#10b981';
            case 'brand': return '#3b82f6';
            case 'feature': return '#f59e0b';
            default: return '#8b5cf6';
        }
    }
    
    // Add tag functionality (edit modal)
    if (addTagBtn && newTagInput) {
        addTagBtn.addEventListener('click', function() {
            const tagName = newTagInput.value.trim();
            const tagType = tagTypeSelect.value;
            
            if (!tagName) {
                alert('Please enter a tag name');
                return;
            }
            
            if (!currentRaffleId) {
                alert('No raffle selected');
                return;
            }
            
            // Add tag via API
            fetch('../api/tags.php?action=add_tag', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    raffle_id: currentRaffleId,
                    tag_name: tagName,
                    tag_type: tagType
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    newTagInput.value = '';
                    loadRaffleTags(currentRaffleId);
                    showSuccessMessage(`Tag "${tagName}" added successfully!`);
                } else {
                    alert('Error adding tag: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error adding tag:', error);
                alert('Error adding tag. Please try again.');
            });
        });
        
        // Enter key to add tag
        newTagInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                addTagBtn.click();
            }
        });
    }
    
    // Add tag functionality (smart edit modal)
    if (smartAddTagBtn && smartNewTagInput) {
        smartAddTagBtn.addEventListener('click', function() {
            const tagName = smartNewTagInput.value.trim();
            const tagType = smartTagTypeSelect.value;
            
            if (!tagName) {
                alert('Please enter a tag name');
                return;
            }
            
            if (!currentRaffleId) {
                alert('No raffle selected');
                return;
            }
            
            // Add tag via API
            fetch('../api/tags.php?action=add_tag', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    raffle_id: currentRaffleId,
                    tag_name: tagName,
                    tag_type: tagType
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    smartNewTagInput.value = '';
                    loadRaffleTags(currentRaffleId);
                    showSuccessMessage(`Tag "${tagName}" added successfully!`);
                } else {
                    alert('Error adding tag: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error adding tag:', error);
                alert('Error adding tag. Please try again.');
            });
        });
        
        // Enter key to add tag
        smartNewTagInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                smartAddTagBtn.click();
            }
        });
    }
    
    // Add tag functionality (add modal)
    if (addNewTagBtn && addNewTagInput) {
        addNewTagBtn.addEventListener('click', function() {
            const tagName = addNewTagInput.value.trim();
            const tagType = addTagTypeSelect.value;
            
            if (!tagName) {
                alert('Please enter a tag name');
                return;
            }
            
            // For new raffles, we'll store tags temporarily and add them when the raffle is created
            addTagToAddModal(tagName, tagType);
            addNewTagInput.value = '';
        });
        
        // Enter key to add tag
        addNewTagInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                addNewTagBtn.click();
            }
        });
    }
    
    // Temporary tag storage for add modal (make it global)
    window.addModalTags = [];
    
    function addTagToAddModal(tagName, tagType) {
        // Check if tag already exists
        if (window.addModalTags.some(tag => tag.tag_name === tagName)) {
            alert('Tag already exists');
            return;
        }
        
        window.addModalTags.push({
            tag_name: tagName,
            tag_type: tagType
        });
        
        window.renderAddModalTags();
        showSuccessMessage(`Tag "${tagName}" added to new raffle!`);
    }
    
    // Make renderAddModalTags globally available
    window.renderAddModalTags = function() {
        if (!addTagsContainer) return;
        
        if (window.addModalTags.length === 0) {
            addTagsContainer.innerHTML = '<span style="color: #6b7280; font-size: 14px;">No tags yet. Add some to improve discoverability!</span>';
            return;
        }
        
        addTagsContainer.innerHTML = window.addModalTags.map(tag => `
            <div class="tag-item" data-tag="${tag.tag_name}" style="
                display: inline-flex; align-items: center; gap: 6px; 
                background: ${getTagColor(tag.tag_type)}; 
                color: white; padding: 6px 12px; border-radius: 20px; 
                font-size: 12px; font-weight: 600; cursor: pointer;
                transition: all 0.2s ease;
            " onclick="removeAddModalTag('${tag.tag_name}')">
                <span>${tag.tag_name}</span>
                <i class="fas fa-times" style="font-size: 10px; opacity: 0.8;"></i>
            </div>
        `).join('');
    };
    
    // Remove tag from add modal
    window.removeAddModalTag = function(tagName) {
        if (!confirm(`Remove tag "${tagName}"?`)) return;
        
        window.addModalTags = window.addModalTags.filter(tag => tag.tag_name !== tagName);
        window.renderAddModalTags();
        showSuccessMessage(`Tag "${tagName}" removed!`);
    };
    
    // Remove tag functionality (global function)
    window.removeTag = function(tagName) {
        if (!currentRaffleId) return;
        
        if (!confirm(`Remove tag "${tagName}"?`)) return;
        
        fetch('../api/tags.php?action=remove_tag', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                raffle_id: currentRaffleId,
                tag_name: tagName
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadRaffleTags(currentRaffleId);
                showSuccessMessage(`Tag "${tagName}" removed successfully!`);
            } else {
                alert('Error removing tag: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error removing tag:', error);
            alert('Error removing tag. Please try again.');
        });
    };
    
    // Load popular tags on init
    loadPopularTags();
    
    // Return the functions so they can be called from modal open
    return { 
        loadRaffleTags,
        initAddModalTags,
        renderAddModalTags
    };
}
    
    function initAddRaffleModal() {
        const addRaffleBtn = document.getElementById('add-raffle-btn');
        const addModal = document.getElementById('add-raffle-modal');
        const addModalClose = document.getElementById('add-modal-close');
        const addCancelBtn = document.getElementById('add-cancel-btn');
        const addForm = document.getElementById('add-raffle-form');

        // Open add modal
        if (addRaffleBtn) {
            addRaffleBtn.addEventListener('click', function() {
                if (addModal) {
                    addModal.classList.add('active');
                    resetAddImageUploader();
                    // Reset modal title and notice
                    document.getElementById('modal-title').textContent = 'Add New Raffle';
                    document.getElementById('bulk-notice').style.display = 'none';
                    // Reset tags for new raffle
                    if (typeof window.addModalTags !== 'undefined') {
                        window.addModalTags = [];
                        if (typeof window.renderAddModalTags === 'function') {
                            window.renderAddModalTags();
                        }
                    }
                }
            });
        }

        // Listen for quantity changes to update modal
        const quantityInput = document.getElementById('add-raffle-quantity');
        if (quantityInput) {
            quantityInput.addEventListener('input', function() {
                const quantity = parseInt(this.value) || 1;
                const modalTitle = document.getElementById('modal-title');
                const bulkNotice = document.getElementById('bulk-notice');
                const bulkNoticeText = document.getElementById('bulk-notice-text');
                
                if (quantity > 1) {
                    modalTitle.textContent = `Add ${quantity} New Raffles`;
                    bulkNotice.style.display = 'block';
                    bulkNoticeText.textContent = `You are creating ${quantity} raffles with these details. Each raffle will have the same information but unique IDs.`;
                } else {
                    modalTitle.textContent = 'Add New Raffle';
                    bulkNotice.style.display = 'none';
                }
            });
        }

        // Close add modal
        let closeAddModal = () => {
            if (addModal) {
                addModal.classList.remove('active');
                if (addForm) addForm.reset();
                resetAddImageUploader();
                
                // Reset brand dropdown to initial state
                if (brandSelect) {
                    brandSelect.innerHTML = '<option value="">-- Select a Category First --</option>';
                    brandSelect.disabled = true;
                    brandSelect.classList.remove('brand-loading');
                }
            }
        };

        if (addModalClose) {
            addModalClose.addEventListener('click', closeAddModal);
        }
        
        if (addCancelBtn) {
            addCancelBtn.addEventListener('click', closeAddModal);
        }

        if (addModal) {
            addModal.addEventListener('click', (e) => {
                if (e.target === addModal) {
                    closeAddModal();
                }
            });
        }

        // Add image uploader functionality
        const addImageUploader = document.getElementById('add-image-uploader');
        const addImageInput = document.getElementById('add-raffle-image');
        const addImagePreview = document.getElementById('add-image-preview');
        const addUploadText = addImageUploader ? addImageUploader.querySelector('.upload-text') : null;

        if (addImageUploader && addImageInput) {
            addImageUploader.addEventListener('click', () => addImageInput.click());

            addImageUploader.addEventListener('dragover', (e) => {
                e.preventDefault();
                addImageUploader.classList.add('dragover');
            });

            addImageUploader.addEventListener('dragleave', () => {
                addImageUploader.classList.remove('dragover');
            });

            addImageUploader.addEventListener('drop', (e) => {
                e.preventDefault();
                addImageUploader.classList.remove('dragover');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    addImageInput.files = files;
                    previewAddImage(files[0]);
                }
            });

            addImageInput.addEventListener('change', (e) => {
                const files = e.target.files;
                if (files.length > 0) {
                    previewAddImage(files[0]);
                }
            });
        }

        function previewAddImage(file) {
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (addImagePreview && addUploadText) {
                        addImagePreview.src = e.target.result;
                        addImagePreview.style.display = 'block';
                        addUploadText.style.display = 'none';
                    }
                };
                reader.readAsDataURL(file);
            }
        }

        function resetAddImageUploader() {
            if (addImageInput) addImageInput.value = '';
            if (addImagePreview) {
                addImagePreview.src = '#';
                addImagePreview.style.display = 'none';
            }
            if (addUploadText) addUploadText.style.display = 'block';
        }

        // Initialize CKEditor with delay to ensure DOM is ready
        setTimeout(() => {
            // Initialize CKEditor for Add Modal
            let addDescriptionEditor;
            const addDescElement = document.querySelector('#add-raffle-description');
            if (addDescElement) {
                ClassicEditor
                    .create(addDescElement, {
                        toolbar: [
                            'heading', '|',
                            'bold', 'italic', 'underline', '|',
                            'bulletedList', 'numberedList', '|',
                            'outdent', 'indent', '|',
                            'blockQuote', 'insertTable', '|',
                            'link', '|',
                            'undo', 'redo'
                        ],
                        heading: {
                            options: [
                                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                                { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                            ]
                        }
                    })
                    .then(editor => {
                        addDescriptionEditor = editor;
                        window.addDescriptionEditor = editor;
                    })
                    .catch(error => {
                        console.error('CKEditor initialization error for Add Modal:', error);
                    });
            }

            // Initialize CKEditor for Edit Modal
            let editDescriptionEditor;
            const editDescElement = document.querySelector('#edit-raffle-description');
            if (editDescElement) {
                ClassicEditor
                    .create(editDescElement, {
                        toolbar: [
                            'heading', '|',
                            'bold', 'italic', 'underline', '|',
                            'bulletedList', 'numberedList', '|',
                            'outdent', 'indent', '|',
                            'blockQuote', 'insertTable', '|',
                            'link', '|',
                            'undo', 'redo'
                        ],
                        heading: {
                            options: [
                                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                                { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                            ]
                        }
                    })
                    .then(editor => {
                        editDescriptionEditor = editor;
                        window.editDescriptionEditor = editor;
                    })
                    .catch(error => {
                        console.error('CKEditor initialization error for Edit Modal:', error);
                    });
            }

            // Initialize CKEditor for Smart Edit Modal
            let smartEditDescriptionEditor;
            const smartDescElement = document.querySelector('#smart-description');
            if (smartDescElement) {
                ClassicEditor
                    .create(smartDescElement, {
                        toolbar: [
                            'heading', '|',
                            'bold', 'italic', 'underline', '|',
                            'bulletedList', 'numberedList', '|',
                            'outdent', 'indent', '|',
                            'blockQuote', 'insertTable', '|',
                            'link', '|',
                            'undo', 'redo'
                        ],
                        heading: {
                            options: [
                                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                                { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                            ]
                        }
                    })
                    .then(editor => {
                        smartEditDescriptionEditor = editor;
                        window.smartEditDescriptionEditor = editor;
                    })
                    .catch(error => {
                        console.error('CKEditor initialization error for Smart Edit Modal:', error);
                    });
            }
        }, 500); // 500ms delay to ensure DOM is ready

        // Update close modal function to clear CKEditor
        const originalCloseAddModal = closeAddModal;
        closeAddModal = () => {
            if (window.addDescriptionEditor) {
                window.addDescriptionEditor.setData('');
            }
            originalCloseAddModal();
        };
        
        // Add close functionality for Edit Modal
        window.closeEditModal = function() {
            const modal = document.getElementById('edit-raffle-modal');
            if (modal) {
                modal.classList.remove('active');
                
                // Clear CKEditor content
                if (window.editDescriptionEditor) {
                    window.editDescriptionEditor.setData('');
                }
            }
        };
        
        // Add close functionality for Smart Edit Modal
        window.closeSmartEditModal = function() {
            const modal = document.getElementById('smart-edit-modal');
            if (modal) {
                modal.classList.remove('active');
                
                // Clear CKEditor content
                if (window.smartEditDescriptionEditor) {
                    window.smartEditDescriptionEditor.setData('');
                }
            }
        };

        // Dynamic brand filtering based on category selection
        const categorySelect = document.getElementById('add-raffle-category');
        const brandSelect = document.getElementById('add-raffle-brand');
        
        if (categorySelect && brandSelect) {
            categorySelect.addEventListener('change', function() {
                const categoryId = this.value;
                
                // Reset brand selection
                brandSelect.innerHTML = '<option value="">-- Select a Brand --</option>';
                brandSelect.disabled = true;
                brandSelect.classList.remove('brand-loading');
                
                if (categoryId) {
                    // Show loading state
                    brandSelect.innerHTML = '<option value="">Loading brands...</option>';
                    brandSelect.classList.add('brand-loading');
                    
                    // Fetch brands for the selected category
                    fetch(`../api/get_brands_by_category.php?category_id=${categoryId}`)
                        .then(response => response.json())
                        .then(data => {
                            brandSelect.classList.remove('brand-loading');
                            
                            if (data.success && data.brands.length > 0) {
                                brandSelect.innerHTML = '<option value="">-- Select a Brand --</option>';
                                data.brands.forEach(brand => {
                                    const option = document.createElement('option');
                                    option.value = brand.id;
                                    option.textContent = brand.name;
                                    brandSelect.appendChild(option);
                                });
                                brandSelect.disabled = false;
                            } else {
                                // No brands found for this category
                                brandSelect.innerHTML = '<option value="">-- Select a Brand --</option>';
                                const option = document.createElement('option');
                                option.value = '';
                                option.textContent = 'No brands available for this category';
                                option.disabled = true;
                                brandSelect.appendChild(option);
                                brandSelect.disabled = true;
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching brands:', error);
                            brandSelect.classList.remove('brand-loading');
                            brandSelect.innerHTML = '<option value="">-- Select a Brand --</option>';
                            const option = document.createElement('option');
                            option.value = '';
                            option.textContent = 'Error loading brands';
                            option.disabled = true;
                            brandSelect.appendChild(option);
                            brandSelect.disabled = true;
                        });
                }
            });
        }

        // Form validation
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                const title = document.getElementById('add-raffle-title').value.trim();
                const category = document.getElementById('add-raffle-category').value;
                const price = document.getElementById('add-raffle-price').value;
                const totalTickets = document.getElementById('add-raffle-total-tickets').value;

                if (!title) {
                    e.preventDefault();
                    alert('Please enter a raffle title.');
                    return;
                }

                if (!category) {
                    e.preventDefault();
                    alert('Please select a category.');
                    return;
                }

                if (!price || parseFloat(price) <= 0) {
                    e.preventDefault();
                    alert('Please enter a valid ticket price.');
                    return;
                }

                if (!totalTickets || parseInt(totalTickets) <= 0) {
                    e.preventDefault();
                    alert('Please enter a valid number of total entires.');
                    return;
                }

                // Update CKEditor data before form submission
                if (window.addDescriptionEditor) {
                    const editorData = window.addDescriptionEditor.getData();
                    document.getElementById('add-raffle-description').value = editorData;
                }
                
                // Add tags to form data
                if (window.addModalTags && window.addModalTags.length > 0) {
                    const tagsInput = document.createElement('input');
                    tagsInput.type = 'hidden';
                    tagsInput.name = 'tags';
                    tagsInput.value = JSON.stringify(window.addModalTags);
                    addForm.appendChild(tagsInput);
                }
            });
        }
        
        // Form validation for Edit Modal
        const editForm = document.getElementById('edit-raffle-form');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                // Update CKEditor data before form submission
                if (window.editDescriptionEditor) {
                    const editorData = window.editDescriptionEditor.getData();
                    document.getElementById('edit-raffle-description').value = editorData;
                }
            });
        }
    }
    
    function openSmartEditModal() {
        const modal = document.getElementById('smart-edit-modal');
        const form = document.getElementById('smart-edit-form');
        
        if (!modal || !form) {
            console.error('Smart edit modal not found');
            return;
        }
        
        // Set edit mode and raffle IDs
        document.getElementById('smart-edit-mode').value = currentEditMode;
        
        if (currentEditMode === 'single') {
            document.getElementById('smart-raffle-id').value = selectedRaffles[0].id;
            document.getElementById('smart-raffle-ids').value = '';
        } else {
            document.getElementById('smart-raffle-id').value = '';
            document.getElementById('smart-raffle-ids').value = JSON.stringify(selectedRaffles.map(r => r.id));
        }
        
        // Update modal title and description
        updateModalHeader();
        
        // Analyze field uniformity and populate form
        analyzeFieldUniformity();
        
        // Show modal
        modal.classList.add('active');
    }
    
    function updateModalHeader() {
        const title = document.getElementById('smart-edit-title');
        const subtitle = document.getElementById('smart-edit-subtitle');
        const modeTitle = document.getElementById('mode-title');
        const modeDescription = document.getElementById('mode-description');
        const footerTitle = document.getElementById('footer-title');
        const footerSubtitle = document.getElementById('footer-subtitle');
        const saveBtnText = document.getElementById('save-btn-text');
        const tagsSection = document.getElementById('smart-tags-section');
        
        if (currentEditMode === 'single') {
            title.textContent = `Edit Raffle #${selectedRaffles[0].id}`;
            subtitle.textContent = 'Modify raffle details and settings';
            modeTitle.textContent = 'Single Edit Mode';
            modeDescription.textContent = 'Editing one raffle. All fields are editable.';
            footerTitle.textContent = 'Ready to Save Changes?';
            footerSubtitle.textContent = 'Review your changes and save the updated raffle details';
            saveBtnText.textContent = 'Save Changes';
            
            // Show tags section for single edit mode
            if (tagsSection) {
                tagsSection.style.display = 'block';
                // Load tags for the single raffle
                if (tagManager && tagManager.loadRaffleTags) {
                    tagManager.loadRaffleTags(selectedRaffles[0].id);
                }
            }
        } else {
            title.textContent = `Batch Edit ${selectedRaffles.length} Raffles`;
            subtitle.textContent = 'Apply changes to multiple raffles simultaneously';
            modeTitle.textContent = 'Batch Edit Mode';
            modeDescription.textContent = `Editing ${selectedRaffles.length} raffles. Fields with uniform values are editable, mixed values are locked unless overridden.`;
            footerTitle.textContent = 'Ready to Apply Changes?';
            footerSubtitle.textContent = `Review your settings and apply changes to ${selectedRaffles.length} selected raffles`;
            saveBtnText.textContent = 'Apply Changes';
            
            // Hide tags section for batch edit mode
            if (tagsSection) {
                tagsSection.style.display = 'none';
            }
        }
    }
    
    function analyzeFieldUniformity() {
        const fieldNames = ['title', 'description', 'status', 'category_id', 'brand_id', 
                           'ticket_price', 'total_tickets', 'tickets_per_entry', 'draw_date'];
        
        raffleFieldStates = {};
        
        fieldNames.forEach(fieldName => {
            const values = selectedRaffles.map(raffle => raffle[fieldName]);
            const uniqueValues = [...new Set(values)];
            
            raffleFieldStates[fieldName] = {
                uniform: uniqueValues.length === 1,
                value: uniqueValues.length === 1 ? uniqueValues[0] : null,
                values: uniqueValues
            };
        });
        
        // Check raffle types for type guards
        const raffleTypes = selectedRaffles.map(raffle => raffle.raffle_type || 'standard');
        const uniqueTypes = [...new Set(raffleTypes)];
        const mixedTypes = uniqueTypes.length > 1;
        
        // Update UI based on analysis
        updateFieldStates();
        updateTypeGuards(mixedTypes);
        populateFormFields();
    }
    
    function updateFieldStates() {
        const forceOverride = document.getElementById('force-override-mixed');
        const forceOverrideSection = document.getElementById('force-override-section');
        
        let hasMixedFields = false;
        
        Object.keys(raffleFieldStates).forEach(fieldName => {
            const fieldCard = document.querySelector(`[data-field="${fieldName}"]`);
            if (!fieldCard) return;
            
            const field = raffleFieldStates[fieldName];
            const uniformIndicator = fieldCard.querySelector('.field-indicator.uniform');
            const mixedIndicator = fieldCard.querySelector('.field-indicator.mixed');
            const lockedIndicator = fieldCard.querySelector('.field-indicator.locked');
            const mixedNote = fieldCard.querySelector('.mixed-values-note');
            const input = fieldCard.querySelector('.smart-field');
            
            if (currentEditMode === 'single' || field.uniform) {
                // Uniform field - show as editable
                if (uniformIndicator) uniformIndicator.style.display = 'inline';
                if (mixedIndicator) mixedIndicator.style.display = 'none';
                if (lockedIndicator) lockedIndicator.style.display = 'none';
                if (mixedNote) mixedNote.style.display = 'none';
                if (input) {
                    input.disabled = false;
                    input.style.background = 'white';
                    input.style.color = '#374151';
                }
            } else {
                // Mixed field - show as locked unless override is enabled
                hasMixedFields = true;
                const isOverridden = forceOverride && forceOverride.checked;
                
                if (uniformIndicator) uniformIndicator.style.display = 'none';
                if (mixedIndicator) mixedIndicator.style.display = isOverridden ? 'none' : 'inline';
                if (lockedIndicator) lockedIndicator.style.display = isOverridden ? 'none' : 'inline';
                if (mixedNote) mixedNote.style.display = isOverridden ? 'none' : 'block';
                
                if (input) {
                    input.disabled = !isOverridden;
                    input.style.background = isOverridden ? 'white' : '#f8fafc';
                    input.style.color = isOverridden ? '#374151' : '#64748b';
                    if (!isOverridden) {
                        // Handle CKEditor for description field
                        if (fieldName === 'description' && window.smartEditDescriptionEditor) {
                            window.smartEditDescriptionEditor.setData(`Mixed values: ${field.values.join(', ')}`);
                            window.smartEditDescriptionEditor.isReadOnly = true;
                        } else {
                            input.value = `Mixed values: ${field.values.join(', ')}`;
                        }
                    } else {
                        // Re-enable CKEditor if it was disabled
                        if (fieldName === 'description' && window.smartEditDescriptionEditor) {
                            window.smartEditDescriptionEditor.isReadOnly = false;
                        }
                    }
                }
            }
        });
        
        // Show/hide force override section
        if (forceOverrideSection) {
            forceOverrideSection.style.display = hasMixedFields && currentEditMode === 'batch' ? 'block' : 'none';
        }
    }
    
    function updateTypeGuards(mixedTypes) {
        const typeGuardSection = document.getElementById('type-guard-section');
        const ticketSettingsSection = document.getElementById('ticket-settings-section');
        const schedulingSection = document.getElementById('scheduling-section');
        
        if (typeGuardSection) {
            typeGuardSection.style.display = mixedTypes ? 'block' : 'none';
        }
        
        // Lock/unlock sections based on type mixing
        [ticketSettingsSection, schedulingSection].forEach(section => {
            if (section) {
                const guard = section.querySelector('.section-guard');
                const fields = section.querySelectorAll('.smart-field');
                
                if (guard) {
                    guard.style.display = mixedTypes ? 'block' : 'none';
                }
                
                fields.forEach(field => {
                    if (mixedTypes) {
                        field.disabled = true;
                        field.style.background = '#f8fafc';
                        field.style.color = '#64748b';
                    }
                });
            }
        });
    }
    
    function populateFormFields() {
        // Only populate uniform fields or single edit mode
        Object.keys(raffleFieldStates).forEach(fieldName => {
            const field = raffleFieldStates[fieldName];
            const input = document.getElementById(`smart-${fieldName.replace('_', '-')}`);
            
            if (input && (currentEditMode === 'single' || field.uniform)) {
                if (fieldName === 'draw_date' && field.value) {
                    // Handle datetime formatting
                    try {
                        const date = new Date(field.value.replace(' ', 'T'));
                        if (!isNaN(date.getTime())) {
                            const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
                            input.value = localDate.toISOString().slice(0, 16);
                        }
                    } catch (e) {
                        input.value = '';
                    }
                } else if (fieldName === 'description') {
                    // Handle CKEditor for description field
                    if (window.smartEditDescriptionEditor) {
                        window.smartEditDescriptionEditor.setData(field.value || '');
                    } else {
                        input.value = field.value || '';
                    }
                } else {
                    input.value = field.value || '';
                }
            }
        });
    }
    
    function initSmartEditModal() {
        const modal = document.getElementById('smart-edit-modal');
        const form = document.getElementById('smart-edit-form');
        const closeBtn = document.getElementById('smart-modal-close');
        const cancelBtn = document.getElementById('smart-cancel-btn');
        const forceOverride = document.getElementById('force-override-mixed');
        
        // Close modal handlers
        const closeModal = () => {
            if (modal) {
                modal.classList.remove('active');
                form.reset();
                selectedRaffles = [];
                raffleFieldStates = {};
                
                // Clear CKEditor content
                if (window.smartEditDescriptionEditor) {
                    window.smartEditDescriptionEditor.setData('');
                }
            }
        };
        
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
        
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        }
        
        // Force override toggle
        if (forceOverride) {
            forceOverride.addEventListener('change', function() {
                updateFieldStates();
                if (this.checked) {
                    // Clear mixed field values when override is enabled
                    Object.keys(raffleFieldStates).forEach(fieldName => {
                        const field = raffleFieldStates[fieldName];
                        if (!field.uniform) {
                            const input = document.getElementById(`smart-${fieldName.replace('_', '-')}`);
                            if (input) input.value = '';
                        }
                    });
                }
            });
        }
        
        // Form submission handler
        if (form) {
            form.addEventListener('submit', function(e) {
                // Sync CKEditor data before submission
                if (window.smartEditDescriptionEditor) {
                    const editorData = window.smartEditDescriptionEditor.getData();
                    document.getElementById('smart-description').value = editorData;
                }
                
                if (currentEditMode === 'batch') {
                    e.preventDefault();
                    handleBatchSubmission();
                }
                // Single mode will submit normally via the form action
            });
        }
    }
    
    function handleBatchSubmission() {
        const form = document.getElementById('smart-edit-form');
        const formData = new FormData();
        
        // Add raffle IDs
        formData.append('raffle_ids', JSON.stringify(selectedRaffles.map(r => r.id)));
        formData.append('action', 'batch_edit');
        
        // Collect changed fields
        const changedFields = {};
        
        Object.keys(raffleFieldStates).forEach(fieldName => {
            const field = raffleFieldStates[fieldName];
            const input = document.getElementById(`smart-${fieldName.replace('_', '-')}`);
            
            if (input && !input.disabled) {
                let inputValue = input.value;
                
                // Handle CKEditor for description field
                if (fieldName === 'description' && window.smartEditDescriptionEditor) {
                    inputValue = window.smartEditDescriptionEditor.getData();
                }
                
                if (inputValue !== (field.value || '')) {
                    changedFields[fieldName] = inputValue;
                    formData.append(fieldName, inputValue);
                }
            }
        });
        
        if (Object.keys(changedFields).length === 0) {
            alert('No changes detected. Please modify at least one field.');
            return;
        }
        
        // Show loading state
        const submitBtn = document.getElementById('smart-save-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying Changes...';
        submitBtn.disabled = true;
        
        // Submit batch changes
        fetch('<?php echo BASE_URL; ?>/batch_operations.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessMessage(`✅ Successfully updated ${data.processed_count} raffle(s)!`);
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                throw new Error(data.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            console.error('Batch edit error:', error);
            alert('❌ Error: ' + error.message);
        })
        .finally(() => {
            // Restore button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }
    
    function showSuccessMessage(message) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            z-index: 10000;
            font-weight: 600;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    function initBatchEditModal() {
        const batchEditBtn = document.getElementById('batch-edit-btn');
        const batchModal = document.getElementById('batch-edit-modal');
        const batchModalClose = document.getElementById('batch-modal-close');
        const batchCancelBtn = document.getElementById('batch-cancel-btn');
        const batchForm = document.getElementById('batch-edit-form');
        const batchModalCount = document.getElementById('batch-modal-count');

        // Open batch edit modal
        if (batchEditBtn) {
            batchEditBtn.addEventListener('click', function() {
                const selectedIds = getSelectedRaffleIds();
                
                if (selectedIds.length === 0) {
                    alert('Please select at least one raffle to edit.');
                    return;
                }
                
                // Update modal title with count
                if (batchModalCount) {
                    batchModalCount.textContent = `(${selectedIds.length} raffle${selectedIds.length > 1 ? 's' : ''})`;
                }
                
                // Reset form
                resetBatchForm();
                
                // Show modal
                if (batchModal) {
                    batchModal.classList.add('active');
                }
            });
        }

        // Close modal handlers
        const closeBatchModal = () => {
            batchModal.classList.remove('active');
            resetBatchForm();
        };

        if (batchModalClose) {
            batchModalClose.addEventListener('click', closeBatchModal);
        }
        
        if (batchCancelBtn) {
            batchCancelBtn.addEventListener('click', closeBatchModal);
        }

        if (batchModal) {
            batchModal.addEventListener('click', (e) => {
                if (e.target === batchModal) {
                    closeBatchModal();
                }
            });
        }

        // Handle checkbox changes to enable/disable fields
        const updateFieldCheckboxes = document.querySelectorAll('input[name="update_fields[]"]');
        updateFieldCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const fieldName = this.value;
                const isChecked = this.checked;
                const fieldCard = this.closest('.batch-field-card');
                
                // Enable/disable corresponding form fields
                switch (fieldName) {
                    case 'status':
                        document.getElementById('batch-status').disabled = !isChecked;
                        break;
                    case 'category':
                        document.getElementById('batch-category').disabled = !isChecked;
                        break;
                    case 'brand':
                        document.getElementById('batch-brand').disabled = !isChecked;
                        break;
                    case 'price':
                        document.getElementById('price-action').disabled = !isChecked;
                        document.getElementById('price-value').disabled = !isChecked;
                        updatePricePreview();
                        break;
                    case 'tickets_per_entry':
                        document.getElementById('batch-tickets-per-entry').disabled = !isChecked;
                        break;
                    case 'draw_date':
                        document.getElementById('batch-draw-date').disabled = !isChecked;
                        break;
                }
                
                // Update card styling
                if (fieldCard) {
                    if (isChecked) {
                        fieldCard.classList.add('active');
                    } else {
                        fieldCard.classList.remove('active');
                    }
                }
                
                // Update progress
                updateBatchProgress();
            });
        });

        // Add price preview functionality
        function updatePricePreview() {
            const priceAction = document.getElementById('price-action').value;
            const priceValue = parseFloat(document.getElementById('price-value').value) || 0;
            const previewDiv = document.getElementById('price-preview');
            const previewText = document.getElementById('price-preview-text');
            
            if (document.getElementById('update-price').checked && priceValue > 0) {
                let previewMessage = '';
                switch (priceAction) {
                    case 'set':
                        previewMessage = `All selected raffles will have ticket price set to RM ${priceValue.toFixed(2)}`;
                        break;
                    case 'increase':
                        previewMessage = `All selected raffles will have ticket price increased by RM ${priceValue.toFixed(2)}`;
                        break;
                    case 'decrease':
                        previewMessage = `All selected raffles will have ticket price decreased by RM ${priceValue.toFixed(2)}`;
                        break;
                    case 'multiply':
                        previewMessage = `All selected raffles will have ticket price multiplied by ${priceValue}`;
                        break;
                }
                previewText.textContent = previewMessage;
                previewDiv.style.display = 'block';
            } else {
                previewDiv.style.display = 'none';
            }
        }

        // Add event listeners for price fields
        document.getElementById('price-action').addEventListener('change', updatePricePreview);
        document.getElementById('price-value').addEventListener('input', updatePricePreview);

        // Update progress indicator
        function updateBatchProgress() {
            const checkedFields = document.querySelectorAll('input[name="update_fields[]"]:checked').length;
            const totalFields = updateFieldCheckboxes.length;
            const percentage = (checkedFields / totalFields) * 100;
            
            const progressBar = document.getElementById('batch-progress-bar');
            const progressText = document.getElementById('batch-progress-text');
            
            if (progressBar) {
                progressBar.style.width = `${percentage}%`;
            }
            
            if (progressText) {
                progressText.textContent = `${checkedFields} of ${totalFields} fields selected`;
            }
        }

        // Handle form submission
        if (batchForm) {
            batchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const selectedIds = getSelectedRaffleIds();
                const checkedFields = Array.from(document.querySelectorAll('input[name="update_fields[]"]:checked'))
                    .map(cb => cb.value);
                
                if (checkedFields.length === 0) {
                    alert('Please select at least one field to update.');
                    return;
                }
                
                // Prepare batch data
                const batchData = { action: 'batch_edit' };
                
                checkedFields.forEach(field => {
                    switch (field) {
                        case 'status':
                            batchData.status = document.getElementById('batch-status').value;
                            break;
                        case 'category':
                            batchData.category_id = document.getElementById('batch-category').value;
                            break;
                        case 'brand':
                            batchData.brand_id = document.getElementById('batch-brand').value;
                            break;
                        case 'price':
                            batchData.price_action = document.getElementById('price-action').value;
                            batchData.price_value = document.getElementById('price-value').value;
                            break;
                        case 'tickets_per_entry':
                            batchData.tickets_per_entry = document.getElementById('batch-tickets-per-entry').value;
                            break;
                        case 'draw_date':
                            batchData.draw_date = document.getElementById('batch-draw-date').value;
                            break;
                    }
                });
                
                // Confirm changes
                const fieldNames = checkedFields.map(f => f.replace('_', ' ')).join(', ');
                if (!confirm(`Apply changes to ${fieldNames} for ${selectedIds.length} raffle(s)?`)) {
                    return;
                }
                
                // Execute batch operation
                executeBatchEdit(batchData, selectedIds);
            });
        }

        function getSelectedRaffleIds() {
            const checkedBoxes = document.querySelectorAll('.raffle-checkbox:checked');
            return Array.from(checkedBoxes).map(checkbox => checkbox.dataset.raffleId);
        }

        // Add preview functionality
        const previewBtn = document.getElementById('batch-preview-btn');
        if (previewBtn) {
            previewBtn.addEventListener('click', function() {
                showBatchPreview();
            });
        }

        function showBatchPreview() {
            const selectedIds = getSelectedRaffleIds();
            const checkedFields = Array.from(document.querySelectorAll('input[name="update_fields[]"]:checked'));
            const previewSection = document.getElementById('batch-preview-section');
            
            if (checkedFields.length === 0) {
                alert('Please select at least one field to preview changes.');
                return;
            }
            
            let previewHTML = `
                <div class="batch-preview-card">
                    <h4 style="margin: 0 0 16px 0; color: #0369a1; font-size: 16px; font-weight: 700;">
                        <i class="fas fa-eye" style="margin-right: 8px;"></i>
                        Preview Changes for ${selectedIds.length} Raffle${selectedIds.length > 1 ? 's' : ''}
                    </h4>
            `;
            
            checkedFields.forEach(cb => {
                const fieldName = cb.value;
                let fieldValue = '';
                let fieldLabel = '';
                
                switch (fieldName) {
                    case 'status':
                        fieldValue = document.getElementById('batch-status').value;
                        fieldLabel = 'Status';
                        break;
                    case 'category':
                        const categorySelect = document.getElementById('batch-category');
                        fieldValue = categorySelect.options[categorySelect.selectedIndex].text;
                        fieldLabel = 'Category';
                        break;
                    case 'brand':
                        const brandSelect = document.getElementById('batch-brand');
                        fieldValue = brandSelect.options[brandSelect.selectedIndex].text;
                        fieldLabel = 'Brand';
                        break;
                    case 'price':
                        const action = document.getElementById('price-action').value;
                        const value = document.getElementById('price-value').value;
                        fieldValue = `${action} by RM ${value}`;
                        fieldLabel = 'Ticket Price';
                        break;
                    case 'tickets_per_entry':
                        fieldValue = document.getElementById('batch-tickets-per-entry').value + ' tickets';
                        fieldLabel = 'Tickets Per Entry';
                        break;
                    case 'draw_date':
                        const dateValue = document.getElementById('batch-draw-date').value;
                        fieldValue = dateValue ? new Date(dateValue).toLocaleString() : 'Remove scheduled date';
                        fieldLabel = 'Draw Date';
                        break;
                }
                
                previewHTML += `
                    <div class="field-preview">
                        <div class="field-preview-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <strong>${fieldLabel}:</strong>
                        <span>${fieldValue}</span>
                    </div>
                `;
            });
            
            previewHTML += `
                    <div style="margin-top: 16px; padding: 12px; background: rgba(14, 165, 233, 0.1); border-radius: 8px; font-size: 12px; color: #0369a1;">
                        <i class="fas fa-info-circle" style="margin-right: 6px;"></i>
                        These changes will be applied to all ${selectedIds.length} selected raffle${selectedIds.length > 1 ? 's' : ''} when you click "Apply Changes".
                    </div>
                </div>
            `;
            
            previewSection.innerHTML = previewHTML;
            previewSection.style.display = 'block';
            
            // Scroll to preview
            previewSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function resetBatchForm() {
            // Reset form
            if (batchForm) {
                batchForm.reset();
            }
            
            // Uncheck all update field checkboxes and remove active classes
            updateFieldCheckboxes.forEach(cb => {
                cb.checked = false;
                const fieldCard = cb.closest('.batch-field-card');
                if (fieldCard) {
                    fieldCard.classList.remove('active');
                }
                cb.dispatchEvent(new Event('change'));
            });
            
            // Reset progress
            updateBatchProgress();
            
            // Hide preview
            const previewSection = document.getElementById('batch-preview-section');
            if (previewSection) {
                previewSection.style.display = 'none';
            }
        }

        function executeBatchEdit(batchData, raffleIds) {
            const formData = new FormData();
            formData.append('raffle_ids', JSON.stringify(raffleIds));
            
            // Add batch data
            Object.keys(batchData).forEach(key => {
                formData.append(key, batchData[key]);
            });
            
            // Show loading state
            const submitBtn = batchForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying Changes...';
            submitBtn.disabled = true;
            
            fetch('<?php echo BASE_URL; ?>/batch_operations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage(`✅ Successfully updated ${data.processed_count} raffle(s)!`);
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Unknown error occurred');
                }
            })
            .catch(error => {
                console.error('Batch edit error:', error);
                alert('❌ Error: ' + error.message);
            })
            .finally(() => {
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }

        function showSuccessMessage(message) {
            // Create and show success notification
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                padding: 16px 24px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
                z-index: 10000;
                font-weight: 600;
                transform: translateX(100%);
                transition: transform 0.3s ease;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Remove after delay
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
    }
});
</script>

<!-- Edit Raffle Modal -->
<?php include __DIR__ . '/components/raffle-edit-modal.php'; ?>

<!-- Add New Raffle Modal -->
<div id="add-raffle-modal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <div>
                <div class="modal-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <div>
                    <h2><span id="modal-title">Add New Raffle</span></h2>
                    <p>Create a new raffle with all the details</p>
                </div>
            </div>
            <button class="modal-close-btn" id="add-modal-close">&times;</button>
        </div>
        <form id="add-raffle-form" method="POST" action="<?php echo BASE_URL; ?>/add_raffle_action.php" enctype="multipart/form-data">

            
            <!-- Bulk Creation Notice -->
            <div id="bulk-notice" style="background: #e3f2fd; border: 1px solid #2196f3; border-radius: var(--ps-radius); padding: 10px; margin-bottom: 15px; display: none; font-size: 14px;">
                <div style="display: flex; align-items: center; gap: 10px; color: #1976d2;">
                    <i class="fas fa-info-circle"></i>
                    <span id="bulk-notice-text">You are creating 1 raffle with these details.</span>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label for="add-raffle-title">Raffle Title *</label>
                    <input type="text" id="add-raffle-title" name="title" required placeholder="Enter raffle title">
                </div>
                
                <div class="form-group">
                    <label for="add-raffle-category">Category *</label>
                    <select id="add-raffle-category" name="category_id" required>
                        <option value="">-- Select a Category --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label for="add-raffle-brand">Brand <small style="color: #6b7280; font-weight: normal;">(Select category first)</small></label>
                    <select id="add-raffle-brand" name="brand_id" disabled>
                        <option value="">-- Select a Category First --</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="add-raffle-status">Status</label>
                    <select id="add-raffle-status" name="status">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="closed">Closed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="add-raffle-description">Description</label>
                <textarea id="add-raffle-description" name="description" rows="3" placeholder="Enter raffle description..."></textarea>
            </div>

            <div class="form-group">
                <label>Raffle Image</label>
                <div class="image-uploader" id="add-image-uploader" style="padding: 15px; min-height: 100px;">
                    <input type="file" name="image" id="add-raffle-image" accept="image/jpeg, image/png, image/webp">
                    <div class="upload-text">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 20px;"></i>
                        <span>Click to Upload Image</span>
                        <small style="display: block; color: #6c757d; margin-top: 4px; font-size: 11px;">JPG, PNG, WEBP (Max 5MB)</small>
                    </div>
                    <img src="" alt="Image Preview" class="image-preview" id="add-image-preview" style="display: none; max-height: 120px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label for="add-raffle-price">Ticket Price (RM) *</label>
                    <input type="number" id="add-raffle-price" name="ticket_price" step="0.01" min="0.01" required placeholder="1.00" value="1.00">
                </div>
                <div class="form-group">
                    <label for="add-raffle-total-tickets">Total Etires *</label>
                    <input type="number" id="add-raffle-total-tickets" name="total_tickets" min="1" required placeholder="100">
                </div>
                <div class="form-group">
                    <label for="add-raffle-tickets-per-entry">Tickets Per Entry</label>
                    <input type="number" id="add-raffle-tickets-per-entry" name="tickets_per_entry" min="1" value="1" placeholder="1">
                </div>
            </div>

            <div class="form-group">
                <label for="add-raffle-draw-date">Draw Date & Time (Optional)</label>
                <input type="datetime-local" id="add-raffle-draw-date" name="draw_date">
                <small style="color: var(--ps-text-light); font-size: 12px; display: block; margin-top: 4px;">
                    Leave empty for automatic draw when all tickets are sold, or set for scheduled live draw
                </small>
            </div>

            <!-- Tags Section for Add Raffle -->
                <div class="form-group" style="position: relative;">
                <label style="font-size: 14px; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-tag" style="margin-right: 6px; color: #667eea;"></i> Tags
                    <span style="margin-left: auto; display: flex; gap: 10px;">
                        <button type="button" id="add-copy-tags-btn" title="Copy All Tags" style="background: none; border: none; cursor: pointer; font-size: 20px; padding: 2px 6px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                        </button>
                        <button type="button" id="add-delete-tags-btn" title="Delete All Tags" style="background: none; border: none; cursor: pointer; font-size: 24px; padding: 2px 6px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </span>
                </label>
                <div id="add-tag-chip-container" class="tag-chip-container" style="margin-top: 8px;">
                    <input type="text" id="add-tag-input" class="tag-input" placeholder="Type a tag and press space or comma" autocomplete="off" style="flex: 1; min-width: 120px;" />
                    <input type="hidden" name="tags" id="add-tags-hidden" value="" />
                </div>
                <small style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">
                    Please press space or comma after each tag
                </small>
                        </div>
            <style>
            .tag-chip-container {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                min-height: 40px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 8px;
                background: #f9fafb;
                align-items: center;
            }
            .tag-chip {
                display: flex;
                align-items: center;
                background: #e0e7ff;
                color: #3730a3;
                border-radius: 16px;
                padding: 4px 12px 4px 10px;
                font-size: 13px;
                font-weight: 600;
                margin: 2px 0;
            }
            .tag-chip .remove-tag {
                margin-left: 6px;
                cursor: pointer;
                color: #6366f1;
                font-size: 15px;
                font-weight: bold;
                border: none;
                background: none;
                outline: none;
            }
            .tag-input {
                border: none;
                outline: none;
                font-size: 14px;
                padding: 4px 8px;
                min-width: 120px;
                background: transparent;
                flex: 1;
            }
            </style>
            <script>
            (function() {
                let tags = [];
                const tagInput = document.getElementById('add-tag-input');
                const tagContainer = document.getElementById('add-tag-chip-container');
                const tagsHidden = document.getElementById('add-tags-hidden');
                const copyBtn = document.getElementById('add-copy-tags-btn');
                const deleteBtn = document.getElementById('add-delete-tags-btn');
                function renderTags() {
                    tagContainer.querySelectorAll('.tag-chip').forEach(e => e.remove());
                    tags.forEach((tag, idx) => {
                        const chip = document.createElement('span');
                        chip.className = 'tag-chip';
                        chip.textContent = typeof tag === 'string' ? tag : tag.tag_name;
                        const removeBtn = document.createElement('button');
                        removeBtn.className = 'remove-tag';
                        removeBtn.type = 'button';
                        removeBtn.innerHTML = '&times;';
                        removeBtn.onclick = () => {
                            tags.splice(idx, 1);
                            renderTags();
                        };
                        chip.appendChild(removeBtn);
                        tagContainer.insertBefore(chip, tagInput);
                    });
                    // Store as JSON array of objects
                    tagsHidden.value = JSON.stringify(tags.map(tag => {
                        if (typeof tag === 'string') {
                            return { tag_name: tag, tag_type: 'custom' };
                        }
                        return tag;
                    }));
                }
                function addTagsFromString(str) {
                    str.split(',').forEach(raw => {
                        raw.split(' ').forEach(part => {
                            const newTag = part.trim();
                            if (newTag && !tags.some(t => (typeof t === 'string' ? t : t.tag_name) === newTag)) {
                                tags.push({ tag_name: newTag, tag_type: 'custom' });
                            }
                        });
                    });
                    renderTags();
                }
                tagInput.addEventListener('keydown', function(e) {
                    if ((e.key === ' ' || e.key === ',' || e.key === 'Enter') && this.value.trim()) {
                        e.preventDefault();
                        addTagsFromString(this.value);
                        this.value = '';
                    }
                });
                tagInput.addEventListener('paste', function(e) {
                    const paste = (e.clipboardData || window.clipboardData).getData('text');
                    if (paste && (paste.includes(',') || paste.includes(' '))) {
                        e.preventDefault();
                        addTagsFromString(paste);
                        this.value = '';
                    }
                });
                tagInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !this.value && tags.length) {
                        tags.pop();
                        renderTags();
                    }
                });
                // Copy all tags to clipboard
                if (copyBtn) {
                    copyBtn.addEventListener('click', function() {
                        const tagList = tags.map(tag => typeof tag === 'string' ? tag : tag.tag_name).join(', ');
                        if (tagList) {
                            navigator.clipboard.writeText(tagList);
                        }
                    });
                }
                // Delete all tags
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', function() {
                        if (tags.length && confirm('Delete all tags?')) {
                            tags = [];
                            renderTags();
                        }
                    });
                }
                // Expose for external use (pre-populate tags)
                window.setAddModalTags = function(arr) {
                    tags = Array.isArray(arr) ? arr.filter(Boolean).map(tag => {
                        if (typeof tag === 'string') {
                            return { tag_name: tag, tag_type: 'custom' };
                        }
                        return tag;
                    }) : [];
                    renderTags();
                };
                renderTags();
            })();
            </script>
            
            <div style="display: flex; gap: 12px; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--ps-border-light);">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="add-raffle-quantity" style="font-size: 14px; font-weight: 600; color: var(--ps-text); margin: 0;">Qty:</label>
                    <input type="number" id="add-raffle-quantity" name="quantity" min="1" max="50" value="1" required style="width: 60px; padding: 8px; border: 2px solid var(--ps-border); border-radius: var(--ps-radius); text-align: center; font-weight: 600; font-size: 14px;">
                    <small style="color: var(--ps-text-light); font-size: 12px;">max 50</small>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button type="button" class="btn btn-secondary" id="add-cancel-btn">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Raffle
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Smart Edit Modal -->
<?php include __DIR__ . '/components/raffle-smart-edit-modal.php'; ?>

<!-- Enhanced Toggle Switch Styles -->
<style>
/* Enhanced Table Layout Styles */
.raffle-table {
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
    overflow-x: auto;
}

.raffle-table table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: var(--admin-bg-primary);
    /* add this: */
    table-layout: fixed;
}

.raffle-table th,
.raffle-table td {
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
    padding: 10px;
}

.raffle-table th {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    letter-spacing: 0.05em;
    position: sticky;
    top: 0;
    z-index: 10;
}

.raffle-table tbody tr {
    transition: all 0.2s ease;
    cursor: pointer;
}

.raffle-table tbody tr:hover {
    background-color: #f9fafb;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.raffle-table tbody tr:nth-child(even) {
    background: rgba(249, 250, 251, 0.5);
}

/* Responsive table */
@media (max-width: 1400px) {
    .raffle-table {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}

/* Enhanced Toggle Switch Styles */
.toggle-switch input:checked + .toggle-slider {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(26px);
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.batch-field-card.active {
    border-color: #667eea !important;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
    background: linear-gradient(135deg, #f8faff 0%, #f1f5ff 100%) !important;
}

.batch-field-card.active .toggle-slider {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

.batch-field-card.active select,
.batch-field-card.active input {
    border-color: #667eea !important;
    background: white !important;
    color: #374151 !important;
}

.modal-close-btn:hover {
    background: rgba(255, 255, 255, 0.3) !important;
    transform: scale(1.1);
}

.batch-preview-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 1px solid #0ea5e9;
    border-radius: 12px;
    padding: 16px;
    margin: 12px 0;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.field-preview {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 0;
    border-bottom: 1px solid rgba(14, 165, 233, 0.2);
}

.field-preview:last-child {
    border-bottom: none;
}

.field-preview-icon {
    background: #0ea5e9;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5;     }
}

/* Main Filters Container Collapsible Styles */
.main-filters-container {
    transition: all 0.4s ease;
    overflow: hidden;
    max-height: 1000px; /* Adjust based on your content */
    background: white;
    border: 1px solid #e2e8f0;
    border-top: none;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.main-filters-container.collapsed {
    max-height: 0;
    opacity: 0;
    transform: translateY(-10px);
    margin-top: 0;
    margin-bottom: 0;
    padding-top: 0;
    padding-bottom: 0;
    border: none;
    box-shadow: none;
}

/* Compact Filter Header Styles */
.filter-header-compact {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 0;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.filter-header-compact:hover {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border-color: #cbd5e1;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.filter-compact-content {
    display: flex;
    align-items: center;
    gap: 16px;
}

.filter-compact-icon {
    background: #3b82f6;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
}

.filter-compact-text {
    flex: 1;
}

.filter-compact-title {
    margin: 0 0 4px 0;
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.2;
}

.filter-compact-subtitle {
    margin: 0;
    font-size: 14px;
    color: #6b7280;
    line-height: 1.4;
}

.filter-compact-toggle {
    display: flex;
    align-items: center;
}

.filter-compact-toggle .toggle-main-filters-btn {
    background: transparent !important;
    border: none !important;
    color: #6b7280 !important;
    font-size: 18px !important;
    padding: 8px !important;
    border-radius: 6px !important;
    transition: all 0.3s ease !important;
    cursor: pointer !important;
}

.filter-compact-toggle .toggle-main-filters-btn:hover {
    background: rgba(59, 130, 246, 0.1) !important;
    color: #3b82f6 !important;
    transform: translateY(-1px) !important;
}

/* Expanded Filter Header Styles */
.filter-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px 12px 0 0;
    margin-bottom: 0;
}

.filter-title {
    margin: 0 0 6px 0;
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-subtitle {
    margin: 0;
    font-size: 14px;
    color: #6b7280;
    line-height: 1.4;
}

.filter-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Main Filters Content Padding */
.main-filters-content {
    padding: 24px;
}

.toggle-main-filters-btn {
    background: #10b981 !important;
    color: white !important;
    border: none !important;
    padding: 8px 16px !important;
    border-radius: 6px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
}

.toggle-main-filters-btn:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3) !important;
}

.toggle-main-filters-btn:active {
    transform: translateY(0) !important;
}

/* Compact Summary Styles */
.compact-summary {
    padding: 12px 20px;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 8px;
    margin-top: 16px;
    border: 1px solid #e2e8f0;
    animation: slideIn 0.3s ease;
}

.compact-info {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: #374151;
}

.compact-info i {
    color: #6366f1;
}

.active-filters-indicator {
    background: #fef3c7;
    color: #92400e;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid #fde68a;
    display: flex;
    align-items: center;
    gap: 4px;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Enhanced button hover effects */
#batch-cancel-btn:hover {
    background: #f8fafc !important;
    border-color: #cbd5e1 !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

#batch-preview-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4) !important;
}

#batch-apply-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
}

/* Card hover effects */
.batch-field-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

/* Toggle switch animations */
.toggle-switch:hover .toggle-slider {
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Smooth transitions for all interactive elements */
.batch-field-card,
.toggle-slider,
button,
select,
input {
    transition: all 0.3s ease;
}

/* Loading state for apply button */
#batch-apply-btn.loading {
    background: linear-gradient(135deg, #9ca3af, #6b7280) !important;
    cursor: not-allowed !important;
    transform: none !important;
    box-shadow: none !important;
}

#batch-apply-btn.loading .fas {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Enhanced Table Styles */
.raffle-table table {
    border-collapse: separate;
    border-spacing: 0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.raffle-table tr {
    transition: all 0.2s ease;
}

.raffle-table tbody tr:hover {
    background: #f8fafc !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.raffle-table tbody tr:nth-child(even) {
    background: #fafbfc;
}

/* Filter Input Focus States */
.filter-group input:focus,
.filter-group select:focus {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
    outline: none;
}

/* Enhanced Button Hover Effects */
#clear-filters-btn:hover {
    background: #e5e7eb !important;
    transform: translateY(-1px);
}

#toggle-advanced-filters:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

#toggle-main-filters:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

/* Status Badge Enhancements */
.status-badge.active {
    background: #dcfce7 !important;
    color: #166534 !important;
    border: 1px solid #bbf7d0 !important;
}

.status-badge.draft {
    background: #fef3c7 !important;
    color: #92400e !important;
    border: 1px solid #fde68a !important;
}

.status-badge.closed {
    background: #fee2e2 !important;
    color: #991b1b !important;
    border: 1px solid #fecaca !important;
}

.status-badge.cancelled {
    background: #f3f4f6 !important;
    color: #374151 !important;
    border: 1px solid #d1d5db !important;
}

/* Smart Row Edit Button Styles */
.smart-row-edit-btn {
    transition: all 0.3s ease !important;
    position: relative;
    overflow: hidden;
}

.smart-row-edit-btn:hover {
    transform: scale(1.08) !important;
    filter: brightness(1.1);
}

.smart-row-edit-btn:active {
    transform: scale(0.98) !important;
}

.smart-row-edit-btn .btn-text {
    transition: all 0.2s ease;
}

/* Smart Edit Modal Styles */
.smart-edit-modal {
    max-width: 900px;
}

.smart-field-section {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
}

.smart-section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    justify-content: space-between;
}

.smart-section-icon {
    background: #f1f5f9;
    padding: 8px;
    border-radius: 8px;
    color: #667eea;
    font-size: 16px;
}

.smart-section-title {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #374151;
}

.smart-section-subtitle {
    margin: 2px 0 0 0;
    font-size: 14px;
    color: #6b7280;
}

.smart-form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.smart-form-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
}

.smart-field-card {
    position: relative;
    transition: all 0.3s ease;
    margin-top: 20px;
}

.smart-field-card[data-field] {
    border-radius: 8px;
    padding: 16px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
}

.field-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.field-header label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin: 0;
}

.field-status {
    display: flex;
    gap: 8px;
}

.field-indicator {
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 600;
}

.field-indicator.uniform {
    background: #dcfce7;
    color: #166534;
}

.field-indicator.mixed {
    background: #fef3c7;
    color: #92400e;
}

.field-indicator.locked {
    background: #fee2e2;
    color: #991b1b;
}

.smart-field {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.smart-field:focus {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
    outline: none;
}

.smart-field:disabled {
    cursor: not-allowed;
}

.mixed-values-note {
    margin-top: 6px;
    font-size: 12px;
    color: #92400e;
}

.unlock-hint {
    font-weight: 600;
    color: #d97706;
}

.field-help {
    margin-top: 6px;
}

.field-help small {
    font-size: 12px;
    color: #6b7280;
}

.section-guard {
    color: #ef4444;
    font-size: 18px;
}

.smart-edit-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.smart-edit-btn:disabled {
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
}

.smart-edit-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

/* Force override section styling */
.force-override-toggle:hover {
    background: rgba(245, 158, 11, 0.1);
    border-radius: 8px;
    padding: 4px;
    margin: -4px;
}

/* Type guard section animation */
#type-guard-section {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive design */
@media (max-width: 768px) {
    .smart-form-grid-2,
    .smart-form-grid-3 {
        grid-template-columns: 1fr;
    }
    
    .smart-edit-modal .modal-container {
        margin: 10px;
        max-height: calc(100vh - 20px);
        overflow-y: auto;
    }
    
    .raffle-table {
        overflow-x: auto;
    }
    
    .raffle-table table {
        min-width: 800px;
    }
    
    /* Mobile-specific filter improvements */
    .filter-actions {
        flex-direction: column;
        gap: 8px;
        align-items: stretch;
    }
    
    .filter-actions button {
        width: 100%;
        justify-content: center;
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .compact-summary {
        padding: 10px 16px;
    }
    
    .compact-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .toggle-main-filters-btn {
        font-size: 12px !important;
        padding: 6px 12px !important;
    }
    
    /* Compact header mobile adjustments */
    .filter-header-compact {
        padding: 12px 16px;
        margin-bottom: 16px;
    }
    
    .filter-compact-content {
        gap: 12px;
    }
    
    .filter-compact-icon {
        width: 36px;
        height: 36px;
        font-size: 14px;
    }
    
    .filter-compact-title {
        font-size: 16px;
    }
    
    .filter-compact-subtitle {
        font-size: 13px;
    }
    
    .filter-compact-toggle .toggle-main-filters-btn {
        font-size: 16px !important;
        padding: 6px !important;
    }
}

/* CKEditor Styles in Modals */
.modal-container .ck-editor__editable {
    min-height: 150px;
    max-height: 300px;
}

.modal-container .ck-editor {
    width: 100%;
}

.smart-field-card .ck-editor__editable {
    min-height: 120px;
    max-height: 250px;
}

.smart-field-card .ck-toolbar {
    border-radius: 8px 8px 0 0;
}

.smart-field-card .ck-editor__editable {
    border-radius: 0 0 8px 8px;
}

/* CKEditor focus states */
.ck-editor__editable:focus {
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
}

.col-checkbox { width: 80px; text-align: center; }
.col-details { width: 220px; }
.col-category { width: 120px; text-align: center; }
.col-brand { width: 120px; text-align: center; }
.col-price { width: 110px; text-align: center; }
.col-sales { width: 150px; text-align: center; }
.col-entry { width: 90px; text-align: center; }
.col-status { width: 110px; text-align: center; }
.col-actions { width: 90px; text-align: center; }

/* Ensure the checkbox column never collapses */
.raffle-table th.col-checkbox,
.raffle-table td.col-checkbox {
    width: 80px !important;
    min-width: 80px !important;
    max-width: 80px !important;
    box-sizing: border-box;
}

/* 2. One true 80 px column (border included) */
.raffle-table th.col-checkbox,
.raffle-table td.col-checkbox {
    width: 80px;              /* border-box is default in your reset */
}

.checkbox-header,
.checkbox-column {
    width: 80px;
    text-align: center;
    background: var(--admin-gray-50);
    border-right: 2px solid var(--admin-border-light);
    box-sizing: border-box;
}

/* Disabled select styling for brand dropdown */
select:disabled {
    background-color: #f9fafb !important;
    color: #9ca3af !important;
    cursor: not-allowed !important;
    border-color: #d1d5db !important;
}

select:disabled option {
    color: #9ca3af !important;
}

/* Brand dropdown loading state */
.brand-loading {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%236b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>') !important;
    background-repeat: no-repeat !important;
    background-position: right 8px center !important;
    background-size: 16px !important;
    animation: spin 1s linear infinite !important;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<!-- CKEditor 5 Script -->
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

<?php include __DIR__ . '/../inc/footer.php'; ?> 