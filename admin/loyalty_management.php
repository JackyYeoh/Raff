<?php 
include __DIR__ . '/../inc/header.php';
require_once __DIR__ . '/../inc/loyalty_system.php';

$loyaltySystem = new LoyaltySystem();

// Handle actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_reward_config':
            $dayNumber = intval($_POST['day_number']);
            $basePoints = intval($_POST['base_points']);
            $bonusType = $_POST['bonus_reward_type'];
            $bonusValue = $_POST['bonus_reward_value'];
            $description = $_POST['description'];
            
            $stmt = $pdo->prepare("
                UPDATE daily_rewards_config 
                SET base_points = ?, bonus_reward_type = ?, bonus_reward_value = ?, description = ?
                WHERE day_number = ?
            ");
            $stmt->execute([$basePoints, $bonusType, $bonusValue, $description, $dayNumber]);
            $success_message = "Reward configuration updated successfully!";
            break;
            
        case 'add_store_item':
            $name = $_POST['name'];
            $description = $_POST['description'];
            $pointsCost = intval($_POST['points_cost']);
            $itemType = $_POST['item_type'];
            $itemValue = $_POST['item_value'];
            $minVipTier = $_POST['min_vip_tier'];
            $stockQuantity = intval($_POST['stock_quantity']);
            
            $stmt = $pdo->prepare("
                INSERT INTO loyalty_store (name, description, points_cost, item_type, item_value, min_vip_tier, stock_quantity)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $description, $pointsCost, $itemType, $itemValue, $minVipTier, $stockQuantity]);
            $success_message = "Store item added successfully!";
            break;
    }
}

// Get loyalty statistics
$loyaltyStats = $loyaltySystem->getLoyaltyStats();

// Get daily rewards config
$stmt = $pdo->query("SELECT * FROM daily_rewards_config ORDER BY day_number");
$rewardsConfig = $stmt->fetchAll();

