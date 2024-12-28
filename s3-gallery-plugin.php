<?php
/**
 * Plugin Name: S3 Image Gallery
 * Description: Automatically fetch images from S3 buckets and display them in a lazy-loading gallery with captions and full-screen functionality.
 * Version: 1.9
 * Author: Matthew Blum
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
use Aws\S3\S3Client;

/**
 * Enqueue CSS and JS
 */
function s3_image_gallery_enqueue_assets() {
    if (!is_admin()) {
        wp_enqueue_style(
            's3-image-gallery-style',
            plugin_dir_url(__FILE__) . 'css/s3-image-gallery.css',
            array(),
            '1.0',
            'all'
        );

        wp_enqueue_script(
            's3-image-gallery-script',
            plugin_dir_url(__FILE__) . 'js/s3-image-gallery.js',
            array(),
            '1.0',
            true
        );

        // Pass the watermark URL to JavaScript
        $watermark_url = get_option('s3_watermark_url', '');
        wp_localize_script('s3-image-gallery-script', 'S3GallerySettings', array(
            'watermarkUrl' => esc_url($watermark_url),
        ));
    }
}
add_action('wp_enqueue_scripts', 's3_image_gallery_enqueue_assets');




/**
 * Add Admin Menu
 */
add_action('admin_menu', 's3_gallery_menu');
function s3_gallery_menu()
{
    add_menu_page(
        'S3 Gallery Settings',
        'S3 Gallery',
        'manage_options',
        's3-gallery',
        's3_gallery_settings_page'
    );
}

/**
 * Settings Page
 */
function s3_gallery_settings_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['s3_buckets'])) {
            update_option('s3_buckets', sanitize_textarea_field($_POST['s3_buckets']));
        }

        if (isset($_POST['watermark_url'])) {
            update_option('s3_watermark_url', esc_url_raw($_POST['watermark_url']));
        }

        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $buckets = get_option('s3_buckets', '');
    $watermark_url = get_option('s3_watermark_url', ''); // Default empty
    ?>
    <div class="wrap">
        <h1>S3 Image Gallery</h1>
        <form method="post">
            <label for="s3_buckets">
                <p>Enter your S3 bucket URLs, one per line:</p>
            </label>
            <textarea id="s3_buckets" name="s3_buckets" rows="10" cols="50" class="large-text"><?php echo esc_textarea($buckets); ?></textarea>

            <label for="watermark_url">
                <p>Enter the URL of the watermark image:</p>
            </label>
            <input id="watermark_url" name="watermark_url" type="url" class="regular-text" value="<?php echo esc_attr($watermark_url); ?>" />

            <input type="submit" class="button-primary" value="Save Changes">
        </form>

        <?php if (!empty($buckets)): ?>
            <h2>Your Shortcodes:</h2>
            <?php 
            $bucket_lines = explode("\n", trim($buckets));
            foreach ($bucket_lines as $bucket_line):
                $bucket_line = trim($bucket_line);
                if (!empty($bucket_line)):
                    echo '<p>[s3_gallery bucket="' . esc_url($bucket_line) . '"]</p>';
                endif;
            endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}


/**
 * Shortcode to Render Gallery
 */
