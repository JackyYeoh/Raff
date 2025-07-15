<?php
// Raffle Table Component
// This component handles the display and interaction of the raffle data table
?>

<!-- ENHANCED RAFFLE TABLE -->
<div class="card" id="table-view">
    <div class="card-header">
        <div>
            <h3 class="card-title">Raffles Management</h3>
            <p class="card-subtitle">Manage all your raffles from this centralized table</p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <span id="table-info" class="text-xs text-muted" style="padding: 6px 12px; background: var(--admin-gray-100); border-radius: 6px;">
                <?php echo count($raffles); ?> total
            </span>
        </div>
    </div>
    <div class="raffle-table" style="margin-top: 20px; overflow-x: auto;">
        <table>
            <colgroup>
                <col style="width:80px" />   <!-- Checkbox -->
                <col style="width:220px" />  <!-- Raffle Details -->
                <col style="width:120px" />  <!-- Category -->
                <col style="width:120px" />  <!-- Brand -->
                <col style="width:110px" />  <!-- Price -->
                <col style="width:150px" />  <!-- Sales Progress -->
                <col style="width:90px" />   <!-- Per Entry -->
                <col style="width:110px" />  <!-- Status -->
                <col style="width:90px" />   <!-- Actions -->
            </colgroup>
            <thead>
                <tr>
                    <th class="col-checkbox checkbox-header" onclick="toggleSelectAll(event)">
                        <input type="checkbox" id="select-all-checkbox">
                    </th>
                    <th class="col-details">Raffle Details</th>
                    <th class="col-category">Category</th>
                    <th class="col-brand">Brand</th>
                    <th class="col-price">Price</th>
                    <th class="col-sales">Sales Progress</th>
                    <th class="col-entry">Per Entry</th>
                    <th class="col-status">Status</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($raffles)): ?>
                    <?php foreach ($raffles as $raffle): ?>
                    <tr class="raffle-row" onclick="toggleRaffleRow(this, event)" data-raffle-id="<?php echo $raffle['id'] ?? 0; ?>">
                        <td class="col-checkbox checkbox-column">
                            <input type="checkbox" class="raffle-checkbox" data-raffle-id="<?php echo $raffle['id'] ?? 0; ?>">
                        </td>
                        <td class="col-details">
                            <div class="raffle-client" style="display: flex; align-items: center; gap: 12px;">
                                <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($raffle['image_url'] ?? 'images/placeholder.png'); ?>" alt="" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover; border: 2px solid #e5e7eb; flex-shrink: 0;">
                                <div style="flex: 1; min-width: 0;">
                                    <div class="title" style="font-weight: 700; font-size: 14px; color: #374151; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($raffle['title'] ?? 'Untitled Raffle'); ?>
                                    </div>
                                    <div style="display: flex; gap: 8px; align-items: center; font-size: 11px; color: #6b7280;">
                                        <span style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-weight: 600;">
                                            ID: <?php echo htmlspecialchars($raffle['id'] ?? 'N/A'); ?>
                                        </span>
                                        <span>
                                            <?php echo date('M j, Y', strtotime($raffle['created_at'] ?? 'now')); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="col-category">
                            <span style="background: #f0fdf4; color: #15803d; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; border: 1px solid #bbf7d0; display: inline-block; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo htmlspecialchars($raffle['category_name'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td class="col-brand">
                            <?php if (!empty($raffle['brand_name'])): ?>
                                <span style="background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; border: 1px solid #fde68a; display: inline-block; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo htmlspecialchars($raffle['brand_name']); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #9ca3af; font-size: 11px;">No Brand</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-price">
                            <div style="font-weight: 700; font-size: 14px; color: #16a085;">
                                RM <?php echo number_format($raffle['ticket_price'], 2); ?>
                            </div>
                            <div style="font-size: 10px; color: #6b7280; margin-top: 2px;">
                                Revenue: RM <?php echo number_format($raffle['ticket_price'] * $raffle['sold_tickets'], 2); ?>
                            </div>
                        </td>
                        <td class="col-sales">
                            <?php 
                            $progress = $raffle['total_tickets'] > 0 ? ($raffle['sold_tickets'] / $raffle['total_tickets']) * 100 : 0;
                            $progress_color = $progress >= 75 ? '#10b981' : ($progress >= 50 ? '#f59e0b' : ($progress >= 25 ? '#ef4444' : '#6b7280'));
                            ?>
                            <div style="margin-bottom: 6px;">
                                <strong style="font-size: 14px; color: #374151;"><?php echo number_format($raffle['sold_tickets']); ?></strong>
                                <span style="color: #6b7280; font-size: 11px;">/ <?php echo number_format($raffle['total_tickets']); ?></span>
                            </div>
                            <div style="background: #f3f4f6; height: 4px; border-radius: 2px; overflow: hidden; margin-bottom: 4px;">
                                <div style="background: <?php echo $progress_color; ?>; height: 100%; width: <?php echo $progress; ?>%; transition: width 0.3s ease;"></div>
                            </div>
                            <div style="font-size: 10px; color: #6b7280;">
                                <?php echo number_format($progress, 1); ?>% sold
                            </div>
                        </td>
                        <td class="col-entry">
                            <div style="background: #eff6ff; color: #1d4ed8; padding: 6px 10px; border-radius: 6px; font-weight: 600; font-size: 12px; border: 1px solid #bfdbfe;">
                                <?php echo $raffle['tickets_per_entry'] ?? 1; ?>
                            </div>
                        </td>
                        <td class="col-status">
                            <span class="status-badge <?php echo htmlspecialchars($raffle['status']); ?>" style="font-size: 11px; font-weight: 600; padding: 6px 12px; border-radius: 12px; display: inline-block;">
                                <?php 
                                $status_icons = [
                                    'active' => '🟢',
                                    'draft' => '🟡', 
                                    'closed' => '🔴',
                                    'cancelled' => '⚫'
                                ];
                                echo ($status_icons[$raffle['status']] ?? '⚪') . ' ' . htmlspecialchars(ucfirst($raffle['status'])); 
                                ?>
                            </span>
                        </td>
                        <td class="col-actions">
                            <button class="smart-row-edit-btn" data-raffle-id="<?php echo $raffle['id'] ?? 0; ?>" style="padding: 6px 12px; background: #6366f1; color: white; border: none; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 4px; margin: 0 auto;">
                                <i class="fas fa-edit"></i> <span class="btn-text">Edit</span>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px; color: var(--ps-text-light);">
                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                            No raffles found. <a href="db-check.php">Check database setup</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div> 