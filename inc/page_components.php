<?php
/**
 * Page Components - Extracted from index.php
 * Handles page-specific business logic and data processing
 */

// --- Moved from index.php ---

function getJustForU($raffles, $currentUser = null, $pdo = null) {
  if (!$pdo) {
    global $pdo;
  }
  $wishlist = [];
  $userPreferences = [];
  
  if ($currentUser) {
    try {
      // Get user's wishlist
      $stmt = $pdo->prepare("SELECT raffle_id FROM user_wishlists WHERE user_id = ? ORDER BY added_at DESC");
      $stmt->execute([$currentUser['id']]);
      $wishlist = $stmt->fetchAll(PDO::FETCH_COLUMN);
      
      // Get user's tag preferences
      $stmt = $pdo->prepare("
        SELECT tag_name, preference_score 
        FROM user_tag_preferences 
        WHERE user_id = ? 
        ORDER BY preference_score DESC, interaction_count DESC 
        LIMIT 10
      ");
      $stmt->execute([$currentUser['id']]);
      $userPreferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { 
      $wishlist = []; 
      $userPreferences = [];
    }
  }

  // Get all wishlisted raffles
  $wishlistedRaffles = array_filter($raffles, function($r) use ($wishlist) {
    return in_array($r['id'], $wishlist);
  });

  // Get tags from wishlisted raffles
  $wishlistedTags = [];
  if (!empty($wishlistedRaffles)) {
    $wishlistedIds = array_column($wishlistedRaffles, 'id');
    $placeholders = str_repeat('?,', count($wishlistedIds) - 1) . '?';
    
    try {
      $stmt = $pdo->prepare("
        SELECT tag_name, COUNT(*) as usage_count
        FROM raffle_tags 
        WHERE raffle_id IN ($placeholders)
        GROUP BY tag_name 
        ORDER BY usage_count DESC
      ");
      $stmt->execute($wishlistedIds);
      $wishlistedTags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      $wishlistedTags = [];
    }
  }

  $preferredTags = [];
  foreach ($userPreferences as $pref) {
    $preferredTags[$pref['tag_name']] = floatval($pref['preference_score']);
  }
  foreach ($wishlistedTags as $tag) {
    $tagName = $tag['tag_name'];
    if (!isset($preferredTags[$tagName])) {
      $preferredTags[$tagName] = 1.0;
    } else {
      $preferredTags[$tagName] += 0.5;
    }
  }

  if (empty($preferredTags)) {
    $keywords = [];
    foreach ($wishlistedRaffles as $r) {
      foreach (explode(' ', $r['title']) as $word) {
        if (strlen($word) > 3) $keywords[] = $word;
      }
    }
    $keywords = array_unique($keywords);
    $related = [];
    foreach ($raffles as $r) {
      if (in_array($r['id'], $wishlist)) continue;
      foreach ($keywords as $kw) {
        if (stripos($r['title'], $kw) !== false) {
          $related[] = $r;
          break;
        }
      }
    }
    $out = array_merge($wishlistedRaffles, array_slice($related, 0, max(0, 6 - count($wishlistedRaffles))));
    return $out;
  }

  $related = [];
  $raffleScores = [];
  foreach ($raffles as $r) {
    if (in_array($r['id'], $wishlist)) continue;
    try {
      $stmt = $pdo->prepare("SELECT tag_name FROM raffle_tags WHERE raffle_id = ?");
      $stmt->execute([$r['id']]);
      $raffleTags = $stmt->fetchAll(PDO::FETCH_COLUMN);
      $score = 0;
      foreach ($raffleTags as $tag) {
        if (isset($preferredTags[$tag])) {
          $score += $preferredTags[$tag];
        }
      }
      if ($score > 0) {
        $raffleScores[$r['id']] = $score;
        $related[] = $r;
      }
    } catch (Exception $e) {
      continue;
    }
  }
  usort($related, function($a, $b) use ($raffleScores) {
    $scoreA = $raffleScores[$a['id']] ?? 0;
    $scoreB = $raffleScores[$b['id']] ?? 0;
    return $scoreB <=> $scoreA;
  });
  $out = array_merge($wishlistedRaffles, array_slice($related, 0, max(0, 6 - count($wishlistedRaffles))));
  if (empty($out)) {
    usort($raffles, function($a, $b) {
      $aSold = isset($a['sold_tickets']) ? $a['sold_tickets'] : 0;
      $bSold = isset($b['sold_tickets']) ? $b['sold_tickets'] : 0;
      return $bSold - $aSold;
    });
    $out = array_slice($raffles, 0, 6);
  }
  return $out;
}

function getHotProducts($raffles) {
  usort($raffles, function($a,$b){ 
    $aSold = isset($a['sold']) ? $a['sold'] : 0;
    $bSold = isset($b['sold']) ? $b['sold'] : 0;
    return $bSold - $aSold; 
  });
  return array_slice($raffles,0,6);
}

function getSellingFast($raffles) {
  $filtered = array_filter($raffles, function($r){
    return (isset($r['badge']) && $r['badge']==='sellingFast') || (isset($r['total']) && $r['total']>0 && isset($r['sold']) && $r['sold']/$r['total']>0.7);
  });
  usort($filtered, function($a,$b){
    $ra = (isset($a['total']) && $a['total']>0) ? $a['sold']/$a['total'] : 0;
    $rb = (isset($b['total']) && $b['total']>0) ? $b['sold']/$b['total'] : 0;
    return $rb <=> $ra;
  });
  return array_slice($filtered,0,6);
}

function getCategoriesWithRaffles($categories, $raffles) {
    $categoriesWithRaffles = [];
    $raffleCounts = [];
    foreach ($raffles as $raffle) {
        if (isset($raffle['category']) && $raffle['category']) {
            $categoryName = $raffle['category'];
            if (!isset($raffleCounts[$categoryName])) {
                $raffleCounts[$categoryName] = 0;
            }
            $raffleCounts[$categoryName]++;
        }
    }
    foreach ($categories as $category) {
        if (isset($raffleCounts[$category['name']]) && $raffleCounts[$category['name']] > 0) {
            $categoriesWithRaffles[] = $category;
        }
    }
    return $categoriesWithRaffles;
}

function groupRafflesByBrand($raffles, $categories) {
    $groupedRaffles = [];
    $categorySettings = [];
    foreach ($categories as $cat) {
        $categorySettings[$cat['name']] = $cat['show_brands'];
    }
    foreach ($raffles as $raffle) {
        $category = $raffle['category'];
        $brand = $raffle['brand_name'];
        if (!isset($groupedRaffles[$category])) {
            $groupedRaffles[$category] = [];
        }
        $showBrands = isset($categorySettings[$category]) ? $categorySettings[$category] : 1;
        if ($showBrands) {
            if (!isset($groupedRaffles[$category][$brand])) {
                $groupedRaffles[$category][$brand] = [
                    'raffles' => [],
                    'featured' => $raffle['brand_featured'] ?? 0,
                    'sort_order' => $raffle['brand_sort_order'] ?? 999,
                    'category_sort_order' => $raffle['category_sort_order'] ?? null
                ];
            }
            $groupedRaffles[$category][$brand]['raffles'][] = $raffle;
        } else {
            if (!isset($groupedRaffles[$category]['All Items'])) {
                $groupedRaffles[$category]['All Items'] = [
                    'raffles' => [],
                    'featured' => 0,
                    'sort_order' => 999
                ];
            }
            $groupedRaffles[$category]['All Items']['raffles'][] = $raffle;
        }
    }
    foreach ($groupedRaffles as $category => $brands) {
        uasort($brands, function($a, $b) {
            if ($a['featured'] != $b['featured']) {
                return $b['featured'] - $a['featured'];
            }
            $sortOrderA = $a['category_sort_order'] ?? $a['sort_order'] ?? 999;
            $sortOrderB = $b['category_sort_order'] ?? $b['sort_order'] ?? 999;
            if ($sortOrderA != $sortOrderB) {
                return $sortOrderA - $sortOrderB;
            }
            $brandNameA = array_key_first($a['raffles']) ? $a['raffles'][0]['brand_name'] : '';
            $brandNameB = array_key_first($b['raffles']) ? $b['raffles'][0]['brand_name'] : '';
            return strcasecmp($brandNameA, $brandNameB);
        });
        $groupedRaffles[$category] = $brands;
    }
    return $groupedRaffles;
}
?> 