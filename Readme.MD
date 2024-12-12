# S3 Image Gallery Plugin

## Overview
The **S3 Image Gallery** WordPress plugin offers a seamless way to integrate image galleries into your website by fetching images directly from Amazon S3 buckets. Designed with efficiency and security in mind, this plugin automates image retrieval, enhances gallery interactivity, and protects your media from unauthorized use. It's an ideal solution for photographers, bloggers, and businesses needing to showcase large image collections.

### What the Plugin Does
- Fetches and displays images from one or more specified S3 buckets.
- Automatically generates a lazy-loading gallery for improved page performance.
- Adds captions to images based on file names.
- Provides a full-screen viewing experience with navigation and keyboard support.
- Protects images with anti-hotlinking and disables right-click actions to deter downloading or copying.
- Optimizes user experience with responsive layouts and clean designs.

## Features
- **Automatic Image Retrieval:** Simply provide your S3 bucket URLs, and the plugin fetches images for you.
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
### Step 1: Configure S3 Bucket Settings
1. Navigate to the plugin settings page under `S3 Gallery` in the WordPress admin menu.
2. Enter the URLs of your S3 buckets, one per line.
3. Click `Save Changes`.

### Step 2: Use Shortcodes
After saving your bucket URLs, shortcodes will be generated for each bucket. Use these shortcodes to embed galleries on any post or page. Example:

```plaintext
[s3_gallery bucket="https://your-bucket-url.s3.amazonaws.com"]
```

### Step 3: View Your Gallery
Visit the page or post where the shortcode is added to see your gallery in action.

## Shortcode Options
| Attribute | Description                        | Example                                   |
|-----------|------------------------------------|-------------------------------------------|
| `bucket`  | The URL of the S3 bucket to fetch images from. | `[s3_gallery bucket="https://your-bucket-url.s3.amazonaws.com"]` |

## Security Features
- **No Right-Click:** Right-clicking on gallery images is disabled to deter copying.
- **Anti-Hotlinking:** Prevents unauthorized access to your images.
- **User-Select Disabled:** Stops users from selecting text or images in the gallery.

## Dependencies
This plugin requires the following:
- [AWS SDK for PHP](https://aws.amazon.com/sdk-for-php/) (included via Composer).
- A valid Amazon S3 bucket with images.

## Development
### File Structure
- `s3-image-gallery.php`: Main plugin file.
- `vendor/`: Includes dependencies installed via Composer.

### Commands
- Install dependencies: `composer install`

## Future Enhancements
- Add pagination for galleries with large image sets.
- Support for custom themes and styles.
- Integration with other cloud storage services.
- Enhanced filtering and sorting options for images.

## License
This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Author
Matthew Blum  
[Visit my website](https://links.mblum6180.com)

---

For any issues, please feel free to open a ticket or contact me directly.

