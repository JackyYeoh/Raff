<?php
require_once '../inc/database.php';

// Include admin header (this will handle session and auth)
include __DIR__ . '/../inc/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = trim($_POST['title']);
                $subtitle = trim($_POST['subtitle']);
                $description = trim($_POST['description']);
                $button_text = trim($_POST['button_text']);
                $button_url = trim($_POST['button_url']);
                $badge_text = trim($_POST['badge_text']);
                $badge_color = $_POST['badge_color'];
                $sort_order = (int)$_POST['sort_order'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
                $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
                
                // Handle image upload
                $background_image = '';
                if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../images/banners/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['background_image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        $filename = 'banner_' . time() . '_' . uniqid() . '.' . $file_extension;
                        $filepath = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['background_image']['tmp_name'], $filepath)) {
                            $background_image = 'images/banners/' . $filename;
                        }
                    }
                }
                
                if (!empty($title) && !empty($background_image)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO banner_slides (title, subtitle, description, background_image, button_text, button_url, badge_text, badge_color, sort_order, is_active, start_date, end_date)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    if ($stmt->execute([$title, $subtitle, $description, $background_image, $button_text, $button_url, $badge_text, $badge_color, $sort_order, $is_active, $start_date, $end_date])) {
                        $_SESSION['flash_message'] = 'Banner slide added successfully!';
                    } else {
                        $_SESSION['flash_message'] = 'Error adding banner slide.';
                    }
                } else {
                    $_SESSION['flash_message'] = 'Please provide title and background image.';
                }
                break;
                
            case 'update':
                $id = (int)$_POST['id'];
                $title = trim($_POST['title']);
                $subtitle = trim($_POST['subtitle']);
                $description = trim($_POST['description']);
                $button_text = trim($_POST['button_text']);
                $button_url = trim($_POST['button_url']);
                $badge_text = trim($_POST['badge_text']);
                $badge_color = $_POST['badge_color'];
                $sort_order = (int)$_POST['sort_order'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
                $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
                
                // Handle image upload
                $background_image = $_POST['current_image'];
                if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../images/banners/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['background_image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        $filename = 'banner_' . time() . '_' . uniqid() . '.' . $file_extension;
                        $filepath = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['background_image']['tmp_name'], $filepath)) {
                            // Delete old image if exists
                            if (!empty($_POST['current_image']) && file_exists('../' . $_POST['current_image'])) {
                                unlink('../' . $_POST['current_image']);
                            }
                            $background_image = 'images/banners/' . $filename;
                        }
                    }
                }
                
                if (!empty($title) && !empty($background_image)) {
                    $stmt = $pdo->prepare("
                        UPDATE banner_slides 
                        SET title = ?, subtitle = ?, description = ?, background_image = ?, button_text = ?, button_url = ?, badge_text = ?, badge_color = ?, sort_order = ?, is_active = ?, start_date = ?, end_date = ?
                        WHERE id = ?
                    ");
                    
                    if ($stmt->execute([$title, $subtitle, $description, $background_image, $button_text, $button_url, $badge_text, $badge_color, $sort_order, $is_active, $start_date, $end_date, $id])) {
                        $_SESSION['flash_message'] = 'Banner slide updated successfully!';
                    } else {
                        $_SESSION['flash_message'] = 'Error updating banner slide.';
                    }
                } else {
                    $_SESSION['flash_message'] = 'Please provide title and background image.';
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                // Get current image to delete
                $stmt = $pdo->prepare("SELECT background_image FROM banner_slides WHERE id = ?");
                $stmt->execute([$id]);
                $slide = $stmt->fetch();
                
                if ($slide && !empty($slide['background_image']) && file_exists('../' . $slide['background_image'])) {
                    unlink('../' . $slide['background_image']);
                }
                
                $stmt = $pdo->prepare("DELETE FROM banner_slides WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $_SESSION['flash_message'] = 'Banner slide deleted successfully!';
                } else {
                    $_SESSION['flash_message'] = 'Error deleting banner slide.';
                }
                break;
                
            case 'toggle_status':
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("UPDATE banner_slides SET is_active = NOT is_active WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $_SESSION['flash_message'] = 'Banner status updated successfully!';
                } else {
                    $_SESSION['flash_message'] = 'Error updating banner status.';
                }
                break;
        }
        
        header('Location: banners.php');
        exit();
    }
}

