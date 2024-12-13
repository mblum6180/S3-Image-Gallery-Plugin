<?php
/**
 * Plugin Name: S3 Image Gallery
 * Description: Automatically fetch images from S3 buckets and display them in a lazy-loading gallery with captions and full-screen functionality.
 * Version: 1.5
 * Author: Matthew Blum
 */

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
use Aws\S3\S3Client;

// Add Admin Menu
add_action('admin_menu', 's3_gallery_menu');
function s3_gallery_menu() {
    add_menu_page(
        'S3 Gallery Settings',
        'S3 Gallery',
        'manage_options',
        's3-gallery',
        's3_gallery_settings_page'
    );
}

// Settings Page
function s3_gallery_settings_page() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['s3_buckets'])) {
        update_option('s3_buckets', sanitize_textarea_field($_POST['s3_buckets']));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $buckets = get_option('s3_buckets', '');
    ?>
    <div class="wrap">
        <h1>S3 Image Gallery</h1>
        <form method="post">
            <label for="s3_buckets">
                <p>Enter your S3 bucket URLs, one per line:</p>
            </label>
            <textarea id="s3_buckets" name="s3_buckets" rows="10" cols="50" class="large-text"><?php echo esc_textarea($buckets); ?></textarea>
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

// Shortcode to Render Gallery
add_shortcode('s3_gallery', 's3_gallery_shortcode');
function s3_gallery_shortcode($atts) {
    $atts = shortcode_atts(array('bucket' => ''), $atts, 's3_gallery');
    $bucket_url = esc_url($atts['bucket']);

    if (!$bucket_url) {
        return '<p>Please provide a valid S3 bucket URL.</p>';
    }

    $aws_access_key = getenv('AWS_ACCESS_KEY_ID') ?: (defined('AWS_ACCESS_KEY_ID') ? AWS_ACCESS_KEY_ID : '');
    $aws_secret_key = getenv('AWS_SECRET_ACCESS_KEY') ?: (defined('AWS_SECRET_ACCESS_KEY') ? AWS_SECRET_ACCESS_KEY : '');

    if (empty($aws_access_key) || empty($aws_secret_key)) {
        return '<p>AWS credentials not configured. Please set AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY.</p>';
    }

    $s3 = new S3Client([
        'version' => 'latest',
        'region'  => 'us-east-1',
        'credentials' => [
            'key'    => $aws_access_key,
            'secret' => $aws_secret_key,
        ],
    ]);

    $parsed_url = parse_url($bucket_url);
    $host_parts = explode('.', $parsed_url['host']);
    $bucket_name = $host_parts[0];
    $path = trim($parsed_url['path'], '/');

    try {
        $results = $s3->listObjects([
            'Bucket' => $bucket_name,
            'Prefix' => $path,
        ]);

        $images = [];
        foreach ($results['Contents'] as $object) {
            if (preg_match('/\\.(jpg|jpeg|png|webp)$/i', $object['Key'])) {
                $relative_key = ltrim(str_replace($path, '', $object['Key']), '/');
                $images[] = [
                    'url' => rtrim($bucket_url, '/') . '/' . $relative_key,
                    'key' => $object['Key']
                ];
            }
        }
    } catch (Exception $e) {
        return '<p>Error fetching images: ' . $e->getMessage() . '</p>';
    }

    // Render Gallery with Captions and Full-Screen View
    $output = '<div class="s3-gallery" style="display: flex; flex-wrap: wrap; gap: 10px;">';
    foreach ($images as $image) {
        $caption = pathinfo($image['key'], PATHINFO_FILENAME); // Default caption from file name
        $output .= '<div class="s3-gallery-item" style="flex: 0 0 80%; margin: auto; text-align: center;">';
        $output .= '<a href="#" class="s3-gallery-link" data-src="' . esc_url($image['url']) . '" data-caption="' . esc_attr($caption) . '">';
        $output .= '<img src="' . esc_url($image['url']) . '" loading="lazy" style="width: 100%; height: auto;" />';
        $output .= '</a>';
        $output .= '<p style="margin: 5px 0;">' . esc_html($caption) . '</p>';
        $output .= '</div>';
    }
    $output .= '</div>';

    // Include JavaScript for Full-Screen View with Navigation, Close Button, and Keyboard Support
    $output .= '<script>
        document.addEventListener("DOMContentLoaded", () => {
            const images = document.querySelectorAll(".s3-gallery-link");
            let currentIndex = 0;
            const overlay = document.createElement("div");
            overlay.style = "position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.9); display: none; align-items: center; justify-content: center; z-index: 1000; transition: opacity 0.3s ease;";

            const img = document.createElement("img");
            img.style = "max-width: 100%; max-height: 100%; transition: transform 0.3s ease;";

            const caption = document.createElement("p");
            caption.style = "color: white; text-align: center; margin-top: 10px; position: absolute; bottom: 10%; width: 100%; text-align: center; font-size: 1.2rem;";

            const prevButton = document.createElement("button");
            prevButton.innerText = "<";
            prevButton.style = "position: absolute; left: 20px; top: 50%; transform: translateY(-50%); background: none; border: none; color: white; font-size: 2rem; cursor: pointer;";
            prevButton.addEventListener("click", () => {
                currentIndex = (currentIndex - 1 + images.length) % images.length;
                showImage();
            });

            const nextButton = document.createElement("button");
            nextButton.innerText = ">";
            nextButton.style = "position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: none; border: none; color: white; font-size: 2rem; cursor: pointer;";
            nextButton.addEventListener("click", () => {
                currentIndex = (currentIndex + 1) % images.length;
                showImage();
            });

            const closeButton = document.createElement("button");
            closeButton.innerText = "X";
            closeButton.style = "position: absolute; top: 20px; right: 20px; background: none; border: none; color: white; font-size: 2rem; cursor: pointer;";
            closeButton.addEventListener("click", () => {
                overlay.style.display = "none";
            });

            overlay.appendChild(prevButton);
            overlay.appendChild(nextButton);
            overlay.appendChild(closeButton);
            overlay.appendChild(img);
            overlay.appendChild(caption);

            overlay.addEventListener("click", (e) => {
                if (e.target === overlay) {
                    overlay.style.display = "none";
                }
            });

            document.body.appendChild(overlay);

            images.forEach((link, index) => {
                link.addEventListener("click", (event) => {
                    event.preventDefault();
                    currentIndex = index;
                    showImage();
                    overlay.style.display = "flex";
                });
            });

            document.addEventListener("keydown", (event) => {
                if (overlay.style.display === "flex") {
                    if (event.key === "ArrowRight") {
                        currentIndex = (currentIndex + 1) % images.length;
                        showImage();
                    } else if (event.key === "ArrowLeft") {
                        currentIndex = (currentIndex - 1 + images.length) % images.length;
                        showImage();
                    } else if (event.key === "Escape") {
                        overlay.style.display = "none";
                    }
                }
            });

                function showImage() {
                const currentLink = images[currentIndex];
                img.src = currentLink.dataset.src;
                caption.innerText = currentLink.dataset.caption || "";
            }
        });
    </script>';

    return $output;
}
?>
