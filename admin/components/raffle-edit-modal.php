<?php
// Edit Raffle Modal Component
// This component contains the full HTML for the Edit Raffle modal, including all fields and the modern tag chip UI
?>

<div id="edit-raffle-modal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <div>
                <div class="modal-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <div>
                    <h2>Edit Raffle <span id="edit-raffle-id-display" style="font-weight: 500; opacity: 0.9;">#0</span></h2>
                    <p>Modify raffle details and settings</p>
                </div>
            </div>
            <button class="modal-close-btn" type="button">&times;</button>
        </div>
        <form id="edit-raffle-form" method="POST" action="<?php echo BASE_URL; ?>/update_raffle.php" enctype="multipart/form-data">
            <input type="hidden" id="edit-raffle-id" name="raffle_id" value="" />
            <input type="hidden" name="edit_mode" value="single" />
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label for="edit-raffle-title">Raffle Title *</label>
                    <input type="text" id="edit-raffle-title" name="title" required placeholder="Enter raffle title">
                </div>
                <div class="form-group">
                    <label for="edit-raffle-category">Category *</label>
                    <select id="edit-raffle-category" name="category_id" required>
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
                    <label for="edit-raffle-brand">Brand</label>
                    <select id="edit-raffle-brand" name="brand_id">
                        <option value="">-- Select a Brand --</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo $brand['id']; ?>">
                                <?php echo htmlspecialchars($brand['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit-raffle-status">Status</label>
                    <select id="edit-raffle-status" name="status">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="closed">Closed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="edit-raffle-description">Description</label>
                <textarea id="edit-raffle-description" name="description" rows="3" placeholder="Enter raffle description..."></textarea>
            </div>
            <div class="form-group">
                <label>Raffle Image</label>
                <div class="image-uploader" id="image-uploader" style="padding: 15px; min-height: 100px;">
                    <input type="file" name="image" id="edit-raffle-image" accept="image/jpeg, image/png, image/webp">
                    <div class="upload-text">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 20px;"></i>
                        <span>Click to Upload Image</span>
                        <small style="display: block; color: #6c757d; margin-top: 4px; font-size: 11px;">JPG, PNG, WEBP (Max 5MB)</small>
                    </div>
                    <img src="" alt="Image Preview" class="image-preview" id="image-preview" style="display: none; max-height: 120px;">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label for="edit-raffle-price">Ticket Price (RM) *</label>
                    <input type="number" id="edit-raffle-price" name="ticket_price" step="0.01" min="0.01" required placeholder="1.00">
                </div>
                <div class="form-group">
                    <label for="edit-raffle-total-tickets">Total Entries *</label>
                    <input type="number" id="edit-raffle-total-tickets" name="total_tickets" min="1" required placeholder="100">
                </div>
                <div class="form-group">
                    <label for="edit-raffle-tickets-per-entry">Tickets Per Entry</label>
                    <input type="number" id="edit-raffle-tickets-per-entry" name="tickets_per_entry" min="1" value="1" placeholder="1">
                </div>
            </div>
            <div class="form-group">
                <label for="edit-raffle-draw-date">Draw Date & Time (Optional)</label>
                <input type="datetime-local" id="edit-raffle-draw-date" name="draw_date">
                <small style="color: var(--ps-text-light); font-size: 12px; display: block; margin-top: 4px;">
                    Leave empty for automatic draw when all tickets are sold, or set for scheduled live draw
                </small>
            </div>
            <!-- Tags Section -->
            <div class="form-group" style="position: relative;">
                <label style="font-size: 14px; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-tag" style="margin-right: 6px; color: #667eea;"></i> Tags
                    <span style="margin-left: auto; display: flex; gap: 10px;">
                        <button type="button" id="edit-copy-tags-btn" title="Copy All Tags" style="background: none; border: none; cursor: pointer; font-size: 20px; padding: 2px 6px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                        </button>
                        <button type="button" id="edit-delete-tags-btn" title="Delete All Tags" style="background: none; border: none; cursor: pointer; font-size: 24px; padding: 2px 6px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </span>
                </label>
                <div id="edit-tag-chip-container" class="tag-chip-container" style="margin-top: 8px;">
                    <!-- Chips will be rendered here -->
                    <input type="text" id="edit-tag-input" class="tag-input" placeholder="Type a tag and press space or comma" autocomplete="off" style="flex: 1; min-width: 120px;" />
                    <input type="hidden" name="tags" id="edit-tags-hidden" value="" />
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
                const tagInput = document.getElementById('edit-tag-input');
                const tagContainer = document.getElementById('edit-tag-chip-container');
                const tagsHidden = document.getElementById('edit-tags-hidden');
                const copyBtn = document.getElementById('edit-copy-tags-btn');
                const deleteBtn = document.getElementById('edit-delete-tags-btn');
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
                window.setEditModalTags = function(arr) {
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
            <div style="display: flex; gap: 12px; justify-content: flex-end; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--ps-border-light);">
                <button type="button" class="btn btn-secondary modal-close-btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div> 