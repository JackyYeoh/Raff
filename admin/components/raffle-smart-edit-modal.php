<?php
// Smart Edit Modal Component
// This component contains the complete HTML for the Smart Edit modal, including all fields and the modern tag chip UI
?>

<div id="smart-edit-modal" class="modal-overlay">
    <div class="modal-container smart-edit-modal">
        <div class="modal-header">
            <div>
                <div class="modal-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <div>
                    <h2 id="smart-edit-title">Edit Raffle</h2>
                    <p id="smart-edit-subtitle">Modify raffle details and settings</p>
                </div>
            </div>
            <button class="modal-close-btn" id="smart-modal-close">&times;</button>
        </div>
        <form id="smart-edit-form" method="POST" action="<?php echo BASE_URL; ?>/update_raffle.php" enctype="multipart/form-data">
            <input type="hidden" id="smart-edit-mode" name="edit_mode" value="single">
            <input type="hidden" id="smart-raffle-id" name="raffle_id" value="">
            <input type="hidden" id="smart-raffle-ids" name="raffle_ids" value="">
            
            <!-- Mode Information -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <div style="background: #6366f1; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div>
                        <h3 id="mode-title" style="margin: 0; font-size: 16px; font-weight: 600; color: #374151;">Single Edit Mode</h3>
                        <p id="mode-description" style="margin: 5px 0 0 0; font-size: 14px; color: #6b7280;">Editing one raffle. All fields are editable.</p>
                    </div>
                </div>
            </div>

            <!-- Force Override Section (for batch mode) -->
            <div id="force-override-section" style="display: none; background: #fef3c7; border: 1px solid #fde68a; border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <input type="checkbox" id="force-override-mixed" style="width: 18px; height: 18px;">
                    <div>
                        <label for="force-override-mixed" style="font-weight: 600; color: #92400e; font-size: 14px;">Force Override Mixed Fields</label>
                        <p style="margin: 4px 0 0 0; font-size: 12px; color: #a16207;">Enable to edit fields with different values across selected raffles</p>
                    </div>
                </div>
            </div>

            <!-- Type Guard Section (for mixed raffle types) -->
            <div id="type-guard-section" style="display: none; background: #fee2e2; border: 1px solid #fecaca; border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-exclamation-triangle" style="color: #dc2626; font-size: 18px;"></i>
                    <div>
                        <h4 style="margin: 0; color: #dc2626; font-size: 14px; font-weight: 600;">Mixed Raffle Types Detected</h4>
                        <p style="margin: 4px 0 0 0; font-size: 12px; color: #991b1b;">Some raffles have different types. Ticket settings and scheduling may be locked.</p>
                    </div>
                </div>
            </div>

            <!-- Basic Information Section -->
            <div class="smart-field-section">
                <div class="smart-section-header">
                    <div>
                        <div class="smart-section-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <h3 class="smart-section-title">Basic Information</h3>
                        <p class="smart-section-subtitle">Core raffle details and settings</p>
                    </div>
                </div>
                
                <div class="smart-form-grid-2">
                    <div class="smart-field-card" data-field="title">
                        <div class="field-header">
                            <label for="smart-title">Raffle Title</label>
                            <div class="field-status">
                                <span class="field-indicator uniform" style="display: none;">Uniform</span>
                                <span class="field-indicator mixed" style="display: none;">Mixed</span>
                                <span class="field-indicator locked" style="display: none;">Locked</span>
                            </div>
                        </div>
                        <input type="text" id="smart-title" name="title" class="smart-field" placeholder="Enter raffle title">
                        <div class="mixed-values-note" style="display: none;">
                            <small>Mixed values detected. <span class="unlock-hint">Enable override to edit</span></small>
                        </div>
                    </div>
                    
                    <div class="smart-field-card" data-field="status">
                        <div class="field-header">
                            <label for="smart-status">Status</label>
                            <div class="field-status">
                                <span class="field-indicator uniform" style="display: none;">Uniform</span>
                                <span class="field-indicator mixed" style="display: none;">Mixed</span>
                                <span class="field-indicator locked" style="display: none;">Locked</span>
                            </div>
                        </div>
                        <select id="smart-status" name="status" class="smart-field">
                            <option value="">-- Select Status --</option>
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="closed">Closed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <div class="mixed-values-note" style="display: none;">
                            <small>Mixed values detected. <span class="unlock-hint">Enable override to edit</span></small>
                        </div>
                    </div>
                </div>
                
                <div class="smart-field-card" data-field="description">
                    <div class="field-header">
                        <label for="smart-description">Description</label>
                        <div class="field-status">
                            <span class="field-indicator uniform" style="display: none;">Uniform</span>
                            <span class="field-indicator mixed" style="display: none;">Mixed</span>
                            <span class="field-indicator locked" style="display: none;">Locked</span>
                        </div>
                    </div>
                    <textarea id="smart-description" name="description" class="smart-field" rows="4" placeholder="Enter raffle description..."></textarea>
                    <div class="mixed-values-note" style="display: none;">
                        <small>Mixed values detected. <span class="unlock-hint">Enable override to edit</span></small>
                    </div>
                </div>
            </div>

            <!-- Category & Brand Section -->
            <div class="smart-field-section">
                <div class="smart-section-header">
                    <div>
                        <div class="smart-section-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <h3 class="smart-section-title">Category & Brand</h3>
                        <p class="smart-section-subtitle">Organize raffles by category and brand</p>
                    </div>
                </div>
                
                <div class="smart-form-grid-2">
                    <div class="smart-field-card" data-field="category_id">
                        <div class="field-header">
                            <label for="smart-category">Category</label>
                            <div class="field-status">
                                <span class="field-indicator uniform" style="display: none;">Uniform</span>
                                <span class="field-indicator mixed" style="display: none;">Mixed</span>
                                <span class="field-indicator locked" style="display: none;">Locked</span>
                            </div>
                        </div>
                        <select id="smart-category" name="category_id" class="smart-field">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mixed-values-note" style="display: none;">
                            <small>Mixed values detected. <span class="unlock-hint">Enable override to edit</span></small>
                        </div>
                    </div>
                    
                    <div class="smart-field-card" data-field="brand_id">
                        <div class="field-header">
                            <label for="smart-brand">Brand</label>
                            <div class="field-status">
                                <span class="field-indicator uniform" style="display: none;">Uniform</span>
                                <span class="field-indicator mixed" style="display: none;">Mixed</span>
                                <span class="field-indicator locked" style="display: none;">Locked</span>
                            </div>
                        </div>
                        <select id="smart-brand" name="brand_id" class="smart-field">
                            <option value="">-- Select Brand --</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['id']; ?>">
                                    <?php echo htmlspecialchars($brand['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mixed-values-note" style="display: none;">
                            <small>Mixed values detected. <span class="unlock-hint">Enable override to edit</span></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ticket Settings Section -->
            <div class="smart-field-section" id="ticket-settings-section">
                <div class="smart-section-header">
                    <div>
                        <div class="smart-section-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <h3 class="smart-section-title">Ticket Settings</h3>
                        <p class="smart-section-subtitle">Configure ticket pricing and availability</p>
                    </div>
                    <div class="section-guard" style="display: none;">
                        <i class="fas fa-lock"></i>
                    </div>
                </div>
                
                <div class="smart-form-grid-3">
                    <div class="smart-field-card" data-field="ticket_price">
                        <div class="field-header">
                            <label for="smart-price">Ticket Price (RM)</label>
                            <div class="field-status">
                                <span class="field-indicator uniform" style="display: none;">Uniform</span>
                                <span class="field-indicator mixed" style="display: none;">Mixed</span>
                                <span class="field-indicator locked" style="display: none;">Locked</span>
                            </div>
                        </div>
                        <input type="number" id="smart-price" name="ticket_price" class="smart-field" step="0.01" min="0.01" placeholder="1.00">
                        <div class="mixed-values-note" style="display: none;">
                            <small>Mixed values detected. <span class="unlock-hint">Enable override to edit</span></small>
                        </div>
                    </div>
                    
                    <div class="smart-field-card" data-field="total_tickets">
                        <div class="field-header">
                            <label for="smart-total-tickets">Total Entries</label>
                            <div class="field-status">
                                <span class="field-indicator uniform" style="display: none;">Uniform</span>
                                <span class="field-indicator mixed" style="display: none;">Mixed</span>
                                <span class="field-indicator locked" style="display: none;">Locked</span>
                            </div>
                        </div>
                        <input type="number" id="smart-total-tickets" name="total_tickets" class="smart-field" min="1" placeholder="100">
                        <div class="mixed-values-note" style="display: none;">
                            <small>Mixed values detected. <span class="unlock-hint">Enable override to edit</span></small>
                        </div>
                    </div>
                    
                    <div class="smart-field-card" data-field="tickets_per_entry">
                        <div class="field-header">
                            <label for="smart-tickets-per-entry">Tickets Per Entry</label>
                            <div class="field-status">
                                <span class="field-indicator uniform" style="display: none;">Uniform</span>
                                <span class="field-indicator mixed" style="display: none;">Mixed</span>
                                <span class="field-indicator locked" style="display: none;">Locked</span>
                            </div>
                        </div>
                        <input type="number" id="smart-tickets-per-entry" name="tickets_per_entry" class="smart-field" min="1" value="1" placeholder="1">
                        <div class="mixed-values-note" style="display: none;">
                            <small>Mixed values detected. <span class="unlock-hint">Enable override to edit</span></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scheduling Section -->
            <div class="smart-field-section" id="scheduling-section">
                <div class="smart-section-header">
                    <div>
                        <div class="smart-section-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3 class="smart-section-title">Scheduling</h3>
                        <p class="smart-section-subtitle">Set draw date and time</p>
                    </div>
                    <div class="section-guard" style="display: none;">
                        <i class="fas fa-lock"></i>
                    </div>
                </div>
                
                <div class="smart-field-card" data-field="draw_date">
                    <div class="field-header">
                        <label for="smart-draw-date">Draw Date & Time</label>
                        <div class="field-status">
                            <span class="field-indicator uniform" style="display: none;">Uniform</span>
                            <span class="field-indicator mixed" style="display: none;">Mixed</span>
                            <span class="field-indicator locked" style="display: none;">Locked</span>
                        </div>
                    </div>
                    <input type="datetime-local" id="smart-draw-date" name="draw_date" class="smart-field">
                    <div class="field-help">
                        <small>Leave empty for automatic draw when all tickets are sold</small>
                    </div>
                    <div class="mixed-values-note" style="display: none;">
                        <small>Mixed values detected. <span class="unlock-hint">Enable override to edit</span></small>
                    </div>
                </div>
            </div>

            <!-- Tags Section for Smart Edit Modal (single mode only) -->
            <div class="smart-field-section" id="smart-edit-tags-section" style="display: none;">
                <div class="smart-section-header">
                    <div>
                        <div class="smart-section-icon">
                            <i class="fas fa-tag"></i>
                        </div>
                        <h3 class="smart-section-title">Tags</h3>
                        <p class="smart-section-subtitle">Add tags to improve discoverability</p>
                    </div>
                    <div style="display: flex; gap: 10px; margin-left: auto;">
                        <button type="button" id="smart-copy-tags-btn" title="Copy All Tags" style="background: none; border: none; cursor: pointer; font-size: 20px; padding: 2px 6px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                        </button>
                        <button type="button" id="smart-delete-tags-btn" title="Delete All Tags" style="background: none; border: none; cursor: pointer; font-size: 24px; padding: 2px 6px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>
                
                <div class="smart-field-card" style="display: flex; align-items: center; gap: 8px;">
                    <div id="smart-edit-tag-chip-container" class="tag-chip-container" style="flex: 1;">
                        <input type="text" id="smart-edit-tag-input" class="tag-input" placeholder="Type a tag and press space or comma" autocomplete="off" style="flex: 1; min-width: 120px;" />
                        <input type="hidden" name="tags" id="smart-edit-tags-hidden" value="" />
                    </div>
                </div>
                <div class="field-help">
                    <small>Please press space or comma after each tag</small>
                </div>
            </div>

            <!-- Footer -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-top: 24px;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <div style="background: #10b981; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <h3 id="footer-title" style="margin: 0; font-size: 16px; font-weight: 600; color: #374151;">Ready to Save Changes?</h3>
                        <p id="footer-subtitle" style="margin: 5px 0 0 0; font-size: 14px; color: #6b7280;">Review your changes and save the updated raffle details</p>
                    </div>
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" id="smart-cancel-btn">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="smart-save-btn">
                        <i class="fas fa-save"></i> <span id="save-btn-text">Save Changes</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
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
    const tagInput = document.getElementById('smart-edit-tag-input');
    const tagContainer = document.getElementById('smart-edit-tag-chip-container');
    const tagsHidden = document.getElementById('smart-edit-tags-hidden');
    const copyBtn = document.getElementById('smart-copy-tags-btn');
    const deleteBtn = document.getElementById('smart-delete-tags-btn');
    function renderTags() {
        if (!tagContainer || !tagsHidden) return;
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
    if (tagInput) {
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
    }
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
    window.setSmartEditModalTags = function(arr) {
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