// Get recent check-ins
$stmt = $pdo->query("
    SELECT dc.*, u.name as user_name 
    FROM daily_checkins dc 
    JOIN users u ON dc.user_id = u.id 
    ORDER BY dc.created_at DESC 
    LIMIT 10
");
$recentCheckins = $stmt->fetchAll();

// Get store items
$stmt = $pdo->query("SELECT * FROM loyalty_store ORDER BY sort_order, points_cost");
$storeItems = $stmt->fetchAll();
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
        <h1>Loyalty System Management</h1>
        
        <?php if (isset($success_message)): ?>
        <div class="alert-success" style="margin-bottom: 20px; padding: 15px; background: #d4edda; color: #155724; border-radius: 8px;">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>
        
        <!-- Statistics Overview -->
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; margin-top:30px;">
            <div class="card">
                <div class="stat-item">
                    <div class="stat-value" style="color: var(--ps-blue);"><?php echo number_format($loyaltyStats['total_users']); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="card">
                <div class="stat-item">
                    <div class="stat-value" style="color: var(--ps-success);"><?php echo number_format($loyaltyStats['total_points_distributed']); ?></div>
                    <div class="stat-label">Points Distributed</div>
                </div>
            </div>
            <div class="card">
                <div class="stat-item">
                    <div class="stat-value" style="color: var(--ps-warning);"><?php echo number_format($loyaltyStats['avg_streak'], 1); ?></div>
                    <div class="stat-label">Avg Streak</div>
                </div>
            </div>
            <div class="card">
                <div class="stat-item">
                    <div class="stat-value" style="color: var(--ps-danger);"><?php echo $loyaltyStats['max_streak_ever']; ?></div>
                    <div class="stat-label">Max Streak Ever</div>
                </div>
            </div>
        </div>

        <!-- VIP Tier Distribution -->
        <div class="card" style="margin-bottom: 30px;">
            <div class="card-header">VIP Tier Distribution</div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; padding: 20px;">
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: #CD7F32;"><?php echo $loyaltyStats['bronze_users'] ?? 0; ?></div>
                    <div style="font-size: 14px; color: var(--ps-text-light);">Bronze</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: #C0C0C0;"><?php echo $loyaltyStats['silver_users']; ?></div>
                    <div style="font-size: 14px; color: var(--ps-text-light);">Silver</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: #FFD700;"><?php echo $loyaltyStats['gold_users']; ?></div>
                    <div style="font-size: 14px; color: var(--ps-text-light);">Gold</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: #B9F2FF;"><?php echo $loyaltyStats['diamond_users']; ?></div>
                    <div style="font-size: 14px; color: var(--ps-text-light);">Diamond</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs" style="margin-bottom: 30px;">
            <button class="tab-btn active" onclick="showTab('rewards')">Daily Rewards</button>
            <button class="tab-btn" onclick="showTab('store')">Loyalty Store</button>
            <button class="tab-btn" onclick="showTab('checkins')">Recent Check-ins</button>
        </div>

        <!-- Daily Rewards Tab -->
        <div id="rewards-tab" class="tab-content">
            <div class="card">
                <div class="card-header">Daily Rewards Configuration</div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Base Points</th>
                                <th>Bonus Type</th>
                                <th>Bonus Value</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rewardsConfig as $config): ?>
                            <tr>
                                <td><span class="badge"><?php echo $config['day_number']; ?></span></td>
                                <td><strong><?php echo $config['base_points']; ?></strong></td>
                                <td>
                                    <?php if ($config['bonus_reward_type'] != 'none'): ?>
                                    <span class="badge badge-success"><?php echo ucfirst($config['bonus_reward_type']); ?></span>
                                    <?php else: ?>
                                    <span class="badge badge-secondary">None</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $config['bonus_reward_value'] ?: '-'; ?></td>
                                <td><?php echo htmlspecialchars($config['description']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editReward(<?php echo $config['day_number']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Loyalty Store Tab -->
        <div id="store-tab" class="tab-content" style="display: none;">
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">Add New Store Item</div>
                <form method="POST" style="padding: 20px;">
                    <input type="hidden" name="action" value="add_store_item">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label>Item Name</label>
                            <input type="text" name="name" required class="form-control">
                        </div>
                        <div>
                            <label>Points Cost</label>
                            <input type="number" name="points_cost" required class="form-control">
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label>Description</label>
                        <textarea name="description" required class="form-control" rows="3"></textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label>Item Type</label>
                            <select name="item_type" required class="form-control">
                                <option value="cash_reward">Cash Reward</option>
                                <option value="ticket_discount">Ticket Discount</option>
                                <option value="free_ticket">Free Ticket</option>
                                <option value="vip_upgrade">VIP Upgrade</option>
                                <option value="exclusive_raffle">Exclusive Raffle</option>
                            </select>
                        </div>
                        <div>
                            <label>Item Value</label>
                            <input type="text" name="item_value" required class="form-control" placeholder="e.g., 5.00, 10%, silver">
                        </div>
                        <div>
                            <label>Min VIP Tier</label>
                            <select name="min_vip_tier" required class="form-control">
                                <option value="bronze">Bronze</option>
                                <option value="silver">Silver</option>
                                <option value="gold">Gold</option>
                                <option value="diamond">Diamond</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label>Stock Quantity (-1 for unlimited)</label>
                        <input type="number" name="stock_quantity" value="-1" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">Store Items</div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Cost</th>
                                <th>Value</th>
                                <th>Min VIP</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($storeItems as $item): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                <td><span class="badge badge-info"><?php echo ucfirst(str_replace('_', ' ', $item['item_type'])); ?></span></td>
                                <td><strong><?php echo number_format($item['points_cost']); ?></strong> pts</td>
                                <td><?php echo htmlspecialchars($item['item_value']); ?></td>
                                <td><span class="badge badge-secondary"><?php echo ucfirst($item['min_vip_tier']); ?></span></td>
                                <td><?php echo $item['stock_quantity'] == -1 ? 'Unlimited' : $item['stock_quantity']; ?></td>
                                <td>
                                    <?php if ($item['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editStoreItem(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteStoreItem(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Check-ins Tab -->
        <div id="checkins-tab" class="tab-content" style="display: none;">
            <div class="card">
                <div class="card-header">Recent Check-ins</div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Date</th>
                                <th>Streak Day</th>
                                <th>Points Awarded</th>
                                <th>Bonus Reward</th>
                                <th>Weekend Bonus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentCheckins as $checkin): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($checkin['user_name']); ?></strong></td>
                                <td><?php echo date('M j, Y', strtotime($checkin['checkin_date'])); ?></td>
                                <td><span class="badge badge-primary"><?php echo $checkin['day_in_streak']; ?></span></td>
                                <td><strong><?php echo $checkin['points_awarded']; ?></strong> pts</td>
                                <td>
                                    <?php if ($checkin['bonus_reward']): ?>
                                    <span class="badge badge-success">Yes</span>
                                    <?php else: ?>
                                    <span class="badge badge-secondary">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($checkin['is_weekend_bonus']): ?>
                                    <span class="badge badge-warning">Yes</span>
                                    <?php else: ?>
                                    <span class="badge badge-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Reward Modal -->
<div id="editRewardModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Edit Daily Reward</h2>
        <form method="POST" id="editRewardForm">
            <input type="hidden" name="action" value="update_reward_config">
            <input type="hidden" name="day_number" id="editDayNumber">
            
            <div style="margin-bottom: 20px;">
                <label>Base Points</label>
                <input type="number" name="base_points" id="editBasePoints" required class="form-control">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label>Bonus Reward Type</label>
                <select name="bonus_reward_type" id="editBonusType" class="form-control">
                    <option value="none">None</option>
                    <option value="ticket">Free Ticket</option>
                    <option value="spin">Spin Token</option>
                    <option value="discount">Discount</option>
                    <option value="multiplier">Points Multiplier</option>
                </select>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label>Bonus Value</label>
                <input type="text" name="bonus_reward_value" id="editBonusValue" class="form-control" placeholder="e.g., 1, 10%, 1.5x">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label>Description</label>
                <textarea name="description" id="editDescription" class="form-control" rows="3"></textarea>
            </div>
            
            <div style="text-align: right;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<style>
.tabs {
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 20px;
}

.tab-btn {
    background: none;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    font-weight: 600;
    color: #6c757d;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}

.tab-btn.active {
    color: var(--ps-blue);
    border-bottom-color: var(--ps-blue);
}

.tab-btn:hover {
    color: var(--ps-blue);
}

.tab-content {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 30px;
    border-radius: 10px;
    width: 90%;
    max-width: 500px;
    position: relative;
}

.close {
    position: absolute;
    right: 20px;
    top: 15px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.close:hover {
    color: #000;
}

.badge {
    display: inline-block;
    padding: 0.25em 0.6em;
    font-size: 0.75em;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.375rem;
}

.badge-primary { background-color: var(--ps-blue); color: white; }
.badge-success { background-color: var(--ps-success); color: white; }
.badge-warning { background-color: var(--ps-warning); color: white; }
.badge-danger { background-color: var(--ps-danger); color: white; }
.badge-info { background-color: var(--ps-info); color: white; }
.badge-secondary { background-color: var(--ps-silver); color: white; }

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.btn-primary { background-color: var(--ps-blue); color: white; }
.btn-secondary { background-color: var(--ps-silver); color: white; }
.btn-danger { background-color: var(--ps-danger); color: white; }
.btn-sm { padding: 6px 12px; font-size: 12px; }

.btn:hover { opacity: 0.9; }

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: var(--ps-text);
}

.table-responsive {
    overflow-x: auto;
}
</style>

<script>
// Tab functionality
function showTab(tabName) {
    // Hide all tab content
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab content
    document.getElementById(tabName + '-tab').style.display = 'block';
    
    // Add active class to clicked button
    event.target.classList.add('active');
}

// Edit reward functionality
function editReward(dayNumber) {
    // Find the reward config for this day
    const rewardsConfig = <?php echo json_encode($rewardsConfig); ?>;
    const config = rewardsConfig.find(r => r.day_number == dayNumber);
    
    if (config) {
        document.getElementById('editDayNumber').value = config.day_number;
        document.getElementById('editBasePoints').value = config.base_points;
        document.getElementById('editBonusType').value = config.bonus_reward_type;
        document.getElementById('editBonusValue').value = config.bonus_reward_value || '';
        document.getElementById('editDescription').value = config.description || '';
        
        document.getElementById('editRewardModal').style.display = 'block';
    }
}

function closeModal() {
    document.getElementById('editRewardModal').style.display = 'none';
}

function editStoreItem(itemId) {
    alert('Edit store item functionality coming soon for item ID: ' + itemId);
}

function deleteStoreItem(itemId) {
    if (confirm('Are you sure you want to delete this store item?')) {
        // Implement delete functionality
        alert('Delete functionality coming soon for item ID: ' + itemId);
    }
}

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

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editRewardModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?> 