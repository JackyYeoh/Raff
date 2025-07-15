<?php
require_once 'inc/database.php';
require_once 'inc/user_auth.php';

// Get current user
$auth = new UserAuth();
$currentUser = $auth->getCurrentUser();

// Get some sample raffles
$stmt = $pdo->query("
    SELECT r.*, c.name as category_name, b.name as brand_name
    FROM raffles r
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN brands b ON r.brand_id = b.id
    WHERE r.status = 'active'
    LIMIT 5
");
$raffles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get popular tags
$stmt = $pdo->query("
    SELECT tag_name, usage_count 
    FROM popular_tags 
    ORDER BY usage_count DESC 
    LIMIT 10
");
$popularTags = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raffle Tags System Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .tag { display: inline-block; background: #667eea; color: white; padding: 4px 8px; border-radius: 12px; margin: 2px; font-size: 12px; }
        .raffle-card { border: 1px solid #eee; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .btn { background: #667eea; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #5a67d8; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Raffle Tags System Test</h1>
        
        <div class="section">
            <h2>📊 Popular Tags</h2>
            <p>Most used tags across all raffles:</p>
            <?php foreach ($popularTags as $tag): ?>
                <span class="tag"><?= htmlspecialchars($tag['tag_name']) ?> (<?= $tag['usage_count'] ?>)</span>
            <?php endforeach; ?>
        </div>

        <div class="section">
            <h2>🏷️ Sample Raffles with Tags</h2>
            <?php foreach ($raffles as $raffle): ?>
                <div class="raffle-card">
                    <h3><?= htmlspecialchars($raffle['title']) ?></h3>
                    <p>Category: <?= htmlspecialchars($raffle['category_name']) ?></p>
                    <p>Brand: <?= htmlspecialchars($raffle['brand_name']) ?></p>
                    <div id="tags-<?= $raffle['id'] ?>">
                        <small>Loading tags...</small>
                    </div>
                    <button class="btn" onclick="addSampleTag(<?= $raffle['id'] ?>)">Add Sample Tag</button>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($currentUser): ?>
        <div class="section">
            <h2>👤 Your Tag Preferences</h2>
            <div id="user-preferences">
                <small>Loading your preferences...</small>
            </div>
        </div>
        <?php endif; ?>

        <div class="section">
            <h2>🔍 Tag Search Test</h2>
            <input type="text" id="search-input" placeholder="Search by tags..." style="padding: 8px; width: 300px;">
            <button class="btn" onclick="searchByTags()">Search</button>
            <div id="search-results"></div>
        </div>

        <div class="section">
            <h2>🚀 Setup Instructions</h2>
            <ol>
                <li>Run <code>setup-raffle-tags.php</code> to create the database tables</li>
                <li>Go to Admin Panel → Raffles → Edit any raffle to add tags</li>
                <li>Tags will automatically power "Just For U" recommendations</li>
                <li>Search will include tag-based results</li>
                <li>User preferences are tracked for personalization</li>
            </ol>
        </div>
    </div>

    <script>
        // Load tags for each raffle
        <?php foreach ($raffles as $raffle): ?>
        fetch('api/tags.php?action=get_raffle_tags&raffle_id=<?= $raffle['id'] ?>')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('tags-<?= $raffle['id'] ?>');
                if (data.success && data.data.length > 0) {
                    container.innerHTML = data.data.map(tag => 
                        `<span class="tag">${tag.tag_name}</span>`
                    ).join('');
                } else {
                    container.innerHTML = '<small>No tags yet</small>';
                }
            })
            .catch(error => {
                document.getElementById('tags-<?= $raffle['id'] ?>').innerHTML = '<small>Error loading tags</small>';
            });
        <?php endforeach; ?>

        <?php if ($currentUser): ?>
        // Load user preferences
        fetch('api/tags.php?action=get_user_preferences')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('user-preferences');
                if (data.success && data.data.length > 0) {
                    container.innerHTML = data.data.map(pref => 
                        `<span class="tag">${pref.tag_name} (${pref.preference_score})</span>`
                    ).join('');
                } else {
                    container.innerHTML = '<small>No preferences yet. Start interacting with raffles!</small>';
                }
            })
            .catch(error => {
                document.getElementById('user-preferences').innerHTML = '<small>Error loading preferences</small>';
            });
        <?php endif; ?>

        // Add sample tag function
        function addSampleTag(raffleId) {
            const sampleTags = ['trending', 'popular', 'new', 'limited', 'exclusive', 'hot'];
            const randomTag = sampleTags[Math.floor(Math.random() * sampleTags.length)];
            
            fetch('api/tags.php?action=add_tag', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    raffle_id: raffleId,
                    tag_name: randomTag,
                    tag_type: 'feature'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Tag "${randomTag}" added successfully!`);
                    location.reload();
                } else {
                    alert('Error adding tag: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error adding tag. Please try again.');
            });
        }

        // Search by tags function
        function searchByTags() {
            const query = document.getElementById('search-input').value;
            const resultsContainer = document.getElementById('search-results');
            
            if (!query) {
                resultsContainer.innerHTML = '<p>Please enter a search term</p>';
                return;
            }
            
            fetch(`api/tags.php?action=search_by_tags&q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        resultsContainer.innerHTML = `
                            <h3>Found ${data.data.length} raffles:</h3>
                            ${data.data.map(raffle => `
                                <div class="raffle-card">
                                    <h4>${raffle.title}</h4>
                                    <p>Category: ${raffle.category_name}</p>
                                    <p>Brand: ${raffle.brand_name}</p>
                                </div>
                            `).join('')}
                        `;
                    } else {
                        resultsContainer.innerHTML = '<p>No raffles found</p>';
                    }
                })
                .catch(error => {
                    resultsContainer.innerHTML = '<p>Error searching</p>';
                });
        }

        // Enter key to search
        document.getElementById('search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchByTags();
            }
        });
    </script>
</body>
</html> 