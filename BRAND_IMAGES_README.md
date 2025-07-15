# Brand Images Feature

This feature allows administrators to upload brand logo images that will be displayed on the frontend next to brand names.

## Setup Instructions

1. **Run the setup script** to add the image_url column to the brands table:
   ```
   http://your-domain/raffle-demo/setup-brand-images.php
   ```

2. **Create the brands images directory** (if not already created):
   - The script will automatically create `images/brands/` directory
   - This directory will store all uploaded brand logos

## Admin Panel Usage

1. **Login to Admin Panel**:
   ```
   http://your-domain/raffle-demo/admin/admin-login.php
   ```

2. **Navigate to Brands Management**:
   - Go to "Brands" in the admin menu
   - Click "Add New Brand" or "Edit" an existing brand

3. **Upload Brand Logo**:
   - In the brand form, you'll see a "Brand Logo" field
   - Click "Choose File" to select an image
   - Supported formats: JPG, PNG, GIF, WebP
   - Recommended size: 200x200px
   - Maximum file size: 2MB

4. **Save the Brand**:
   - The image will be automatically uploaded to `images/brands/`
   - The filename will be: `{brand-slug}_{timestamp}.{extension}`

## Frontend Display

Brand images will be displayed in two places:

1. **Brand Headers in Category Sections**:
   - Small 32x32px logo next to brand names
   - Shows in category sections when brands are grouped

2. **Admin Brand Cards**:
   - 40x40px logo in the admin brands management page
   - Shows current brand logo or placeholder icon

## File Structure

```
raffle-demo/
├── images/
│   └── brands/           # Brand logo uploads
│       ├── apple_1234567890.png
│       ├── samsung_1234567891.jpg
│       └── ...
├── admin/
│   └── brands.php        # Updated with image upload
├── index.php             # Updated to display brand images
└── setup-brand-images.php # Setup script
```

## Database Changes

The `brands` table now includes:
- `image_url VARCHAR(500)` - Path to the brand logo image

## Features

- ✅ Image upload with validation
- ✅ Automatic file naming (brand-slug + timestamp)
- ✅ File type validation (JPG, PNG, GIF, WebP)
- ✅ File size limit (2MB)
- ✅ Image preview in admin edit form
- ✅ Remove image functionality
- ✅ Automatic old image deletion when updating
- ✅ Fallback placeholder icons when no image
- ✅ Responsive display on frontend

## Troubleshooting

1. **Images not uploading**:
   - Check that `images/brands/` directory exists and is writable
   - Verify PHP file upload settings in php.ini
   - Check file size limits

2. **Images not displaying**:
   - Verify the image path is correct
   - Check file permissions
   - Ensure the image file exists in the directory

3. **Database errors**:
   - Run `setup-brand-images.php` to ensure the column exists
   - Check database connection settings

## Security Notes

- Only image files are allowed (validated by extension and MIME type)
- Files are renamed to prevent conflicts
- Old images are automatically deleted when updated
- File size is limited to prevent abuse 