<?php 
include __DIR__ . '/../inc/header.php';

// Search parameter
$search = $_GET['search'] ?? '';

// Build users query
$where_conditions = [];
$params = [];
if ($search) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get users with ticket counts
$stmt = $pdo->prepare("
    SELECT u.*, COUNT(t.id) as total_tickets, COUNT(CASE WHEN t.status = 'winner' THEN 1 END) as wins
    FROM users u 
    LEFT JOIN tickets t ON u.id = t.user_id 
    $where_clause 
    GROUP BY u.id, u.name, u.email, u.created_at 
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total users count
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$total_users = $stmt->fetchColumn();
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
        <h1>Manage Users</h1>
        
        <!-- Search and Stats -->
        <div class="card" style="margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="text-align: center;">
                        <div style="font-size: 24px; font-weight: 700; color: var(--ps-blue);"><?php echo $total_users; ?></div>
                        <div style="font-size: 14px; color: var(--ps-text-light);">Total Users</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 24px; font-weight: 700; color: var(--ps-success);">
                            <?php 
                            $active_users = array_filter($users, function($user) { return $user['total_tickets'] > 0; });
                            echo count($active_users);
                            ?>
                        </div>
                        <div style="font-size: 14px; color: var(--ps-text-light);">Active Users</div>
                    </div>
                </div>
                <form method="GET" class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
        </div>
        
        <!-- Users List -->
        <div class="card">
            <div class="card-header">Users List</div>
            <div class="raffle-table">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Joined</th>
                            <th>Tickets</th>
                            <th>Wins</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="raffle-client">
                                    <div class="pic"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                                    <div>
                                        <div class="title"><?php echo htmlspecialchars($user['name']); ?></div>
                                        <div class="id">ID: <?php echo htmlspecialchars($user['id']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <span style="background: var(--ps-blue-gradient); color: var(--ps-white); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                    <?php echo $user['total_tickets']; ?> tickets
                                </span>
                            </td>
                            <td>
                                <?php if ($user['wins'] > 0): ?>
                                <span style="background: var(--ps-success); color: var(--ps-white); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                    <?php echo $user['wins']; ?> wins
                                </span>
                                <?php else: ?>
                                <span style="color: var(--ps-text-light); font-size: 12px;">No wins</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['total_tickets'] > 0): ?>
                                <span style="background: var(--ps-success); color: var(--ps-white); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Active</span>
                                <?php else: ?>
                                <span style="background: var(--ps-text-light); color: var(--ps-white); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-secondary" style="padding: 8px 16px;" onclick="viewUserDetails(<?php echo $user['id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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

// View user details function (placeholder)
function viewUserDetails(userId) {
    alert('User details functionality coming soon for user ID: ' + userId);
}
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?> 