# Banner Management System

## Overview
The Banner Management System allows administrators to create and manage dynamic banner slides for the homepage hero section. This system provides a complete CRUD interface for managing banner content, images, and scheduling.

## Features

### 🎨 **Dynamic Banner Slider**
- Multiple banner slides with smooth transitions
- Auto-advance functionality (5-second intervals)
- Manual navigation with arrows and dots
- Pause on hover functionality
- Keyboard navigation (arrow keys)

### 📝 **Content Management**
- **Title**: Main headline text
- **Subtitle**: Secondary supporting text
- **Description**: Detailed description text
- **Badge**: Optional promotional badge with customizable colors
- **Button**: Customizable call-to-action button with URL

### 🖼️ **Image Management**
- Background image upload with preview
- Support for JPG, PNG, and WebP formats
- Automatic image optimization and storage
- Live preview during editing

### ⏰ **Scheduling & Control**
- Start and end dates for time-sensitive banners
- Active/inactive status toggle
- Sort order for controlling display sequence
- Date-based filtering (only shows current banners)

### 🎯 **Admin Features**
- Full CRUD operations (Create, Read, Update, Delete)
- Live preview during editing
- Drag-and-drop image upload
- Bulk operations and status management
- Responsive admin interface

## Setup Instructions

### 1. Database Setup
Run the setup script to create the required table:
```bash
http://your-domain/raffle-demo/setup-banner-table.php
```

### 2. File Structure
Ensure the following directories exist:
```
raffle-demo/
├── images/
│   └── banners/          # Banner images storage
├── admin/
│   └── banners.php       # Admin management interface
└── index.php             # Updated with banner slider
```

### 3. Admin Access
Access the banner management at:
```
http://your-domain/raffle-demo/admin/banners.php
```

## Database Schema

### `banner_slides` Table
```sql
CREATE TABLE banner_slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(500),
    description TEXT,
    background_image VARCHAR(500) NOT NULL,
    button_text VARCHAR(100) DEFAULT 'Get Started',
    button_url VARCHAR(500),
    badge_text VARCHAR(100),
    badge_color ENUM('yellow', 'red', 'blue', 'green', 'purple') DEFAULT 'yellow',
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    start_date DATE NULL,
    end_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Usage Guide

### Creating a New Banner

1. **Access Admin Panel**
   - Go to Admin Dashboard → Manage Banners
   - Click "Add New Banner"

2. **Fill Content Fields**
   - **Title**: Main headline (required)
   - **Subtitle**: Supporting text (optional)
   - **Description**: Detailed description (optional)
   - **Button Text**: CTA button text (default: "Get Started")
   - **Button URL**: Link for the button (optional)

3. **Configure Badge**
   - **Badge Text**: Promotional text (e.g., "FLASH DEAL")
   - **Badge Color**: Choose from yellow, red, blue, green, purple

4. **Upload Background Image**
   - Click upload area or drag & drop
   - Supported formats: JPG, PNG, WebP
   - Maximum file size: 5MB
   - Images are automatically optimized

5. **Set Display Options**
   - **Sort Order**: Control display sequence (lower numbers first)
   - **Active Status**: Enable/disable the banner
   - **Start Date**: When to start showing (optional)
   - **End Date**: When to stop showing (optional)

6. **Preview & Save**
   - Use live preview to see how it looks
   - Click "Save Banner" to create

### Managing Existing Banners

- **Edit**: Click edit button to modify content and settings
- **Toggle Status**: Activate/deactivate without deleting
- **Delete**: Remove banner permanently (with image cleanup)
- **Reorder**: Change sort order to control display sequence

### Banner Display Logic

The system automatically filters banners based on:
- **Active Status**: Only `is_active = 1` banners are shown
- **Date Range**: Only banners within their start/end dates (if set)
- **Sort Order**: Banners are displayed in ascending sort order
- **Fallback**: If no banners are configured, shows default banner

## Frontend Integration

### Banner Slider Features
- **Auto-advance**: Changes slides every 5 seconds
- **Manual Navigation**: Arrow buttons and dot indicators
- **Hover Pause**: Stops auto-advance when mouse is over slider
- **Keyboard Support**: Left/Right arrow keys for navigation
- **Responsive**: Adapts to different screen sizes

### CSS Classes
```css
.banner-slide          /* Individual slide container */
.banner-slide.active   /* Currently visible slide */
.slide-dot             /* Navigation dots */
.slide-dot.active      /* Active navigation dot */
```

### JavaScript Functions
```javascript
initializeBannerSlider()  // Initialize slider functionality
showSlide(index)          // Show specific slide
nextSlide()              // Advance to next slide
prevSlide()              // Go to previous slide
```

## Best Practices

### Content Guidelines
- **Title**: Keep under 50 characters for mobile compatibility
- **Subtitle**: Use to provide context or additional info
- **Description**: Keep concise but informative
- **Button Text**: Use action-oriented text (e.g., "Buy Now", "Learn More")

### Image Guidelines
- **Aspect Ratio**: 16:9 or 2:1 works best
- **Resolution**: Minimum 1200x600px for good quality
- **File Size**: Keep under 2MB for fast loading
- **Format**: Use WebP for best compression, JPG for compatibility

### Scheduling Tips
- **Seasonal Campaigns**: Use start/end dates for holiday promotions
- **Flash Sales**: Set short date ranges for urgency
- **Evergreen Content**: Leave dates empty for permanent banners

## Troubleshooting

### Common Issues

1. **Banners Not Showing**
   - Check if banners are marked as active
   - Verify date ranges are current
   - Ensure sort order is set correctly

2. **Images Not Loading**
   - Check file permissions on `images/banners/` directory
   - Verify image file exists and is accessible
   - Check for special characters in filenames

3. **Slider Not Working**
   - Ensure JavaScript is enabled
   - Check browser console for errors
   - Verify multiple banners exist for slider functionality

### File Permissions
```bash
chmod 755 images/banners/
chmod 644 images/banners/*
```

## Security Considerations

- **File Upload**: Only image files are allowed
- **File Size**: Limited to 5MB maximum
- **Admin Access**: Requires admin authentication
- **SQL Injection**: Uses prepared statements
- **XSS Protection**: All output is properly escaped

## Performance Optimization

- **Image Optimization**: Automatic compression and resizing
- **Lazy Loading**: Images load as needed
- **Caching**: Browser caching for static assets
- **Database Indexing**: Optimized queries for banner retrieval

## Future Enhancements

Potential improvements for the banner system:
- **A/B Testing**: Track banner performance
- **Analytics**: Click tracking and conversion metrics
- **Templates**: Pre-designed banner templates
- **Animation**: Advanced transition effects
- **Targeting**: User-specific banner display
- **Mobile Optimization**: Touch gestures for mobile
- **Video Support**: Background video banners
- **Multi-language**: Internationalization support

## Support

For technical support or feature requests:
1. Check this documentation
2. Review browser console for errors
3. Verify database connectivity
4. Check file permissions
5. Contact development team

---

**Version**: 1.0  
**Last Updated**: December 2024  
**Compatibility**: PHP 7.4+, MySQL 5.7+ 