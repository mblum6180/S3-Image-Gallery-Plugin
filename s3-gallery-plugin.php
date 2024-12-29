<?php
/**
 * Plugin Name: S3 Image Gallery (w/ Local Support)
 * Description: Automatically fetch images from S3 buckets OR local directories and display them in a lazy-loading gallery with captions and full-screen functionality.
 * Version: 2.0
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

        // Pass settings to JavaScript
        $watermark_url = get_option('s3_watermark_url', '');
        $watermark_enabled = get_option('s3_watermark_enabled', 1);

        wp_localize_script('s3-image-gallery-script', 'S3GallerySettings', array(
            'watermarkUrl' => esc_url($watermark_url),
            'watermarkEnabled' => (bool) $watermark_enabled,
        ));
        
        // Enqueue the disable-right-click script
        wp_enqueue_script(
            'disable-right-click',
            plugin_dir_url(__FILE__) . 'js/disable-right-click.js',
            array(), // No dependencies
            '1.0',
            true // Load in footer
        );
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

        if (isset($_POST['local_dirs'])) {
            update_option('local_dirs', sanitize_textarea_field($_POST['local_dirs']));
        }

        if (isset($_POST['watermark_url'])) {
            update_option('s3_watermark_url', esc_url_raw($_POST['watermark_url']));
        }

        // Save watermark enabled setting
        $watermark_enabled = isset($_POST['watermark_enabled']) ? 1 : 0;
        update_option('s3_watermark_enabled', $watermark_enabled);

        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $buckets       = get_option('s3_buckets', '');
    $local_dirs    = get_option('local_dirs', '');
    $watermark_url = get_option('s3_watermark_url', '');
    $watermark_enabled = get_option('s3_watermark_enabled', 1); // Default enabled
    ?>
    <div class="wrap">
        <h1>S3 Image Gallery Settings</h1>

        <form method="post">
            <h2>S3 Buckets</h2>
            <label for="s3_buckets">
                <p>Enter your S3 bucket URLs, one per line:</p>
            </label>
            <textarea id="s3_buckets" name="s3_buckets" rows="5" cols="50" class="large-text"><?php echo esc_textarea($buckets); ?></textarea>

            <hr />

            <h2>Local Directories</h2>
            <label for="local_dirs">
                <p>Enter absolute paths to local directories, one per line:</p>
            </label>
            <textarea id="local_dirs" name="local_dirs" rows="5" cols="50" class="large-text"><?php echo esc_textarea($local_dirs); ?></textarea>

            <hr />

            <h2>Watermark Settings</h2>
            <label for="watermark_url">
                <p>Enter the URL of the watermark image:</p>
            </label>
            <input id="watermark_url" name="watermark_url" type="url" class="regular-text" value="<?php echo esc_attr($watermark_url); ?>" />

            <br/><br/>
            <label for="watermark_enabled">
                <p>Enable Watermark:</p>
            </label>
            <input id="watermark_enabled" name="watermark_enabled" type="checkbox" value="1" <?php checked($watermark_enabled, 1); ?> />

            <br/><br/>
            <input type="submit" class="button-primary" value="Save Changes">
        </form>

        <hr />

        <h2>Usage Instructions</h2>
        <p>Use the following shortcodes to display your image galleries:</p>
        
        <h3>S3 Buckets</h3>
        <p>
            <code>[s3_gallery bucket="YOUR_S3_BUCKET_URL"]</code>
        </p>
        <p>Example:</p>
        <pre><code>[s3_gallery bucket="https://mybucket.s3.amazonaws.com/myfolder"]</code></pre>

        <h3>Local Directories</h3>
        <p>
            <code>[s3_gallery local_dir="YOUR_LOCAL_DIRECTORY_PATH"]</code>
        </p>
        <p>Example:</p>
        <pre><code>[s3_gallery local_dir="/var/www/html/wp-content/uploads/my-gallery"]</code></pre>

        <h3>High-Resolution Images</h3>
        <p>
            If high-resolution images are available, place them in a <code>hires</code> subfolder inside your S3 bucket or local directory.<br />
            Name the files with <code>_960</code>, <code>_1440</code>, or <code>_1920</code> suffixes (e.g., <code>image_960.jpg</code>).
        </p>

        <h3>Watermark</h3>
        <p>
            Enable the watermark option and specify the URL of the watermark image in the settings above.<br />
            This will overlay the watermark on all displayed images in the gallery.
        </p>

        <hr />

        <h2>Generated Shortcodes</h2>
        <p>Below are all your currently configured galleries, automatically generated for easy copy-paste:</p>

        <?php if (!empty($buckets)): ?>
            <h3>S3 Bucket Shortcodes</h3>
            <?php 
            $bucket_lines = explode("\n", trim($buckets));
            foreach ($bucket_lines as $bucket_line):
                $bucket_line = trim($bucket_line);
                if (!empty($bucket_line)):
                    ?>
                    <p><code>[s3_gallery bucket="<?php echo esc_url($bucket_line); ?>"]</code></p>
                    <?php
                endif;
            endforeach; 
            ?>
        <?php endif; ?>

        <?php if (!empty($local_dirs)): ?>
            <h3>Local Directory Shortcodes</h3>
            <?php 
            $dir_lines = explode("\n", trim($local_dirs));
            foreach ($dir_lines as $dir_line):
                $dir_line = trim($dir_line);
                if (!empty($dir_line)):
                    ?>
                    <p><code>[s3_gallery local_dir="<?php echo esc_attr($dir_line); ?>"]</code></p>
                    <?php
                endif;
            endforeach; 
            ?>
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
    // Accept either 'bucket' or 'local_dir' attributes
    $atts = shortcode_atts(
        array(
            'bucket'    => '',
            'local_dir' => '',
        ), 
        $atts, 
        's3_gallery'
    );

    $bucket_url = trim($atts['bucket']);
    $local_dir  = trim($atts['local_dir']);

    // Decide whether we're pulling from S3 or a local directory
    if (!$bucket_url && !$local_dir) {
        return '<p>Please provide either a valid S3 bucket URL or a local directory path.</p>';
    }

    // Prepare array of images (uniform structure for both sources)
    $images = array();

    // -----------------------------------------------------
    // CASE 1: Local Directory
    // -----------------------------------------------------
    if (!empty($local_dir)) {

        // Make sure directory exists
        if (!is_dir($local_dir)) {
            return '<p>Local directory does not exist or is not accessible: ' . esc_html($local_dir) . '</p>';
        }

        // Normalized base directory (remove trailing slash)
        $base_dir  = rtrim($local_dir, '/');
        $hires_dir = $base_dir . '/hires';

        // Helper function to convert absolute file path to a publicly accessible URL
        // Assumes $file_path is inside your WordPress installation.
        $file_path_to_url = function($file_path) {
            // Remove ABSPATH portion to get a relative path
            $relative_path = str_replace(ABSPATH, '', $file_path);
            // Build a full URL using site_url
            return site_url($relative_path);
        };

        // Read all files in the base_dir
        $base_files = @scandir($base_dir);
        if (!is_array($base_files)) {
            $base_files = array();
        }

        // Attempt to get a list of hires files
        $hires_files = array();
        if (is_dir($hires_dir)) {
            $hires_files = @scandir($hires_dir);
            if (!is_array($hires_files)) {
                $hires_files = array();
            }
        }

        // Create a quick lookup for hires filenames
        // E.g. 'myphoto_960.jpg' => true
        $hires_lookup = array();
        foreach ($hires_files as $hf) {
            $hires_lookup[$hf] = true;
        }

        // Loop through base files
        foreach ($base_files as $file) {
            // Skip dirs
            if ($file === '.' || $file === '..') {
                continue;
            }

            // Full path
            $full_path = $base_dir . '/' . $file;

            // Only handle actual files
            if (!is_file($full_path)) {
                continue;
            }

            // Only match images
            if (!preg_match('/\.(jpg|jpeg|png|webp)$/i', $file)) {
                continue;
            }

            // Build the standard pieces we need (filename, extension, etc.)
            $filename   = pathinfo($file, PATHINFO_FILENAME);
            $extension  = pathinfo($file, PATHINFO_EXTENSION);

            // The main (720) image URL
            $url_720 = $file_path_to_url($full_path);

            // Potential hires filenames in hires/ subdir
            $file_960  = $filename . '_960.'  . $extension;
            $file_1440 = $filename . '_1440.' . $extension;
            $file_1920 = $filename . '_1920.' . $extension;

            // Build actual hires URLs if they exist, else fallback
            $url_960  = isset($hires_lookup[$file_960])  
                ? $file_path_to_url($hires_dir . '/' . $file_960)  
                : $url_720;

            $url_1440 = isset($hires_lookup[$file_1440]) 
                ? $file_path_to_url($hires_dir . '/' . $file_1440) 
                : $url_720;

            $url_1920 = isset($hires_lookup[$file_1920]) 
                ? $file_path_to_url($hires_dir . '/' . $file_1920) 
                : $url_720;

            $images[] = array(
                'key'      => $file,
                'caption'  => $filename,
                'url_720'  => $url_720,
                'url_960'  => $url_960,
                'url_1440' => $url_1440,
                'url_1920' => $url_1920,
            );
        }

        // Log for debugging (optional)
        // error_log(print_r($images, true));
    }

    // -----------------------------------------------------
    // CASE 2: S3 Bucket
    // -----------------------------------------------------
    else if (!empty($bucket_url)) {

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
        $bucket_name = $host_parts[0]; // e.g. "mybucket"
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
                $hiresKeys[$obj['Key']] = true;
            }
        }

        // Prepare images
        if (!empty($baseResults['Contents'])) {
            foreach ($baseResults['Contents'] as $object) {

                // Skip any objects that are in the hires/ subfolder
                if (strpos($object['Key'], $hires_dir) !== false) {
                    continue;
                }

                // Only match images
                if (preg_match('/\\.(jpg|jpeg|png|webp)$/i', $object['Key'])) {
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

        // Log for debugging (optional)
        // error_log(print_r($images, true));
    }

    // -----------------------------------------------------
    // Render the HTML gallery (common for both local & S3)
    // -----------------------------------------------------
    ob_start();
    ?>
        <div class="s3-gallery">
            <?php foreach ($images as $image): ?>
                <div class="s3-gallery-item">
                    <div class="s3-gallery-image-container">
                        <a href="#"
                        class="s3-gallery-link"
                        data-src720="<?php echo esc_url($image['url_720']); ?>"
                        data-src960="<?php echo esc_url($image['url_960']); ?>"
                        data-src1440="<?php echo esc_url($image['url_1440']); ?>"
                        data-src1920="<?php echo esc_url($image['url_1920']); ?>"
                        data-caption="<?php echo esc_attr($image['caption']); ?>">

                        <!-- Always load the 720 image by default for performance -->
                        <img 
                                src="<?php echo esc_url($image['url_720']); ?>"
                                alt="<?php echo esc_attr($image['caption']); ?>"
                                loading="lazy"
                                width="719"
                                height="479"
                        />
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php
    return ob_get_clean();
}
