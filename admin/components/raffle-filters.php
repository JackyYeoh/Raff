<?php
// Raffle Filters Component
// This component handles all filtering and search functionality for the raffle management page

// Get filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$category_filter = $_GET['category'] ?? 'all';
$brand_filter = $_GET['brand'] ?? 'all';
$price_range = $_GET['price_range'] ?? 'all';
$date_range = $_GET['date_range'] ?? 'all';
$sort_by = $_GET['sort_by'] ?? 'created_at';
$sort_order = $_GET['sort_order'] ?? 'DESC';
$tickets_filter = $_GET['tickets_filter'] ?? 'all';
?>

<!-- ENHANCED FILTER SYSTEM -->
<div class="raffle-management-controls">
    <form id="raffle-filters" method="GET" action="">
        <!-- Compact Filter Header (Default State) -->
        <div class="filter-header-compact" id="filter-header-compact">
            <div class="filter-compact-content">
                <div class="filter-compact-icon">
                    <i class="fas fa-filter"></i>
                </div>
                <div class="filter-compact-text">
                    <h3 class="filter-compact-title">Advanced Filters & Search</h3>
                    <p class="filter-compact-subtitle">Find exactly what you're looking for with powerful filtering options</p>
                </div>
            </div>
            <div class="filter-compact-toggle">
                <button type="button" id="toggle-main-filters" class="toggle-main-filters-btn">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        </div>

        <!-- Expanded Filter Header (Hidden by Default) -->
        <div class="filter-header" id="filter-header-expanded" style="display: none;">
            <div>
                <h3 class="filter-title">
                    <i class="fas fa-filter"></i>
                    Advanced Filters & Search
                </h3>
                <p class="filter-subtitle">
                    Find exactly what you're looking for with powerful filtering options
                </p>
            </div>
            <div class="filter-actions">
                <button type="button" id="clear-filters-btn" class="clear-filters-btn">
                    <i class="fas fa-times"></i> Clear All
                </button>
                <button type="button" id="toggle-main-filters-expanded" class="toggle-main-filters-btn">
                    <i class="fas fa-chevron-up"></i> Collapse
                </button>
                <button type="button" id="toggle-advanced-filters" class="toggle-advanced-btn">
                    <i class="fas fa-cog"></i> Advanced
                </button>
            </div>
        </div>

        <!-- Main Filters Container (Collapsible) -->
        <div id="main-filters-container" class="main-filters-container collapsed">
            <div class="main-filters-content">
                <!-- Primary Filters Row -->
                <div class="filter-grid">
                    <!-- Enhanced Search -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-search"></i> Search Raffles
                        </label>
                        <div style="position: relative;">
                            <input type="text" name="search" id="raffle-search" class="filter-input search-input" placeholder="Search by title, description, or ID..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-toggle-on"></i> Status
                        </label>
                        <select name="status" id="status-filter" class="filter-select">
                            <option value="all">All Status</option>
                            <option value="active" <?php echo ($status_filter === 'active') ? 'selected' : ''; ?>>🟢 Active</option>
                            <option value="draft" <?php echo ($status_filter === 'draft') ? 'selected' : ''; ?>>🟡 Draft</option>
                            <option value="closed" <?php echo ($status_filter === 'closed') ? 'selected' : ''; ?>>🔴 Closed</option>
                            <option value="cancelled" <?php echo ($status_filter === 'cancelled') ? 'selected' : ''; ?>>⚫ Cancelled</option>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-tags"></i> Category
                        </label>
                        <select name="category" id="category-filter" class="filter-select">
                            <option value="all">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Sort Options -->
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-sort"></i> Sort By
                        </label>
                        <div style="display: flex; gap: 4px;">
                            <select name="sort_by" id="sort-by" class="filter-select" style="flex: 1; padding: 12px 8px; font-size: 13px;">
                                <option value="created_at" <?php echo ($sort_by === 'created_at') ? 'selected' : ''; ?>>Date</option>
                                <option value="title" <?php echo ($sort_by === 'title') ? 'selected' : ''; ?>>Title</option>
                                <option value="ticket_price" <?php echo ($sort_by === 'ticket_price') ? 'selected' : ''; ?>>Price</option>
                                <option value="sold_tickets" <?php echo ($sort_by === 'sold_tickets') ? 'selected' : ''; ?>>Sales</option>
                                <option value="status" <?php echo ($sort_by === 'status') ? 'selected' : ''; ?>>Status</option>
                            </select>
                            <button type="button" id="sort-order-btn" data-order="<?php echo $sort_order; ?>" class="btn btn-secondary" style="padding: 12px;">
                                <i class="fas fa-sort-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?>"></i>
                            </button>
                            <input type="hidden" name="sort_order" id="sort-order" value="<?php echo $sort_order; ?>">
                        </div>
                    </div>
                </div>

                <!-- Advanced Filters (Collapsible) -->
                <div id="advanced-filters" style="display: none;">  
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                        <!-- Brand Filter -->
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-crown"></i> Brand
                            </label>
                            <select name="brand" id="brand-filter" class="filter-select">
                                <option value="all">All Brands</option>
                                <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['id']; ?>" <?php echo ($brand_filter == $brand['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brand['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Price Range Filter -->
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-dollar-sign"></i> Price Range
                            </label>
                            <select name="price_range" id="price-range-filter" class="filter-select">
                                <option value="all">All Prices</option>
                                <option value="rm1_only" <?php echo ($price_range === 'rm1_only') ? 'selected' : ''; ?>>RM1 Only (Recommended)</option>
                                <option value="under_5" <?php echo ($price_range === 'under_5') ? 'selected' : ''; ?>>Under RM 5</option>
                                <option value="5_10" <?php echo ($price_range === '5_10') ? 'selected' : ''; ?>>RM 5 - 10</option>
                                <option value="over_10" <?php echo ($price_range === 'over_10') ? 'selected' : ''; ?>>Over RM 10</option>
                            </select>
                        </div>

                        <!-- Tickets Sales Filter -->
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-chart-bar"></i> Sales Performance
                            </label>
                            <select name="tickets_filter" id="tickets-filter" class="filter-select">
                                <option value="all">All Sales</option>
                                <option value="no_sales" <?php echo ($tickets_filter === 'no_sales') ? 'selected' : ''; ?>>🔴 No Sales</option>
                                <option value="low_sales" <?php echo ($tickets_filter === 'low_sales') ? 'selected' : ''; ?>>🟡 Low Sales (&lt;25%)</option>
                                <option value="medium_sales" <?php echo ($tickets_filter === 'medium_sales') ? 'selected' : ''; ?>>🟠 Medium Sales (25-75%)</option>
                                <option value="high_sales" <?php echo ($tickets_filter === 'high_sales') ? 'selected' : ''; ?>>🟢 High Sales (&gt;75%)</option>
                                <option value="sold_out" <?php echo ($tickets_filter === 'sold_out') ? 'selected' : ''; ?>>🔥 Sold Out</option>
                            </select>
                        </div>

                        <!-- Date Range Filter -->
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-calendar"></i> Created Date
                            </label>
                            <select name="date_range" id="date-range-filter" class="filter-select">
                                <option value="all">All Time</option>
                                <option value="today" <?php echo ($date_range === 'today') ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?php echo ($date_range === 'week') ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="month" <?php echo ($date_range === 'month') ? 'selected' : ''; ?>>Last 30 Days</option>
                                <option value="quarter" <?php echo ($date_range === 'quarter') ? 'selected' : ''; ?>>Last 90 Days</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Results Summary -->
                <div id="filter-summary">
                    <i class="fas fa-info-circle"></i>
                    <span id="results-count">Showing <?php echo count($raffles); ?> raffle<?php echo count($raffles) !== 1 ? 's' : ''; ?></span>
                    <span id="active-filters"></span>
                </div>
            </div> <!-- End main-filters-content -->
        </div> <!-- End main-filters-container -->
        
        <!-- Compact Summary (Always Visible) -->
        <div id="compact-summary" class="compact-summary">
            <div class="compact-info">
                <i class="fas fa-info-circle"></i>
                <span>Showing <strong><?php echo count($raffles); ?></strong> raffle<?php echo count($raffles) !== 1 ? 's' : ''; ?></span>
                <?php if (!empty($search) || $status_filter !== 'all' || $category_filter !== 'all' || $brand_filter !== 'all' || $price_range !== 'all' || $tickets_filter !== 'all' || $date_range !== 'all'): ?>
                    <span class="active-filters-indicator">
                        <i class="fas fa-filter"></i> Filters Active
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div> 