// Fetch all banner slides with error handling
$banners = [];
try {
    $stmt = $pdo->query("SELECT * FROM banner_slides ORDER BY sort_order ASC, created_at DESC");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['flash_message'] = 'Error loading banners: ' . $e->getMessage();
}

// Get statistics
$total_banners = count($banners);
$active_banners = count(array_filter($banners, fn($b) => $b['is_active']));
$inactive_banners = $total_banners - $active_banners;
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
                <h1 class="page-title">Banner Management</h1>
                <p style="color: var(--ps-text-light); margin-top: 5px; font-size: 14px;">
                    Create and manage hero banner slides for your homepage
                </p>
            </div>
            <div class="page-actions">
                <button onclick="openAddModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Banner
                </button>
            </div>
        </div>
        
        <!-- Workflow Status Indicator -->
        <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 1px solid #f59e0b; border-radius: var(--ps-radius-lg); padding: 20px; margin-bottom: 30px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                <div style="background: #f59e0b; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                    <i class="fas fa-images"></i>
                </div>
                <div>
                    <h3 style="color: #92400e; font-size: 16px; font-weight: 600; margin: 0;">Banner Management Guide</h3>
                    <p style="color: #92400e; font-size: 14px; margin: 5px 0 0 0;">Create engaging banner slides to enhance your homepage</p>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div style="background: white; padding: 12px; border-radius: var(--ps-radius); border: 1px solid #fcd34d;">
                    <div style="font-weight: 600; color: #92400e; font-size: 14px;">🎨 Visual Impact</div>
                    <div style="font-size: 12px; color: #a16207; margin-top: 2px;">High-quality images increase engagement by 80%</div>
                </div>
                <div style="background: white; padding: 12px; border-radius: var(--ps-radius); border: 1px solid #fcd34d;">
                    <div style="font-weight: 600; color: #92400e; font-size: 14px;">📱 Mobile First</div>
                    <div style="font-size: 12px; color: #a16207; margin-top: 2px;">Optimize for mobile devices (70% of traffic)</div>
                </div>
                <div style="background: white; padding: 12px; border-radius: var(--ps-radius); border: 1px solid #fcd34d;">
                    <div style="font-weight: 600; color: #92400e; font-size: 14px;">⏰ Scheduling</div>
                    <div style="font-size: 12px; color: #a16207; margin-top: 2px;">Set start/end dates for seasonal campaigns</div>
                </div>
                <div style="background: white; padding: 12px; border-radius: var(--ps-radius); border: 1px solid #fcd34d;">
                    <div style="font-weight: 600; color: #92400e; font-size: 14px;">🎯 Call to Action</div>
                    <div style="font-size: 12px; color: #a16207; margin-top: 2px;">Clear buttons drive 200% more conversions</div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-images"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $total_banners; ?></div>
                    <div class="stat-label">Total Banners</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $active_banners; ?></div>
                    <div class="stat-label">Active Banners</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon inactive">
                    <i class="fas fa-eye-slash"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $inactive_banners; ?></div>
                    <div class="stat-label">Inactive Banners</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-sliders-h"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo count(array_filter($banners, fn($b) => !empty($b['start_date']) || !empty($b['end_date']))); ?></div>
                    <div class="stat-label">Scheduled</div>
                </div>
            </div>
        </div>
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo strpos($_SESSION['flash_message'], 'Error') !== false ? 'error' : 'success'; ?>">
                <i class="fas fa-<?php echo strpos($_SESSION['flash_message'], 'Error') !== false ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>
        
        <!-- ENHANCED BANNER TABLE/CARD -->
        <div class="card" id="table-view">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Banner Management</h3>
                    <p class="card-subtitle">Manage all your banner slides from this centralized view</p>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <span id="table-info" class="text-xs text-muted" style="padding: 6px 12px; background: var(--admin-gray-100); border-radius: 6px;">
                        <?php echo count($banners); ?> total
                    </span>
                </div>
            </div>
            
            <!-- Banner Grid -->
            <div style="padding: 24px;">
                <?php if (empty($banners)): ?>
                    <div style="text-align: center; padding: 40px; color: var(--ps-text-light);">
                        <i class="fas fa-images" style="font-size: 48px; margin-bottom: 16px; display: block; color: #d1d5db;"></i>
                        <h3 style="font-size: 18px; font-weight: 600; color: #6b7280; margin-bottom: 8px;">No Banners Yet</h3>
                        <p style="color: #9ca3af; margin-bottom: 24px;">Create your first banner slide to enhance your homepage</p>
                        <button onclick="openAddModal()" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create First Banner
                        </button>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($banners as $banner): ?>
                        <div class="banner-card" style="margin-top: 20px;background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; transition: all 0.3s ease;">
                            <div class="banner-image-area" style="background-image: url('../<?php echo htmlspecialchars($banner['background_image']); ?>'); background-size: cover; background-position: center; height: 200px; position: relative; overflow: hidden;">
                                <div class="banner-gradient" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.8), rgba(37, 99, 235, 0.8)); position: absolute; inset: 0;"></div>
                                <div class="banner-content-area" style="position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: center; padding: 20px; color: white;">
                                    <?php if (!empty($banner['badge_text'])): ?>
                                        <span class="banner-badge <?php echo $banner['badge_color']; ?>" style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; margin-bottom: 12px; align-self: flex-start;">
                                            <?php echo htmlspecialchars($banner['badge_text']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <div class="banner-title" style="font-size: 18px; font-weight: 700; margin-bottom: 8px; line-height: 1.2;">
                                        <?php echo htmlspecialchars($banner['title']); ?>
                                    </div>
                                    <?php if (!empty($banner['subtitle'])): ?>
                                        <div class="banner-subtitle" style="font-size: 14px; opacity: 0.9; margin-bottom: 12px;">
                                            <?php echo htmlspecialchars($banner['subtitle']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($banner['button_text'])): ?>
                                        <a href="<?php echo htmlspecialchars($banner['button_url'] ?: '#'); ?>" class="btn btn-primary btn-sm" style="width: fit-content; min-width: 120px; font-size: 12px; padding: 8px 16px;">
                                            <?php echo htmlspecialchars($banner['button_text']); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <span class="banner-status-badge <?php echo $banner['is_active'] ? 'status-active' : 'status-inactive'; ?>" style="position: absolute; top: 12px; right: 12px; padding: 4px 8px; border-radius: 12px; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <?php echo $banner['is_active'] ? '🟢 Active' : '🔴 Inactive'; ?>
                                </span>
                            </div>
                            <div class="banner-card-footer" style="padding: 16px;">
                                <div style="margin-bottom: 12px;">
                                    <div style="font-weight: 600; color: #374151; margin-bottom: 4px; font-size: 14px;">
                                        <?php echo htmlspecialchars($banner['title']); ?>
                                    </div>
                                    <?php if (!empty($banner['description'])): ?>
                                        <div class="banner-desc" style="font-size: 12px; color: #6b7280; line-height: 1.4;">
                                            <?php echo htmlspecialchars($banner['description']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div style="display: flex; gap: 4px; margin-top: 8px;">
                                        <span style="background: #f3f4f6; color: #6b7280; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600;">
                                            ID: <?php echo $banner['id']; ?>
                                        </span>
                                        <span style="background: #f3f4f6; color: #6b7280; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600;">
                                            Sort: <?php echo $banner['sort_order']; ?>
                                        </span>
                                    </div>
                                </div>
                                <div style="display: flex; items-center gap-2">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($banner)); ?>)" class="smart-row-edit-btn" style="padding: 6px 12px; background: #6366f1; color: white; border: none; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 4px; margin: 0 auto;" title="Edit Banner">
                                        <i class="fas fa-edit"></i> <span class="btn-text">Edit</span>
                                    </button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to toggle this banner status?')" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?php echo $banner['id']; ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="padding: 6px 12px; font-size: 11px;" title="<?php echo $banner['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas fa-<?php echo $banner['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                        </button>
                                    </form>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this banner? This action cannot be undone.')" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $banner['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" style="padding: 6px 12px; font-size: 11px;" title="Delete Banner">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .banner-preview {
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        min-height: 200px;
        border-radius: var(--admin-radius-xl);
        position: relative;
        overflow: hidden;
    }
    
    .banner-overlay {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.8), rgba(37, 99, 235, 0.8));
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        padding: var(--admin-space-6);
    }
    
    .banner-content {
        color: white;
        max-width: 60%;
    }
    
    .banner-badge {
        display: inline-block;
        padding: var(--admin-space-1) var(--admin-space-3);
        border-radius: var(--admin-radius-full);
        font-size: var(--admin-font-size-xs);
        font-weight: 600;
        margin-bottom: var(--admin-space-3);
    }
    
    .banner-badge.yellow { background: #fbbf24; color: #92400e; }
    .banner-badge.red { background: #ef4444; color: white; }
    .banner-badge.blue { background: #3b82f6; color: white; }
    .banner-badge.green { background: #10b981; color: white; }
    .banner-badge.purple { background: #8b5cf6; color: white; }
    
    .slide-dot {
        transition: all 0.3s ease;
    }
    
    .slide-dot:hover {
        transform: scale(1.2);
    }
    
    .slide-dot.active {
        background: white !important;
        transform: scale(1.3);
    }
    
    /* Statistics Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: all 0.3s ease;
        border: 1px solid #e9ecef;
    }
    
    .stat-card:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        color: #6c757d;
        font-size: 20px;
    }
    
    .stat-icon.active {
        background: #d1fae5;
        color: #065f46;
    }
    
    .stat-icon.inactive {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .stat-content {
        flex: 1;
    }
    
    .stat-value {
        font-size: 24px;
        font-weight: 700;
        color: #1f2937;
        line-height: 1.2;
    }
    
    .stat-label {
        font-size: 14px;
        color: #6b7280;
        margin-top: 4px;
    }
    
    /* Alert Styles */
    .alert {
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #10b981;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #ef4444;
    }
    
    /* Status Badge Styles */
    .status-badge.status-active {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        color: #065f46;
        border: 1px solid #10b981;
    }
    
    .status-badge.status-inactive {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        color: #991b1b;
        border: 1px solid #ef4444;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Enhanced Modal System -->
<div id="bannerModal" class="modal-overlay">
    <div class="modal-container large-modal">
        <div class="modal-header">
            <div>
                <div class="modal-icon">
                    <i class="fas fa-images"></i>
                </div>
                <h2 id="modalTitle">Add New Banner</h2>
                <p id="modalSubtitle">Create a new banner slide for your homepage</p>
            </div>
            <button class="modal-close-btn" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="bannerForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="bannerId">
            <input type="hidden" name="current_image" id="currentImage">
            
            <div class="form-section">
                <div class="form-section-header">
                    <div class="form-section-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div>
                        <div class="form-section-title">Banner Content</div>
                        <div class="form-section-subtitle">Set the main content and messaging for your banner</div>
                    </div>
                </div>
                
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="bannerTitle">Title *</label>
                        <input type="text" name="title" id="bannerTitle" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="bannerSubtitle">Subtitle</label>
                        <input type="text" name="subtitle" id="bannerSubtitle">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="bannerDescription">Description</label>
                    <textarea name="description" id="bannerDescription" rows="3"></textarea>
                </div>
                
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="buttonText">Button Text</label>
                        <input type="text" name="button_text" id="buttonText" value="Get Started">
                    </div>
                    
                    <div class="form-group">
                        <label for="buttonUrl">Button URL</label>
                        <input type="url" name="button_url" id="buttonUrl">
                    </div>
                </div>
                
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="badgeText">Badge Text</label>
                        <input type="text" name="badge_text" id="badgeText" placeholder="e.g., FLASH DEAL">
                    </div>
                    
                    <div class="form-group">
                        <label for="badgeColor">Badge Color</label>
                        <select name="badge_color" id="badgeColor">
                            <option value="yellow">Yellow</option>
                            <option value="red">Red</option>
                            <option value="blue">Blue</option>
                            <option value="green">Green</option>
                            <option value="purple">Purple</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="form-section-header">
                    <div class="form-section-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div>
                        <div class="form-section-title">Banner Settings</div>
                        <div class="form-section-subtitle">Configure display settings and scheduling</div>
                    </div>
                </div>
                
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="sortOrder">Sort Order</label>
                        <input type="number" name="sort_order" id="sortOrder" value="0" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" id="isActive" checked>
                            <span class="ml-2">Active</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="startDate">Start Date (Optional)</label>
                        <input type="date" name="start_date" id="startDate">
                    </div>
                    
                    <div class="form-group">
                        <label for="endDate">End Date (Optional)</label>
                        <input type="date" name="end_date" id="endDate">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="form-section-header">
                    <div class="form-section-icon">
                        <i class="fas fa-image"></i>
                    </div>
                    <div>
                        <div class="form-section-title">Background Image</div>
                        <div class="form-section-subtitle">Upload and preview your banner background</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="backgroundImage">Background Image *</label>
                    <div class="image-uploader">
                        <input type="file" name="background_image" id="backgroundImage" accept="image/*" onchange="previewImage(this)">
                        <div id="uploadArea" onclick="document.getElementById('backgroundImage').click()">
                            <div class="upload-text">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to upload or drag and drop</p>
                                <small>PNG, JPG, WEBP up to 5MB</small>
                            </div>
                        </div>
                        <div id="imagePreview" class="image-preview hidden">
                            <img id="previewImg" src="" alt="Preview">
                        </div>
                    </div>
                </div>
                
                <!-- Live Preview -->
                <div class="form-group">
                    <label>Live Preview</label>
                    <div id="livePreview" class="banner-preview" style="min-height: 200px;">
                        <div class="banner-overlay">
                            <div class="banner-content">
                                <div id="previewBadge" class="banner-badge yellow hidden"></div>
                                <h3 id="previewTitle" class="text-xl font-bold mb-2">Your Banner Title</h3>
                                <p id="previewSubtitle" class="text-sm opacity-90 mb-3 hidden">Your subtitle here</p>
                                <button id="previewButton" class="bg-white text-blue-600 px-4 py-2 rounded-lg font-semibold text-sm hidden">
                                    Get Started
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <div class="modal-footer">
            <div class="modal-footer-content">
                <div class="modal-footer-info">
                    <div class="modal-footer-title">Banner Management</div>
                    <div class="modal-footer-subtitle">Create engaging banner slides to enhance your homepage</div>
                </div>
                <div class="modal-footer-actions">
                    <button type="button" onclick="closeModal()" class="modal-button modal-button-secondary">
                        Cancel
                    </button>
                    <button type="submit" form="bannerForm" class="modal-button modal-button-primary">
                        <i class="fas fa-save"></i> Save Banner
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'Add New Banner';
        document.getElementById('modalSubtitle').textContent = 'Create a new banner slide for your homepage';
        document.getElementById('formAction').value = 'add';
        document.getElementById('bannerForm').reset();
        document.getElementById('bannerId').value = '';
        document.getElementById('currentImage').value = '';
        document.getElementById('imagePreview').classList.add('hidden');
        document.getElementById('uploadArea').classList.remove('hidden');
        resetPreview();
        document.getElementById('bannerModal').classList.add('active');
    }
    
    function openEditModal(banner) {
        document.getElementById('modalTitle').textContent = 'Edit Banner';
        document.getElementById('modalSubtitle').textContent = 'Update your banner slide settings';
        document.getElementById('formAction').value = 'update';
        document.getElementById('bannerId').value = banner.id;
        document.getElementById('currentImage').value = banner.background_image;
        
        // Fill form fields
        document.getElementById('bannerTitle').value = banner.title;
        document.getElementById('bannerSubtitle').value = banner.subtitle || '';
        document.getElementById('bannerDescription').value = banner.description || '';
        document.getElementById('buttonText').value = banner.button_text || 'Get Started';
        document.getElementById('buttonUrl').value = banner.button_url || '';
        document.getElementById('badgeText').value = banner.badge_text || '';
        document.getElementById('badgeColor').value = banner.badge_color || 'yellow';
        document.getElementById('sortOrder').value = banner.sort_order || 0;
        document.getElementById('isActive').checked = banner.is_active == 1;
        document.getElementById('startDate').value = banner.start_date || '';
        document.getElementById('endDate').value = banner.end_date || '';
        
        // Show current image
        if (banner.background_image) {
            document.getElementById('previewImg').src = '../' + banner.background_image;
            document.getElementById('imagePreview').classList.remove('hidden');
            document.getElementById('uploadArea').classList.add('hidden');
            updateLivePreview();
        }
        
        document.getElementById('bannerModal').classList.add('active');
    }
    
    function closeModal() {
        document.getElementById('bannerModal').classList.remove('active');
    }
    
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('imagePreview').classList.remove('hidden');
                document.getElementById('uploadArea').classList.add('hidden');
                updateLivePreview();
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function updateLivePreview() {
        const title = document.getElementById('bannerTitle').value || 'Your Banner Title';
        const subtitle = document.getElementById('bannerSubtitle').value;
        const buttonText = document.getElementById('buttonText').value || 'Get Started';
        const badgeText = document.getElementById('badgeText').value;
        const badgeColor = document.getElementById('badgeColor').value;
        const image = document.getElementById('previewImg').src;
        
        // Update preview elements
        document.getElementById('previewTitle').textContent = title;
        
        if (subtitle) {
            document.getElementById('previewSubtitle').textContent = subtitle;
            document.getElementById('previewSubtitle').classList.remove('hidden');
        } else {
            document.getElementById('previewSubtitle').classList.add('hidden');
        }
        
        if (buttonText) {
            document.getElementById('previewButton').textContent = buttonText;
            document.getElementById('previewButton').classList.remove('hidden');
        } else {
            document.getElementById('previewButton').classList.add('hidden');
        }
        
        if (badgeText) {
            document.getElementById('previewBadge').textContent = badgeText;
            document.getElementById('previewBadge').className = `banner-badge ${badgeColor}`;
            document.getElementById('previewBadge').classList.remove('hidden');
        } else {
            document.getElementById('previewBadge').classList.add('hidden');
        }
        
        // Update background image
        if (image && image !== 'data:') {
            document.getElementById('livePreview').style.backgroundImage = `url(${image})`;
        }
    }
    
    function resetPreview() {
        document.getElementById('previewTitle').textContent = 'Your Banner Title';
        document.getElementById('previewSubtitle').classList.add('hidden');
        document.getElementById('previewButton').classList.add('hidden');
        document.getElementById('previewBadge').classList.add('hidden');
        document.getElementById('livePreview').style.backgroundImage = '';
    }
    
    // Add event listeners for live preview
    document.addEventListener('DOMContentLoaded', function() {
        const formFields = ['bannerTitle', 'bannerSubtitle', 'buttonText', 'badgeText', 'badgeColor'];
        formFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', updateLivePreview);
            }
        });
        
        // Close modal on outside click
        document.getElementById('bannerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('bannerModal').classList.contains('active')) {
                closeModal();
            }
        });
    });
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?> 