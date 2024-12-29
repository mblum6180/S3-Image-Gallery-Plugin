# S3 Image Gallery Plugin

## Overview

The **S3 Image Gallery** WordPress plugin allows you to create dynamic image galleries by fetching images from **Amazon S3 buckets** and **local directories**. Designed for flexibility and performance, this plugin makes it easy to display large collections of images with responsive layouts, lazy loading, and enhanced security.

### What the Plugin Does

- Fetches and displays images from one or more specified S3 buckets or local directories.
- Automatically generates a lazy-loading gallery for improved page performance.
- Adds captions to images based on file names.
- Provides a full-screen viewing experience with navigation and keyboard support.
- Protects images with anti-hotlinking and disables right-click actions to deter unauthorized use.
- Optimizes user experience with responsive layouts and clean designs.

## Features

- **Support for S3 and Local Directories:** Fetch images from either S3 buckets or local server directories.
- **Automatic Image Retrieval:** Just provide your S3 bucket URLs or local directory paths, and the plugin fetches images for you.
- **Lazy Loading:** Reduces initial page load times by loading images only as they come into view.
- **Full-Screen Viewer:** Users can click on images to open a full-screen lightbox with caption display.
- **Keyboard Navigation:** Easily navigate between images using arrow keys.
- **Security Features:** Includes anti-hotlinking, right-click disable, and user-select prevention.
- **Customizable Gallery Display:** Adjust gallery appearance with CSS as needed.

## Installation

1. **Download the Plugin:**
   Download or clone this repository to your local machine.

2. **Upload to WordPress:**
   - Navigate to the WordPress admin panel.
   - Go to `Plugins > Add New > Upload Plugin`.
   - Upload the plugin ZIP file and click `Install Now`.

3. **Activate the Plugin:**
   - Once installed, click `Activate` to enable the plugin.

## Usage

### Step 1: Configure Settings

1. Navigate to the plugin settings page under `S3 Gallery` in the WordPress admin menu.
2. Enter the URLs of your **S3 buckets** or absolute paths of **local directories**, one per line.
3. Click `Save Changes`.

### Step 2: Use Shortcodes

After saving your bucket URLs or directory paths, shortcodes will be generated for each entry. Use these shortcodes to embed galleries on any post or page.

#### Examples:

- For an S3 bucket:

  ```plaintext
  [s3_gallery bucket="https://your-bucket-url.s3.amazonaws.com"]

  make this styled for git.

For a local directory:

    [s3_gallery local_dir="/path/to/your/directory"]

Step 3: View Your Gallery

Visit the page or post where the shortcode is added to see your gallery in action.
Shortcode Options
Attribute	Description	Example
bucket	The URL of the S3 bucket to fetch images from.	[s3_gallery bucket="https://your-bucket-url.s3.amazonaws.com"]
local_dir	The absolute path to a local directory.	[s3_gallery local_dir="/path/to/your/directory"]
Security Features

    No Right-Click: Right-clicking on gallery images is disabled to deter copying.
    Anti-Hotlinking: Prevents unauthorized access to your images.
    User-Select Disabled: Stops users from selecting text or images in the gallery.

Dependencies

This plugin requires the following:

    AWS SDK for PHP (included via Composer).
    A valid Amazon S3 bucket with images (for S3 galleries).
    Local directories must be accessible and properly configured for WordPress.

Development
File Structure

    s3-image-gallery.php: Main plugin file.
    vendor/: Includes dependencies installed via Composer.

Commands

    Install dependencies: composer install

License

This project is licensed under the MIT License. See the LICENSE file for details.