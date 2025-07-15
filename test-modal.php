<!DOCTYPE html>
<html>
<head>
    <title>Modal Test</title>
    <style>
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-container {
            background: white;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin: 5px;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
    </style>
</head>
<body>
    <h1>Modal Test</h1>
    
    <button class="btn btn-primary" onclick="openModal()">Open Test Modal</button>
    
    <div id="test-modal" class="modal-overlay">
        <div class="modal-container">
            <div style="padding: 20px;">
                <h2>Test Modal</h2>
                
                <!-- Tags Section Test -->
                <div style="background: white; border: 2px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 25px;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                        <div style="background: #f1f5f9; padding: 8px; border-radius: 8px;">
                            <i class="fas fa-tags" style="color: #667eea; font-size: 16px;">🏷️</i>
                        </div>
                        <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #374151;">Tags & Recommendations</h3>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                            🏷️ Raffle Tags
                        </label>
                        <div style="margin-bottom: 12px;">
                            <div id="raffle-tags-container" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; min-height: 40px; border: 1px dashed #ccc; padding: 10px;">
                                <span style="color: #6b7280;">Tags will be loaded here</span>
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="text" id="new-tag-input" placeholder="Add new tag..." style="flex: 1; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                                <select id="tag-type-select" style="padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                                    <option value="custom">Custom</option>
                                    <option value="category">Category</option>
                                    <option value="brand">Brand</option>
                                    <option value="feature">Feature</option>
                                </select>
                                <button type="button" id="add-tag-btn" class="btn btn-secondary" style="padding: 10px 16px;">
                                    ➕ Add
                                </button>
                            </div>
                            <div id="popular-tags-suggestions" style="margin-top: 8px; display: none;">
                                <small style="color: #6b7280; font-size: 12px;">Popular tags: <span id="popular-tags-list"></span></small>
                            </div>
                        </div>
                        <div style="padding: 10px; background: #eff6ff; border: 1px solid #3b82f6; border-radius: 8px; font-size: 12px; color: #1e40af;">
                            ℹ️ <strong>Smart Recommendations:</strong> Tags help power the "Just For U" recommendations and improve search results. Add relevant tags to help users discover your raffles!
                        </div>
                    </div>
                </div>
                
                <button class="btn btn-secondary" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('test-modal').classList.add('active');
            console.log('Modal opened');
            
            // Test if elements exist
            const elements = [
                'raffle-tags-container',
                'new-tag-input', 
                'tag-type-select',
                'add-tag-btn',
                'popular-tags-suggestions',
                'popular-tags-list'
            ];
            
            elements.forEach(id => {
                const el = document.getElementById(id);
                console.log(`${id}:`, el ? '✅ Found' : '❌ Not found');
            });
        }
        
        function closeModal() {
            document.getElementById('test-modal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('test-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html> 