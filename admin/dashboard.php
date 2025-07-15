<?php 
include __DIR__ . '/../inc/header.php';

// Get statistics with error handling
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM raffles");
    $total_raffles = $stmt->fetchColumn();
} catch (PDOException $e) {
    $total_raffles = 0;
    error_log("Raffles count query failed: " . $e->getMessage());
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt->fetchColumn();
} catch (PDOException $e) {
    $total_users = 0;
    error_log("Users count query failed: " . $e->getMessage());
}

try {
    $stmt = $pdo->query("SELECT SUM(retail_value) as total_value FROM raffles WHERE status = 'completed'");
    $total_prize_value = $stmt->fetchColumn() ?: 0;
} catch (PDOException $e) {
    $total_prize_value = 0;
    error_log("Prize value query failed: " . $e->getMessage());
}

try {
    $stmt = $pdo->query("SELECT SUM(sold_tickets) FROM raffles");
    $total_tickets_sold = $stmt->fetchColumn() ?: 0;
} catch (PDOException $e) {
    $total_tickets_sold = 0;
    error_log("Tickets sold query failed: " . $e->getMessage());
}

// Get recent winners - with error handling
try {
    $stmt = $pdo->query("SELECT w.*, r.title as raffle_title, u.name as winner_name FROM winners w JOIN raffles r ON w.raffle_id = r.id JOIN users u ON w.user_id = u.id ORDER BY w.win_date DESC LIMIT 5");
    $recent_winners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If tables don't exist or have issues, provide empty array
    $recent_winners = [];
    error_log("Winners query failed: " . $e->getMessage());
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
        <h1>Welcome to the Admin Dashboard</h1>
        
        <!-- KPI WIDGETS -->
        <div class="kpi-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px; margin-bottom: 40px;">
            <div class="kpi-card" style="background: var(--ps-white); border-radius: var(--ps-radius-xl); padding: 30px; box-shadow: var(--ps-shadow-lg); border: 1px solid var(--ps-border-light); position: relative; overflow: hidden;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div style="width: 60px; height: 60px; border-radius: var(--ps-radius-lg); background: var(--ps-blue-gradient); color: var(--ps-white); display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: var(--ps-shadow-lg);"><i class="fas fa-trophy"></i></div>
                    <div style="font-size: 14px; font-weight: 600; padding: 4px 12px; border-radius: 20px; background: rgba(16,185,129,0.1); color: var(--ps-success); display: flex; align-items: center; gap: 4px;"><i class="fas fa-arrow-up"></i>+12%</div>
                </div>
                <div style="font-family: 'Poppins', sans-serif; font-size: 36px; font-weight: 800; color: var(--ps-text); margin-bottom: 8px;"><?php echo $total_raffles; ?></div>
                <div style="color: var(--ps-text-light); font-size: 16px; font-weight: 500;">Total Raffles</div>
            </div>
            <div class="kpi-card" style="background: var(--ps-white); border-radius: var(--ps-radius-xl); padding: 30px; box-shadow: var(--ps-shadow-lg); border: 1px solid var(--ps-border-light); position: relative; overflow: hidden;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div style="width: 60px; height: 60px; border-radius: var(--ps-radius-lg); background: var(--ps-yellow-gradient); color: var(--ps-white); display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: var(--ps-shadow-lg);"><i class="fas fa-users"></i></div>
                    <div style="font-size: 14px; font-weight: 600; padding: 4px 12px; border-radius: 20px; background: rgba(16,185,129,0.1); color: var(--ps-success); display: flex; align-items: center; gap: 4px;"><i class="fas fa-arrow-up"></i>+5%</div>
                </div>
                <div style="font-family: 'Poppins', sans-serif; font-size: 36px; font-weight: 800; color: var(--ps-text); margin-bottom: 8px;"><?php echo $total_users; ?></div>
                <div style="color: var(--ps-text-light); font-size: 16px; font-weight: 500;">Total Users</div>
            </div>
            <div class="kpi-card" style="background: var(--ps-white); border-radius: var(--ps-radius-xl); padding: 30px; box-shadow: var(--ps-shadow-lg); border: 1px solid var(--ps-border-light); position: relative; overflow: hidden;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div style="width: 60px; height: 60px; border-radius: var(--ps-radius-lg); background: var(--ps-pink-gradient); color: var(--ps-white); display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: var(--ps-shadow-lg);"><i class="fas fa-ticket-alt"></i></div>
                    <div style="font-size: 14px; font-weight: 600; padding: 4px 12px; border-radius: 20px; background: rgba(16,185,129,0.1); color: var(--ps-success); display: flex; align-items: center; gap: 4px;"><i class="fas fa-arrow-up"></i>+18%</div>
                </div>
                <div style="font-family: 'Poppins', sans-serif; font-size: 36px; font-weight: 800; color: var(--ps-text); margin-bottom: 8px;"><?php echo number_format($total_tickets_sold); ?></div>
                <div style="color: var(--ps-text-light); font-size: 16px; font-weight: 500;">Tickets Sold</div>
            </div>
            <div class="kpi-card" style="background: var(--ps-white); border-radius: var(--ps-radius-xl); padding: 30px; box-shadow: var(--ps-shadow-lg); border: 1px solid var(--ps-border-light); position: relative; overflow: hidden;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div style="width: 60px; height: 60px; border-radius: var(--ps-radius-lg); background: var(--ps-success-light); color: var(--ps-success); display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: var(--ps-shadow-lg);"><i class="fas fa-dollar-sign"></i></div>
                    <div style="font-size: 14px; font-weight: 600; padding: 4px 12px; border-radius: 20px; background: rgba(16,185,129,0.1); color: var(--ps-success); display: flex; align-items: center; gap: 4px;"><i class="fas fa-arrow-up"></i>+18%</div>
                </div>
                <div style="font-family: 'Poppins', sans-serif; font-size: 36px; font-weight: 800; color: var(--ps-text); margin-bottom: 8px;">RM <?php echo number_format($total_prize_value); ?></div>
                <div style="color: var(--ps-text-light); font-size: 16px; font-weight: 500;">Total Prize Value</div>
            </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="quick-actions" style="background: var(--ps-white); border-radius: var(--ps-radius-xl); padding: 30px; margin-bottom: 40px; box-shadow: var(--ps-shadow-lg); border: 1px solid var(--ps-border-light);">
            <h3 style="margin-bottom: 20px; color: var(--ps-text); font-size: 18px; font-weight: 600;">Quick Actions</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center; margin-bottom: 20px;">
                <a href="<?php echo BASE_URL; ?>/admin/raffles.php" class="btn btn-primary" style="background: var(--ps-blue-gradient); color: var(--ps-white); font-weight: 600; font-size: 16px; padding: 16px 28px; border-radius: var(--ps-radius-lg); border: none; display: flex; align-items: center; gap: 10px; box-shadow: var(--ps-shadow); text-decoration: none;">
                    <i class="fas fa-plus"></i> Manage Raffles
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/categories.php" class="btn btn-secondary" style="background: var(--ps-yellow-gradient); color: var(--ps-white); font-weight: 600; font-size: 16px; padding: 16px 28px; border-radius: var(--ps-radius-lg); border: none; display: flex; align-items: center; gap: 10px; box-shadow: var(--ps-shadow); text-decoration: none;">
                    <i class="fas fa-tags"></i> Manage Categories
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/brands.php" class="btn btn-accent" style="background: var(--ps-pink-gradient); color: var(--ps-white); font-weight: 600; font-size: 16px; padding: 16px 28px; border-radius: var(--ps-radius-lg); border: none; display: flex; align-items: center; gap: 10px; box-shadow: var(--ps-shadow); text-decoration: none;">
                    <i class="fas fa-building"></i> Manage Brands
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-accent" style="background: var(--ps-green-gradient); color: var(--ps-white); font-weight: 600; font-size: 16px; padding: 16px 28px; border-radius: var(--ps-radius-lg); border: none; display: flex; align-items: center; gap: 10px; box-shadow: var(--ps-shadow); text-decoration: none;">
                    <i class="fas fa-users"></i> Manage Users
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/banners.php" class="btn btn-accent" style="background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%); color: var(--ps-white); font-weight: 600; font-size: 16px; padding: 16px 28px; border-radius: var(--ps-radius-lg); border: none; display: flex; align-items: center; gap: 10px; box-shadow: var(--ps-shadow); text-decoration: none;">
                    <i class="fas fa-images"></i> Manage Banners
                </a>
            </div>
            
            <!-- Setup Workflow Guide -->
            <div style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-radius: var(--ps-radius-lg); padding: 20px; border: 1px solid #cbd5e1;">
                <h4 style="margin-bottom: 15px; color: var(--ps-text); font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-lightbulb" style="color: #f59e0b;"></i>
                    Setup Workflow Guide
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <div style="background: white; padding: 15px; border-radius: var(--ps-radius); border-left: 4px solid #3b82f6;">
                        <div style="font-weight: 600; color: #3b82f6; margin-bottom: 5px;">Step 1: Categories</div>
                        <div style="font-size: 14px; color: var(--ps-text-light);">Create categories and set which ones should display brands on frontend</div>
                    </div>
                    <div style="background: white; padding: 15px; border-radius: var(--ps-radius); border-left: 4px solid #10b981;">
                        <div style="font-weight: 600; color: #10b981; margin-bottom: 5px;">Step 2: Brands</div>
                        <div style="font-size: 14px; color: var(--ps-text-light);">Add brands and assign them to relevant categories</div>
                    </div>
                    <div style="background: white; padding: 15px; border-radius: var(--ps-radius); border-left: 4px solid #f59e0b;">
                        <div style="font-weight: 600; color: #f59e0b; margin-bottom: 5px;">Step 3: Raffles</div>
                        <div style="font-size: 14px; color: var(--ps-text-light);">Create raffles with dynamic brand selection</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="right-column">
        <div class="card">
            <div class="card-header">Recent Winners</div>
            <div class="winners-list">
                <?php if (!empty($recent_winners)): ?>
                    <?php foreach($recent_winners as $winner): ?>
                    <div class="winner-item">
                        <div class="pic"><?php echo strtoupper(substr($winner['winner_name'], 0, 1)); ?></div>
                        <div>
                            <div class="name"><?php echo htmlspecialchars($winner['winner_name']); ?></div>
                            <div class="prize">Won: <?php echo htmlspecialchars($winner['raffle_title']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--ps-text-light); text-align: center; padding: 20px;">No recent winners</p>
                <?php endif; ?>
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