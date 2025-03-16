<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Admin page content
function bbpas_admin_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('bbPress API Server', 'bbpress-api-server'); ?></h1>

                <!-- Tabs for API Details -->
                <div class="card">
                    <h2><?php _e('API Details', 'bbpress-api-server'); ?></h2>
                    <p><?php _e('This plugin provides REST API endpoints for bbPress forums, topics, and replies.', 'bbpress-api-server'); ?></p>
                    <div class="bbpas-tabs">
                        <ul class="nav-tab-wrapper">
                            <li><a href="#bbpas-tab-info" class="nav-tab nav-tab-active"><?php _e('API Status', 'bbpress-api-server'); ?></a></li>
                            <li><a href="#bbpas-tab-endpoints" class="nav-tab"><?php _e('Available Endpoints', 'bbpress-api-server'); ?></a></li>
                            <li><a href="#bbpas-tab-instructions" class="nav-tab"><?php _e('Instructions', 'bbpress-api-server'); ?></a></li>
                        </ul>

                        <!-- Tab: API Information -->
                        <div id="bbpas-tab-info" class="tab-content" style="display: block;">
                            <p><strong><span class="dashicons dashicons-hammer"></span> <?php _e('API Namespace:', 'bbpress-api-server'); ?></strong> <?php echo BBPAS_API_NAMESPACE; ?></p>
                            <p><strong><span class="dashicons dashicons-admin-plugins"></span> <?php _e('Plugin Version:', 'bbpress-api-server'); ?></strong> <?php echo BBPAS_VERSION; ?></p>
                            <p><strong><span class="dashicons dashicons-buddicons-forums"></span> <?php _e('bbPress Version:', 'bbpress-api-server'); ?></strong> <?php echo bbp_get_version(); ?></p>
                            <?php
                            $api_test_url = rest_url(BBPAS_API_NAMESPACE . '/test');
                            $response = wp_remote_get($api_test_url);
                            $current_timestamp = current_time('mysql');

                            if (is_wp_error($response)) {
                                echo '<p><strong><span class="dashicons dashicons-update"></span> ' . __('Status:', 'bbpress-api-server') . '</strong> <span style="color: red;"><span class="dashicons dashicons-warning"></span> ' . __('Error', 'bbpress-api-server') . '</span></p>';
                                echo '<p>' . $response->get_error_message() . '</p>';
                            } else {
                                $body = json_decode(wp_remote_retrieve_body($response), true);
                                if ($body['success']) {
                                    echo '<p><strong><span class="dashicons dashicons-update"></span> ' . __('Status:', 'bbpress-api-server') . '</strong> <span style="color: green;"><span class="dashicons dashicons-yes-alt"></span> ' . __('Active', 'bbpress-api-server') . '</span></p>';
                                } else {
                                    echo '<p><strong><span class="dashicons dashicons-update"></span> ' . __('Status:', 'bbpress-api-server') . '</strong> <span style="color: red;"><span class="dashicons dashicons-no-alt"></span> ' . __('Inactive', 'bbpress-api-server') . '</span></p>';
                                }
                                echo '<p><strong><span class="dashicons dashicons-media-code"></span> ' . __('Message:', 'bbpress-api-server') . '</strong> ' . esc_html($body['message']) . '</p>';
                            }
                            ?>
                            <p><strong><span class="dashicons dashicons-clock"></span> <?php _e('Last Check:', 'bbpress-api-server'); ?></strong> <?php echo $current_timestamp; ?></p>
                        </div>

                        <!-- Tab: Available Endpoints -->
                        <div id="bbpas-tab-endpoints" class="tab-content" style="display: none;">
                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th><?php _e('Endpoint', 'bbpress-api-server'); ?></th>
                                        <th><?php _e('Method', 'bbpress-api-server'); ?></th>
                                        <th><?php _e('Description', 'bbpress-api-server'); ?></th>
                                        <th><?php _e('Example', 'bbpress-api-server'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/test</code></td>
                                        <td>GET</td>
                                        <td><?php _e('Test if the API is working', 'bbpress-api-server'); ?></td>
                                        <td><a href="#" class="bbpas-toggle-example"><?php _e('Show Example', 'bbpress-api-server'); ?></a>
                                            <div class="bbpas-example" style="display: none;">
                                                <pre><?php echo esc_html("GET " . rest_url(BBPAS_API_NAMESPACE . '/test') . "\nResponse:\n{\n  \"success\": true,\n  \"message\": \"Connection successful\"\n}"); ?></pre>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/forums</code></td>
                                        <td>GET</td>
                                        <td><?php _e('Get a list of forums', 'bbpress-api-server'); ?></td>
                                        <td><a href="#" class="bbpas-toggle-example"><?php _e('Show Example', 'bbpress-api-server'); ?></a>
                                            <div class="bbpas-example" style="display: none;">
                                                <pre><?php echo esc_html("GET " . rest_url(BBPAS_API_NAMESPACE . '/forums?per_page=5') . "\nResponse:\n[{\"id\": 1, \"title\": \"General\", ...}]"); ?></pre>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/forum/{id}</code></td>
                                        <td>GET</td>
                                        <td><?php _e('Get details of a specific forum', 'bbpress-api-server'); ?></td>
                                        <td><a href="#" class="bbpas-toggle-example"><?php _e('Show Example', 'bbpress-api-server'); ?></a>
                                            <div class="bbpas-example" style="display: none;">
                                                <pre><?php echo esc_html("GET " . rest_url(BBPAS_API_NAMESPACE . '/forum/1') . "\nResponse:\n{\"id\": 1, \"title\": \"General\", ...}"); ?></pre>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/forum/{id}</code></td>
                                        <td>POST</td>
                                        <td><?php _e('Create a new topic in a forum', 'bbpress-api-server'); ?></td>
                                        <td><a href="#" class="bbpas-toggle-example"><?php _e('Show Example', 'bbpress-api-server'); ?></a>
                                            <div class="bbpas-example" style="display: none;">
                                                <pre><?php echo esc_html("POST " . rest_url(BBPAS_API_NAMESPACE . '/forum/1') . "\nBody:\n{\n  \"title\": \"New Topic\",\n  \"content\": \"Hello!\",\n  \"email\": \"user@example.com\"\n}\nResponse:\n{\"id\": 10, \"message\": \"Topic created successfully\"}"); ?></pre>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/topics</code></td>
                                        <td>GET</td>
                                        <td><?php _e('Get a list of topics', 'bbpress-api-server'); ?></td>
                                        <td><a href="#" class="bbpas-toggle-example"><?php _e('Show Example', 'bbpress-api-server'); ?></a>
                                            <div class="bbpas-example" style="display: none;">
                                                <pre><?php echo esc_html("GET " . rest_url(BBPAS_API_NAMESPACE . '/topics?forum_id=1') . "\nResponse:\n[{\"id\": 10, \"title\": \"New Topic\", ...}]"); ?></pre>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/topic/{id}</code></td>
                                        <td>GET</td>
                                        <td><?php _e('Get details of a specific topic', 'bbpress-api-server'); ?></td>
                                        <td><a href="#" class="bbpas-toggle-example"><?php _e('Show Example', 'bbpress-api-server'); ?></a>
                                            <div class="bbpas-example" style="display: none;">
                                                <pre><?php echo esc_html("GET " . rest_url(BBPAS_API_NAMESPACE . '/topic/10') . "\nResponse:\n{\"id\": 10, \"title\": \"New Topic\", ...}"); ?></pre>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/topic/{id}</code></td>
                                        <td>POST</td>
                                        <td><?php _e('Create a reply to a topic', 'bbpress-api-server'); ?></td>
                                        <td><a href="#" class="bbpas-toggle-example"><?php _e('Show Example', 'bbpress-api-server'); ?></a>
                                            <div class="bbpas-example" style="display: none;">
                                                <pre><?php echo esc_html("POST " . rest_url(BBPAS_API_NAMESPACE . '/topic/10') . "\nBody:\n{\n  \"content\": \"Great post!\",\n  \"email\": \"user@example.com\"\n}\nResponse:\n{\"id\": 11, \"message\": \"Reply created successfully\"}"); ?></pre>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/replies</code></td>
                                        <td>GET</td>
                                        <td><?php _e('Get replies for a topic', 'bbpress-api-server'); ?></td>
                                        <td><a href="#" class="bbpas-toggle-example"><?php _e('Show Example', 'bbpress-api-server'); ?></a>
                                            <div class="bbpas-example" style="display: none;">
                                                <pre><?php echo esc_html("GET " . rest_url(BBPAS_API_NAMESPACE . '/replies?topic_id=10') . "\nResponse:\n[{\"id\": 11, \"content\": \"Great post!\", ...}]"); ?></pre>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/reply/{id}</code></td>
                                        <td>GET</td>
                                        <td><?php _e('Get details of a specific reply', 'bbpress-api-server'); ?></td>
                                        <td><a href="#" class="bbpas-toggle-example"><?php _e('Show Example', 'bbpress-api-server'); ?></a>
                                            <div class="bbpas-example" style="display: none;">
                                                <pre><?php echo esc_html("GET " . rest_url(BBPAS_API_NAMESPACE . '/reply/11') . "\nResponse:\n{\"id\": 11, \"content\": \"Great post!\", ...}"); ?></pre>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/reply/{id}</code></td>
                                        <td>POST</td>
                                        <td><?php _e('Create a reply to another reply', 'bbpress-api-server'); ?></td>
                                        <td><a href="#" class="bbpas-toggle-example"><?php _e('Show Example', 'bbpress-api-server'); ?></a>
                                            <div class="bbpas-example" style="display: none;">
                                                <pre><?php echo esc_html("POST " . rest_url(BBPAS_API_NAMESPACE . '/reply/11') . "\nBody:\n{\n  \"content\": \"Thanks!\",\n  \"email\": \"user@example.com\"\n}\nResponse:\n{\"id\": 12, \"message\": \"Reply created successfully\"}"); ?></pre>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/topic-tags</code></td>
                                        <td>GET</td>
                                        <td><?php _e('Get a list of topic tags', 'bbpress-api-server'); ?></td>
                                        <td><a href="#" class="bbpas-toggle-example"><?php _e('Show Example', 'bbpress-api-server'); ?></a>
                                            <div class="bbpas-example" style="display: none;">
                                                <pre><?php echo esc_html("GET " . rest_url(BBPAS_API_NAMESPACE . '/topic-tags') . "\nResponse:\n[{\"id\": 1, \"name\": \"support\", ...}]"); ?></pre>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/stats</code></td>
                                        <td>GET</td>
                                        <td><?php _e('Get forum statistics', 'bbpress-api-server'); ?></td>
                                        <td><a href="#" class="bbpas-toggle-example"><?php _e('Show Example', 'bbpress-api-server'); ?></a>
                                            <div class="bbpas-example" style="display: none;">
                                                <pre><?php echo esc_html("GET " . rest_url(BBPAS_API_NAMESPACE . '/stats') . "\nResponse:\n{\"forums\": {\"total\": 5, ...}, ...}"); ?></pre>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Tab: Instructions -->
                        <div id="bbpas-tab-instructions" class="tab-content" style="display: none;">
                            <h3><?php _e('Getting Started', 'bbpress-api-server'); ?></h3>
                            <ol>
                                <li><?php _e('Install and activate bbPress on your WordPress site.', 'bbpress-api-server'); ?></li>
                                <li><?php _e('Activate this plugin (bbPress API Server) from the Plugins page.', 'bbpress-api-server'); ?></li>
                                <li><?php _e('Enable pretty permalinks in Settings > Permalinks (recommended: "Post name").', 'bbpress-api-server'); ?></li>
                                <li><?php _e('Test the API using the "Test API" tool above or an external tool like Postman.', 'bbpress-api-server'); ?></li>
                            </ol>
                            <h3><?php _e('More Help', 'bbpress-api-server'); ?></h3>
                            <ul>
                            <li><a href="https://wpsdk.com/?p=1076#using-the-api" target="_blank"><?php _e('How to Using the API？', 'bbpress-api-server'); ?></a></li>
                            <li><a href="https://wpsdk.com/?p=1076#permissions-and-authentication" target="_blank"><?php _e('Permissions and Authentication', 'bbpress-api-server'); ?></a></li>
                            <li><a href="https://wpsdk.com/?p=1076#advanced-tips" target="_blank"><?php _e('Advanced Tips', 'bbpress-api-server'); ?></a></li>
                          </ul>
                        </div>

                    </div>
                </div>

        <!-- API Test Tool -->
        <div class="card">
            <h2><?php _e('Test API', 'bbpress-api-server'); ?></h2>
            <form method="post" action="">
                <p>
                    <label for="bbpas_test_endpoint"><?php _e('Endpoint:', 'bbpress-api-server'); ?></label><br>
                    <input type="text" id="bbpas_test_endpoint" name="bbpas_test_endpoint" value="/<?php echo BBPAS_API_NAMESPACE; ?>/test" style="width: 300px;">
                </p>
                <p>
                        <label for="bbpas_test_method"><?php _e('Method:', 'bbpress-api-server'); ?></label><br>
                    <select id="bbpas_test_method" name="bbpas_test_method">
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                    </select>
                </p>
                <p>
                    <label for="bbpas_test_params"><?php _e('Parameters (JSON):', 'bbpress-api-server'); ?></label><br>
                    <textarea id="bbpas_test_params" name="bbpas_test_params" rows="4" cols="50" placeholder='{"param1": "value1"}'></textarea>
                </p>
                <?php wp_nonce_field('bbpas_test_api', 'bbpas_test_nonce'); ?>
                <p><input type="submit" value="<?php _e('Test Now', 'bbpress-api-server'); ?>" class="button button-primary"></p>
            </form>
            <?php
            if (isset($_POST['bbpas_test_endpoint']) && check_admin_referer('bbpas_test_api', 'bbpas_test_nonce')) {
                $endpoint = sanitize_text_field($_POST['bbpas_test_endpoint']);
                $method = sanitize_text_field($_POST['bbpas_test_method']);
                $params = !empty($_POST['bbpas_test_params']) ? json_decode(stripslashes($_POST['bbpas_test_params']), true) : array();

                $args = array('method' => $method);
                if ($method === 'POST' && !empty($params)) {
                    $args['body'] = $params;
                }
                $response = wp_remote_request(rest_url($endpoint), $args);

                echo '<h3>' . __('Test Result', 'bbpress-api-server') . '</h3>';
                if (is_wp_error($response)) {
                    echo '<pre>' . esc_html($response->get_error_message()) . '</pre>';
                } else {
                    echo '<pre>' . esc_html(print_r(json_decode(wp_remote_retrieve_body($response), true), true)) . '</pre>';
                }
            }
            ?>
        </div>

        <!-- Inline Styles and Scripts -->
        <style>
            .card { max-width: unset; }
            .nav-tab-wrapper, .wrap h2.nav-tab-wrapper, h1.nav-tab-wrapper { padding-top: 0; }
            .bbpas-tabs .nav-tab-wrapper { margin-bottom: 20px; }
            .bbpas-tabs .nav-tab { cursor: pointer; }
            .bbpas-tabs .nav-tab-active { background: #fff; border-bottom: 1px solid #fff; }
            .bbpas-tabs .tab-content { padding: 10px; }
            .bbpas-example { margin-top: 10px; background: #f5f5f5; padding: 10px; border-radius: 4px; }
            .bbpas-toggle-example { text-decoration: underline; color: #0073aa; }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Tab switching
                const tabs = document.querySelectorAll('.bbpas-tabs .nav-tab');
                const contents = document.querySelectorAll('.bbpas-tabs .tab-content');
                tabs.forEach(tab => {
                    tab.addEventListener('click', function(e) {
                        e.preventDefault();
                        tabs.forEach(t => t.classList.remove('nav-tab-active'));
                        contents.forEach(c => c.style.display = 'none');
                        tab.classList.add('nav-tab-active');
                        document.querySelector(tab.getAttribute('href')).style.display = 'block';
                    });
                });

                // Toggle examples
                const toggles = document.querySelectorAll('.bbpas-toggle-example');
                toggles.forEach(toggle => {
                    toggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        const example = toggle.nextElementSibling;
                        const isVisible = example.style.display === 'block';
                        example.style.display = isVisible ? 'none' : 'block';
                        toggle.textContent = isVisible ? '<?php _e('Show Example', 'bbpress-api-server'); ?>' : '<?php _e('Hide Example', 'bbpress-api-server'); ?>';
                    });
                });
            });
        </script>
    </div>
    <?php
}