add_shortcode('s3_gallery', 's3_gallery_shortcode');
function s3_gallery_shortcode($atts)
{
    $atts = shortcode_atts(array('bucket' => ''), $atts, 's3_gallery');
    $bucket_url = esc_url($atts['bucket']);

    if (!$bucket_url) {
        return '<p>Please provide a valid S3 bucket URL.</p>';
    }

    // Check AWS credentials (environment variables or constants)
    $aws_access_key = getenv('AWS_ACCESS_KEY_ID') ?: (defined('AWS_ACCESS_KEY_ID') ? AWS_ACCESS_KEY_ID : '');
    $aws_secret_key = getenv('AWS_SECRET_ACCESS_KEY') ?: (defined('AWS_SECRET_ACCESS_KEY') ? AWS_SECRET_ACCESS_KEY : '');

    if (empty($aws_access_key) || empty($aws_secret_key)) {
        return '<p>AWS credentials not configured. Please set AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY.</p>';
    }

    // Initialize S3 client
    $s3 = new S3Client([
        'version'     => 'latest',
        'region'      => 'us-east-1',
        'credentials' => [
            'key'    => $aws_access_key,
            'secret' => $aws_secret_key,
        ],
    ]);

    // Parse bucket name and path from URL
    $parsed_url  = parse_url($bucket_url);
    if (empty($parsed_url['host'])) {
        return '<p>Invalid bucket URL provided.</p>';
    }
    $host_parts  = explode('.', $parsed_url['host']);
    $bucket_name = $host_parts[0];      // e.g. "mybucket"
    $path        = isset($parsed_url['path']) ? trim($parsed_url['path'], '/') : '';

    // Subdirectory for higher resolution images (optional)
    $hires_dir = 'hires/';

    // 1) Fetch the base images (the main or "720" set).
    try {
        $baseResults = $s3->listObjects([
            'Bucket' => $bucket_name,
            'Prefix' => $path,
        ]);
    } catch (Exception $e) {
        return '<p>Error fetching base images: ' . $e->getMessage() . '</p>';
    }

    // 2) Fetch all possible hires images in "path/hires/" (for 960, 1440, 1920).
    $hiresPrefix = $path ? ($path . '/' . $hires_dir) : $hires_dir;
    try {
        $hiresResults = $s3->listObjects([
            'Bucket' => $bucket_name,
            'Prefix' => $hiresPrefix,
        ]);
    } catch (Exception $e) {
        // If listing fails, just fallback to base only
        $hiresResults = ['Contents' => []];
    }

    // Build a set of all hires keys for quick lookup
    $hiresKeys = [];
    if (!empty($hiresResults['Contents'])) {
        foreach ($hiresResults['Contents'] as $obj) {
            $hiresKeys[ $obj['Key'] ] = true;
        }
    }

    $images = [];
    if (!empty($baseResults['Contents'])) {
        foreach ($baseResults['Contents'] as $object) {
            // Only match images
            if (preg_match('/\\.(jpg|jpeg|png|webp)$/i', $object['Key'])) {
                // e.g. "someFolder/myImage.jpg"
                $relative_key = ltrim(str_replace($path, '', $object['Key']), '/');
                $filename     = pathinfo($relative_key, PATHINFO_FILENAME);
                $extension    = pathinfo($relative_key, PATHINFO_EXTENSION);

                // Base URL for the bucket path
                $base_url = rtrim($bucket_url, '/') . '/';

                // The main (720) image URL
                $url_720 = $base_url . $relative_key;

                // Potential hires filenames
                $key_960  = $path . '/' . $hires_dir . $filename . '_960.'  . $extension;
                $key_1440 = $path . '/' . $hires_dir . $filename . '_1440.' . $extension;
                $key_1920 = $path . '/' . $hires_dir . $filename . '_1920.' . $extension;

                // Build actual hires URLs if they exist, else fallback
                $url_960  = isset($hiresKeys[$key_960])  ? $base_url . $hires_dir . $filename . '_960.'  . $extension : $url_720;
                $url_1440 = isset($hiresKeys[$key_1440]) ? $base_url . $hires_dir . $filename . '_1440.' . $extension : $url_720;
                $url_1920 = isset($hiresKeys[$key_1920]) ? $base_url . $hires_dir . $filename . '_1920.' . $extension : $url_720;

                $images[] = [
                    'key'      => $object['Key'],
                    'caption'  => pathinfo($object['Key'], PATHINFO_FILENAME),
                    'url_720'  => $url_720,
                    'url_960'  => $url_960,
                    'url_1440' => $url_1440,
                    'url_1920' => $url_1920,
                ];
            }
        }
    }

    // Render the HTML gallery
    ob_start();
    ?>
    <div class="s3-gallery">
        <?php foreach ($images as $image): ?>
            <div class="s3-gallery-item">
                <a href="#"
                   class="s3-gallery-link"
                   data-src720="<?php echo esc_url($image['url_720']); ?>"
                   data-src960="<?php echo esc_url($image['url_960']); ?>"
                   data-src1440="<?php echo esc_url($image['url_1440']); ?>"
                   data-src1920="<?php echo esc_url($image['url_1920']); ?>"
                   data-caption="<?php echo esc_attr($image['caption']); ?>">

                   <!-- Always load 720 by default on the main page for performance -->
                   <img 
                        src="<?php echo esc_url($image['url_720']); ?>"
                        alt="<?php echo esc_attr($image['caption']); ?>"
                        loading="lazy"
                   />
                </a>
               <!--<p><?php echo esc_html($image['caption']); ?></p> -->
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

