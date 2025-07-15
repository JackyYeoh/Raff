<?php 
include __DIR__ . '/../inc/header.php';

// Get workflow status
try {
    // Check categories
    $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active FROM categories");
    $category_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check brands
    $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured FROM brands");
    $brand_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check brand-category relationships
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM brand_categories");
    $relationship_count = $stmt->fetchColumn();
    
    // Check categories with brands enabled
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categories WHERE show_brands = 1");
    $brand_enabled_categories = $stmt->fetchColumn();
    
    // Check raffles
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM raffles");
    $raffle_count = $stmt->fetchColumn();
    
    // Check for orphaned brands (brands without categories)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM brands b LEFT JOIN brand_categories bc ON b.id = bc.brand_id WHERE bc.brand_id IS NULL");
    $orphaned_brands = $stmt->fetchColumn();
    
    // Check for categories without brands
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categories c LEFT JOIN brand_categories bc ON c.id = bc.category_id WHERE bc.category_id IS NULL AND c.show_brands = 1");
    $categories_without_brands = $stmt->fetchColumn();
    
} catch (Exception $e) {
    $error_message = "Error checking workflow status: " . $e->getMessage();
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
        <div style="margin-bottom: 30px;">
            <h1>Workflow Status</h1>
            <p style="color: var(--ps-text-light); margin-top: 5px; font-size: 14px;">
                Monitor and manage your raffle platform setup workflow
            </p>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div style="background: var(--ps-error-light); color: var(--ps-error); padding: 15px; border-radius: var(--ps-radius); margin-bottom: 20px; border: 1px solid var(--ps-error);">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Workflow Overview -->
        <div style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border: 1px solid #cbd5e1; border-radius: var(--ps-radius-lg); padding: 25px; margin-bottom: 30px;">
            <h3 style="color: #374151; font-size: 18px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-project-diagram" style="color: #3b82f6;"></i>
                Workflow Overview
            </h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <!-- Step 1: Categories -->
                <div style="background: white; padding: 20px; border-radius: var(--ps-radius); border: 1px solid #e2e8f0; position: relative;">
                    <div style="position: absolute; top: -10px; left: 20px; background: #3b82f6; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600;">1</div>
                    <h4 style="color: #374151; font-size: 16px; font-weight: 600; margin-bottom: 10px;">Categories Setup</h4>
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="font-size: 14px; color: #6b7280;">Total Categories:</span>
                            <span style="font-weight: 600; color: #374151;"><?php echo $category_stats['total']; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="font-size: 14px; color: #6b7280;">Active Categories:</span>
                            <span style="font-weight: 600; color: #10b981;"><?php echo $category_stats['active']; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-size: 14px; color: #6b7280;">Brand Display Enabled:</span>
                            <span style="font-weight: 600; color: #f59e0b;"><?php echo $brand_enabled_categories; ?></span>
                        </div>
                    </div>
                    <a href="categories.php" class="btn btn-primary" style="width: 100%; text-align: center; padding: 10px; border-radius: var(--ps-radius); text-decoration: none; display: block;">
                        <i class="fas fa-cog"></i> Manage Categories
                    </a>
                </div>
                
                <!-- Step 2: Brands -->
                <div style="background: white; padding: 20px; border-radius: var(--ps-radius); border: 1px solid #e2e8f0; position: relative;">
                    <div style="position: absolute; top: -10px; left: 20px; background: #10b981; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600;">2</div>
                    <h4 style="color: #374151; font-size: 16px; font-weight: 600; margin-bottom: 10px;">Brands Setup</h4>
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="font-size: 14px; color: #6b7280;">Total Brands:</span>
                            <span style="font-weight: 600; color: #374151;"><?php echo $brand_stats['total']; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="font-size: 14px; color: #6b7280;">Featured Brands:</span>
                            <span style="font-weight: 600; color: #f59e0b;"><?php echo $brand_stats['featured']; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-size: 14px; color: #6b7280;">Category Relationships:</span>
                            <span style="font-weight: 600; color: #3b82f6;"><?php echo $relationship_count; ?></span>
                        </div>
                    </div>
                    <a href="brands.php" class="btn btn-primary" style="width: 100%; text-align: center; padding: 10px; border-radius: var(--ps-radius); text-decoration: none; display: block;">
                        <i class="fas fa-building"></i> Manage Brands
                    </a>
                </div>
                
                <!-- Step 3: Raffles -->
                <div style="background: white; padding: 20px; border-radius: var(--ps-radius); border: 1px solid #e2e8f0; position: relative;">
                    <div style="position: absolute; top: -10px; left: 20px; background: #f59e0b; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600;">3</div>
                    <h4 style="color: #374151; font-size: 16px; font-weight: 600; margin-bottom: 10px;">Raffles Creation</h4>
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="font-size: 14px; color: #6b7280;">Total Raffles:</span>
                            <span style="font-weight: 600; color: #374151;"><?php echo $raffle_count; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="font-size: 14px; color: #6b7280;">Ready to Create:</span>
                            <span style="font-weight: 600; color: #10b981;"><?php echo ($category_stats['active'] > 0 && $brand_stats['total'] > 0) ? 'Yes' : 'No'; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-size: 14px; color: #6b7280;">Dynamic Filtering:</span>
                            <span style="font-weight: 600; color: #3b82f6;"><?php echo ($relationship_count > 0) ? 'Active' : 'Inactive'; ?></span>
                        </div>
                    </div>
                    <a href="raffles.php" class="btn btn-primary" style="width: 100%; text-align: center; padding: 10px; border-radius: var(--ps-radius); text-decoration: none; display: block;">
                        <i class="fas fa-trophy"></i> Manage Raffles
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Issues & Recommendations -->
        <div style="background: white; border-radius: var(--ps-radius-lg); padding: 25px; margin-bottom: 30px; box-shadow: var(--ps-shadow-lg); border: 1px solid var(--ps-border-light);">
            <h3 style="color: #374151; font-size: 18px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                Issues & Recommendations
            </h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <?php if ($orphaned_brands > 0): ?>
                <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: var(--ps-radius); padding: 15px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <i class="fas fa-exclamation-circle" style="color: #f59e0b;"></i>
                        <strong style="color: #92400e;">Orphaned Brands</strong>
                    </div>
                    <p style="color: #92400e; font-size: 14px; margin: 0 0 10px 0;">
                        <?php echo $orphaned_brands; ?> brand(s) are not assigned to any categories.
                    </p>
                    <a href="brands.php" class="btn btn-warning" style="padding: 8px 16px; font-size: 14px; text-decoration: none;">
                        <i class="fas fa-edit"></i> Fix Now
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($categories_without_brands > 0): ?>
                <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: var(--ps-radius); padding: 15px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <i class="fas fa-exclamation-circle" style="color: #f59e0b;"></i>
                        <strong style="color: #92400e;">Empty Categories</strong>
                    </div>
                    <p style="color: #92400e; font-size: 14px; margin: 0 0 10px 0;">
                        <?php echo $categories_without_brands; ?> category(ies) have brand display enabled but no brands assigned.
                    </p>
                    <a href="brands.php" class="btn btn-warning" style="padding: 8px 16px; font-size: 14px; text-decoration: none;">
                        <i class="fas fa-plus"></i> Add Brands
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($category_stats['total'] == 0): ?>
                <div style="background: #fee2e2; border: 1px solid #ef4444; border-radius: var(--ps-radius); padding: 15px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <i class="fas fa-times-circle" style="color: #ef4444;"></i>
                        <strong style="color: #991b1b;">No Categories</strong>
                    </div>
                    <p style="color: #991b1b; font-size: 14px; margin: 0 0 10px 0;">
                        You need to create categories before adding brands and raffles.
                    </p>
                    <a href="categories.php" class="btn btn-danger" style="padding: 8px 16px; font-size: 14px; text-decoration: none;">
                        <i class="fas fa-plus"></i> Create Categories
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($brand_stats['total'] == 0 && $category_stats['total'] > 0): ?>
                <div style="background: #fee2e2; border: 1px solid #ef4444; border-radius: var(--ps-radius); padding: 15px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <i class="fas fa-times-circle" style="color: #ef4444;"></i>
                        <strong style="color: #991b1b;">No Brands</strong>
                    </div>
                    <p style="color: #991b1b; font-size: 14px; margin: 0 0 10px 0;">
                        You need to create brands and assign them to categories.
                    </p>
                    <a href="brands.php" class="btn btn-danger" style="padding: 8px 16px; font-size: 14px; text-decoration: none;">
                        <i class="fas fa-plus"></i> Create Brands
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($orphaned_brands == 0 && $categories_without_brands == 0 && $category_stats['total'] > 0 && $brand_stats['total'] > 0): ?>
                <div style="background: #d1fae5; border: 1px solid #10b981; border-radius: var(--ps-radius); padding: 15px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <i class="fas fa-check-circle" style="color: #10b981;"></i>
                        <strong style="color: #065f46;">All Set!</strong>
                    </div>
                    <p style="color: #065f46; font-size: 14px; margin: 0 0 10px 0;">
                        Your workflow is properly configured. You can now create raffles with dynamic filtering.
                    </p>
                    <a href="raffles.php" class="btn btn-success" style="padding: 8px 16px; font-size: 14px; text-decoration: none;">
                        <i class="fas fa-plus"></i> Create Raffles
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Setup Actions -->
        <div style="background: white; border-radius: var(--ps-radius-lg); padding: 25px; box-shadow: var(--ps-shadow-lg); border: 1px solid var(--ps-border-light);">
            <h3 style="color: #374151; font-size: 18px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-rocket" style="color: #3b82f6;"></i>
                Quick Setup Actions
            </h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                <a href="../setup-sample-data.php" class="btn btn-primary" style="padding: 15px; border-radius: var(--ps-radius); text-decoration: none; display: flex; align-items: center; gap: 10px; text-align: left;">
                    <i class="fas fa-database" style="font-size: 18px;"></i>
                    <div>
                        <div style="font-weight: 600;">Setup Sample Data</div>
                        <div style="font-size: 12px; opacity: 0.8;">Create sample categories and brands</div>
                    </div>
                </a>
                
                <a href="categories.php" class="btn btn-secondary" style="padding: 15px; border-radius: var(--ps-radius); text-decoration: none; display: flex; align-items: center; gap: 10px; text-align: left;">
                    <i class="fas fa-tags" style="font-size: 18px;"></i>
                    <div>
                        <div style="font-weight: 600;">Manage Categories</div>
                        <div style="font-size: 12px; opacity: 0.8;">Create and configure categories</div>
                    </div>
                </a>
                
                <a href="brands.php" class="btn btn-accent" style="padding: 15px; border-radius: var(--ps-radius); text-decoration: none; display: flex; align-items: center; gap: 10px; text-align: left;">
                    <i class="fas fa-building" style="font-size: 18px;"></i>
                    <div>
                        <div style="font-weight: 600;">Manage Brands</div>
                        <div style="font-size: 12px; opacity: 0.8;">Add brands and assign categories</div>
                    </div>
                </a>
                
                <a href="raffles.php" class="btn btn-warning" style="padding: 15px; border-radius: var(--ps-radius); text-decoration: none; display: flex; align-items: center; gap: 10px; text-align: left;">
                    <i class="fas fa-trophy" style="font-size: 18px;"></i>
                    <div>
                        <div style="font-weight: 600;">Create Raffles</div>
                        <div style="font-size: 12px; opacity: 0.8;">Start creating raffles with filtering</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
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
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?> 