<?php
/**
 * Plugin Name: bbPress API Server
 * Plugin URI: https://cyberforums.com/document/bbpress-api-server
 * Description: Provides comprehensive API endpoints for bbPress forums, topics, and replies
 * Version: 1.0.0
 * Author: Cyberforums
 * Author URI: https://cyberforums.com
 * Text Domain: bbpress-api-server
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BBPAS_VERSION', '1.0.0');
define('BBPAS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BBPAS_PLUGIN_URL', plugin_dir_url(__FILE__));

// API namespace and version
define('BBPAS_API_NAMESPACE', 'bbpas/v1');

// Check if bbPress is active
function bbpas_check_required_plugins() {
    if (!class_exists('bbPress')) {
        add_action('admin_notices', 'bbpas_admin_notice');
        return false;
    }
    return true;
}

// Admin notice if required plugins are not active
function bbpas_admin_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('bbPress API Server requires bbPress to be installed and activated.', 'bbpress-api-server'); ?></p>
    </div>
    <?php
}

// Register REST API routes
function bbpas_register_routes() {
    // === Test endpoint ===
    register_rest_route(BBPAS_API_NAMESPACE, '/test', array(
        'methods' => 'GET',
        'callback' => 'bbpas_test_connection',
        'permission_callback' => '__return_true',
    ));
    
    // === Forum endpoints ===
    // Get all forums
    register_rest_route(BBPAS_API_NAMESPACE, '/forums', array(
    'methods' => 'GET',
    'callback' => 'bbpas_get_forums',
    'permission_callback' => '__return_true',
    'args' => array(
        'parent' => array(
            'validate_callback' => function($param) {
                return is_numeric($param) || $param === '0';
            },
            'default' => null,
            'description' => 'Filter forums by parent ID. Use 0 for root forums.',
        ),
        'per_page' => array(
            'validate_callback' => function($param) {
                return is_numeric($param) && ($param == -1 || ($param > 0 && $param <= 100));
            },
            'default' => -1,
            'description' => 'Number of forums to return per page. Use -1 to return all.',
        ),
        'page' => array(
            'validate_callback' => function($param) {
                return is_numeric($param) && $param > 0;
            },
            'default' => 1,
            'description' => 'Current page of forum results.',
        ),
    ),
));
    
    // Get single forum details
    register_rest_route(BBPAS_API_NAMESPACE, '/forum/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'bbpas_get_forum_details',
        'permission_callback' => '__return_true',
        'args' => array(
            'id' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                },
                'description' => 'Forum ID',
            ),
        ),
    ));
    
    // === Topic endpoints ===
    // Get topics (with optional forum filter)
    register_rest_route(BBPAS_API_NAMESPACE, '/topics', array(
        'methods' => 'GET',
        'callback' => 'bbpas_get_topics',
        'permission_callback' => '__return_true',
        'args' => array(
            'forum_id' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param);
                },
                'description' => 'Filter topics by forum ID',
            ),
            'per_page' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0 && $param <= 100;
                },
                'default' => 10,
                'description' => 'Number of topics to return per page',
            ),
            'page' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
                'default' => 1,
                'description' => 'Current page of topic results',
            ),
            'orderby' => array(
                'validate_callback' => function($param) {
                    return in_array($param, ['date', 'title', 'activity', 'popularity']);
                },
                'default' => 'activity',
                'description' => 'Order topics by parameter: date, title, activity, popularity',
            ),
            'order' => array(
                'validate_callback' => function($param) {
                    return in_array(strtoupper($param), ['ASC', 'DESC']);
                },
                'default' => 'DESC',
                'description' => 'Order direction: ASC or DESC',
            ),
        ),
    ));
    
    // Get single topic details
    register_rest_route(BBPAS_API_NAMESPACE, '/topic/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'bbpas_get_topic_details',
        'permission_callback' => '__return_true',
        'args' => array(
            'id' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                },
                'description' => 'Topic ID',
            ),
            'with_replies' => array(
                'validate_callback' => function($param) {
                    return rest_is_boolean($param);
                },
                'default' => false,
                'description' => 'Include topic replies',
            ),
        ),
    ));
    
    // === Reply endpoints ===
    // Get replies for a topic
    register_rest_route(BBPAS_API_NAMESPACE, '/replies', array(
        'methods' => 'GET',
        'callback' => 'bbpas_get_replies',
        'permission_callback' => '__return_true',
        'args' => array(
            'topic_id' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                },
                'description' => 'Topic ID to get replies from',
            ),
            'per_page' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0 && $param <= 100;
                },
                'default' => 20,
                'description' => 'Number of replies to return per page',
            ),
            'page' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
                'default' => 1,
                'description' => 'Current page of reply results',
            ),
            'order' => array(
                'validate_callback' => function($param) {
                    return in_array(strtoupper($param), ['ASC', 'DESC']);
                },
                'default' => 'ASC',
                'description' => 'Order direction: ASC or DESC',
            ),
        ),
    ));
    
    // Get single reply details
    register_rest_route(BBPAS_API_NAMESPACE, '/reply/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'bbpas_get_reply_details',
        'permission_callback' => '__return_true',
        'args' => array(
            'id' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                },
                'description' => 'Reply ID',
            ),
        ),
    ));
    
    // === User endpoints ===
    // Get user forum activity
    register_rest_route(BBPAS_API_NAMESPACE, '/user/(?P<id>\d+)/activity', array(
        'methods' => 'GET',
        'callback' => 'bbpas_get_user_activity',
        'permission_callback' => '__return_true',
        'args' => array(
            'id' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                },
                'description' => 'User ID',
            ),
            'type' => array(
                'validate_callback' => function($param) {
                    return in_array($param, ['topics', 'replies', 'all']);
                },
                'default' => 'all',
                'description' => 'Activity type: topics, replies, or all',
            ),
            'per_page' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0 && $param <= 50;
                },
                'default' => 10,
                'description' => 'Number of activities to return',
            ),
        ),
    ));
    
    // === Search endpoint ===
    register_rest_route(BBPAS_API_NAMESPACE, '/search', array(
        'methods' => 'GET',
        'callback' => 'bbpas_search',
        'permission_callback' => '__return_true',
        'args' => array(
            'query' => array(
                'required' => true,
                'description' => 'Search query',
            ),
            'type' => array(
                'validate_callback' => function($param) {
                    return in_array($param, ['forum', 'topic', 'reply', 'all']);
                },
                'default' => 'all',
                'description' => 'Type of content to search: forum, topic, reply, all',
            ),
            'per_page' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0 && $param <= 50;
                },
                'default' => 10,
                'description' => 'Number of results to return',
            ),
            'page' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
                'default' => 1,
                'description' => 'Current page of search results',
            ),
        ),
    ));
    
    // === Stats endpoint ===
    register_rest_route(BBPAS_API_NAMESPACE, '/stats', array(
        'methods' => 'GET',
        'callback' => 'bbpas_get_stats',
        'permission_callback' => '__return_true',
    ));
    
}

// === Test endpoint function ===
function bbpas_test_connection() {
    if (!function_exists('bbp_get_forum_post_type')) {
        return new WP_Error('bbpress_not_active', __('bbPress is not active', 'bbpress-api-server'), array('status' => 500));
    }
    
    return array(
        'success' => true,
        'message' => __('Connection successful', 'bbpress-api-server'),
        'version' => BBPAS_VERSION,
        'bbpress_version' => bbp_get_version(),
        'api_namespace' => BBPAS_API_NAMESPACE,
    );
}

// === Forum endpoint functions ===

function bbpas_get_forums($request) {
    if (!function_exists('bbp_get_forum_post_type')) {
        return new WP_Error('bbpress_not_active', __('bbPress is not active', 'bbpress-api-server'), array('status' => 500));
    }
    
    $parent = $request->get_param('parent');
    $per_page = $request->get_param('per_page');
    $page = $request->get_param('page');
    
    $args = array(
        'post_type' => bbp_get_forum_post_type(),
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'orderby' => 'menu_order',
        'order' => 'ASC',
    );
    
    // If parent parameter is set, filter by parent forum
    if ($parent !== null) {
        $args['post_parent'] = $parent;
    }
    
    $forums_query = new WP_Query($args);
    $forums = array();
    
    if ($forums_query->have_posts()) {
        while ($forums_query->have_posts()) {
            $forums_query->the_post();
            $forum_id = get_the_ID();
            
            // Skip hidden forums or forums user doesn't have access to
            if (!bbp_is_forum_public($forum_id)) {
                continue;
            }
            
            $forums[] = bbpas_prepare_forum_data($forum_id);
        }
        wp_reset_postdata();
    }
    
    // Add pagination info to response
    $response = rest_ensure_response($forums);
    $response->header('X-WP-Total', $forums_query->found_posts);
    $response->header('X-WP-TotalPages', $forums_query->max_num_pages);
    
    return $response;
}

function bbpas_get_forum_details($request) {
    $forum_id = $request->get_param('id');
    
    if (!bbp_is_forum($forum_id)) {
        return new WP_Error('invalid_forum', __('Invalid forum ID', 'bbpress-api-server'), array('status' => 404));
    }
    
    if (!bbp_is_forum_public($forum_id)) {
        return new WP_Error('private_forum', __('This forum is not public', 'bbpress-api-server'), array('status' => 403));
    }
    
    return bbpas_prepare_forum_data($forum_id, true);
}

function bbpas_prepare_forum_data($forum_id, $with_subforums = false) {
    $forum_data = array(
        'id' => $forum_id,
        'title' => bbp_get_forum_title($forum_id),
        'permalink' => bbp_get_forum_permalink($forum_id),
        'description' => bbp_get_forum_content($forum_id),
        'topic_count' => bbp_get_forum_topic_count($forum_id),
        'reply_count' => bbp_get_forum_reply_count($forum_id),
        'total_topic_count' => bbp_get_forum_topic_count($forum_id, true),
        'total_reply_count' => bbp_get_forum_reply_count($forum_id, true),
        'last_active' => bbp_get_forum_last_active_time($forum_id),
        'last_active_time' => get_post_meta($forum_id, '_bbp_last_active_time', true),
        'parent_forum' => bbp_get_forum_parent_id($forum_id),
        'status' => bbp_get_forum_status($forum_id),
        'is_category' => bbp_is_forum_category($forum_id),
        'forum_type' => bbp_get_forum_type($forum_id),
        'visibility' => bbp_get_forum_visibility($forum_id),
    );
    
    // Add last topic info if available
    $last_topic_id = bbp_get_forum_last_topic_id($forum_id);
    if ($last_topic_id) {
        $forum_data['last_topic'] = array(
            'id' => $last_topic_id,
            'title' => bbp_get_forum_last_topic_title($forum_id),
            'permalink' => bbp_get_topic_permalink($last_topic_id),
            'author_id' => bbp_get_topic_author_id($last_topic_id),
            'author_name' => bbp_get_topic_author_display_name($last_topic_id),
        );
    }
    
    // Include subforums if requested
    if ($with_subforums) {
        $subforums = bbp_forum_get_subforums($forum_id);
        if ($subforums) {
            $forum_data['subforums'] = array();
            foreach ($subforums as $subforum) {
                if (bbp_is_forum_public($subforum->ID)) {
                    $forum_data['subforums'][] = bbpas_prepare_forum_data($subforum->ID);
                }
            }
        }
    }
    
    return $forum_data;
}

// === Topic endpoint functions ===

function bbpas_get_topics($request) {
    if (!function_exists('bbp_get_topic_post_type')) {
        return new WP_Error('bbpress_not_active', __('bbPress is not active', 'bbpress-api-server'), array('status' => 500));
    }
    
    $forum_id = $request->get_param('forum_id');
    $per_page = $request->get_param('per_page');
    $page = $request->get_param('page');
    $orderby = $request->get_param('orderby');
    $order = $request->get_param('order');
    
    // Map API orderby parameter to WP_Query parameters
    $orderby_map = array(
        'date' => 'date',
        'title' => 'title',
        'activity' => 'meta_value',
        'popularity' => 'meta_value_num'
    );
    
    $wp_orderby = isset($orderby_map[$orderby]) ? $orderby_map[$orderby] : 'meta_value';
    $meta_key = ($orderby === 'popularity') ? '_bbp_voice_count' : '_bbp_last_active_time';
    
    $args = array(
        'post_type' => bbp_get_topic_post_type(),
        'post_status' => bbp_get_public_status_id(),
        'posts_per_page' => $per_page,
        'paged' => $page,
        'orderby' => $wp_orderby,
        'order' => $order,
    );
    
    // Add meta key for ordering if needed
    if (in_array($orderby, ['activity', 'popularity'])) {
        $args['meta_key'] = $meta_key;
    }
    
    // If forum ID is provided, filter by forum
    if ($forum_id) {
        // Check if forum exists and is public
        if (!bbp_is_forum($forum_id) || !bbp_is_forum_public($forum_id)) {
            return new WP_Error('invalid_forum', __('Invalid or inaccessible forum', 'bbpress-api-server'), array('status' => 404));
        }
        
        $args['meta_query'] = array(
            array(
                'key' => '_bbp_forum_id',
                'value' => $forum_id,
            ),
        );
    }
    
    // Handle sticky topics
    $sticky_topics = array();
    if ($forum_id) {
        $sticky_topics = bbp_get_stickies($forum_id);
    }
    
    $topics_query = new WP_Query($args);
    $topics = array();
    
    // Add sticky topics first if forum_id is provided and we're on the first page
    if ($page == 1 && !empty($sticky_topics) && $forum_id) {
        foreach ($sticky_topics as $sticky_id) {
            if (bbp_is_topic($sticky_id) && bbp_is_topic_published($sticky_id)) {
                $topics[] = bbpas_prepare_topic_data($sticky_id);
            }
        }
    }
    
    // Add regular topics
    if ($topics_query->have_posts()) {
        while ($topics_query->have_posts()) {
            $topics_query->the_post();
            $topic_id = get_the_ID();
            
            // Skip sticky topics as they've already been added
            if ($page == 1 && !empty($sticky_topics) && in_array($topic_id, $sticky_topics)) {
                continue;
            }
            
            $topics[] = bbpas_prepare_topic_data($topic_id);
        }
        wp_reset_postdata();
    }
    
    // Add pagination info to response
    $response = rest_ensure_response($topics);
    $response->header('X-WP-Total', $topics_query->found_posts);
    $response->header('X-WP-TotalPages', $topics_query->max_num_pages);
    
    return $response;
}

function bbpas_get_topic_details($request) {
    $topic_id = $request->get_param('id');
    $with_replies = $request->get_param('with_replies');
    
    if (!bbp_is_topic($topic_id)) {
        return new WP_Error('invalid_topic', __('Invalid topic ID', 'bbpress-api-server'), array('status' => 404));
    }
    
    $topic_data = bbpas_prepare_topic_data($topic_id, true);
    
    // Include replies if requested
    if ($with_replies) {
        $replies_data = bbpas_get_replies(new WP_REST_Request('GET', '/bbpas/v1/replies'));
        $topic_data['replies'] = $replies_data;
    }
    
    return $topic_data;
}

function bbpas_prepare_topic_data($topic_id, $with_content = false) {
    $last_active = get_post_meta($topic_id, '_bbp_last_active_time', true);
    $forum_id = bbp_get_topic_forum_id($topic_id);
    
    $topic_data = array(
        'id' => $topic_id,
        'title' => bbp_get_topic_title($topic_id),
        'permalink' => bbp_get_topic_permalink($topic_id),
        'forum_id' => $forum_id,
        'forum_title' => bbp_get_forum_title($forum_id),
        'forum_permalink' => bbp_get_forum_permalink($forum_id),
        'created' => get_post_time('c', true, $topic_id),
        'last_active' => $last_active,
        'last_active_gmt' => get_gmt_from_date($last_active),
        'last_active_relative' => bbp_get_topic_last_active_time($topic_id),
        'author_id' => bbp_get_topic_author_id($topic_id),
        'author_name' => bbp_get_topic_author_display_name($topic_id),
        'author_avatar' => bbpas_get_author_avatar_url(bbp_get_topic_author_id($topic_id)),
        'reply_count' => bbp_get_topic_reply_count($topic_id),
        'voice_count' => bbp_get_topic_voice_count($topic_id),
        'is_sticky' => bbp_is_topic_sticky($topic_id),
        'is_super_sticky' => bbp_is_topic_super_sticky($topic_id),
        'status' => bbp_get_topic_status($topic_id),
        'excerpt' => bbp_get_topic_excerpt($topic_id, 100),
    );
    
    // Include full content if requested
    if ($with_content) {
        $topic_data['content'] = bbp_get_topic_content($topic_id);
    }
    
    // Get tags if any
    $topic_data['tags'] = array();
    $tags = wp_get_object_terms($topic_id, bbp_get_topic_tag_tax_id());
    if (!empty($tags) && !is_wp_error($tags)) {
        foreach ($tags as $tag) {
            $topic_data['tags'][] = array(
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'permalink' => bbp_get_topic_tag_link($tag->term_id)
            );
        }
    }
    
    // Add last reply info if available
    $last_reply_id = bbp_get_topic_last_reply_id($topic_id);
    if ($last_reply_id && $last_reply_id != $topic_id) {
        $topic_data['last_reply'] = array(
            'id' => $last_reply_id,
            'excerpt' => bbp_get_reply_excerpt($last_reply_id, 55),
            'author_id' => bbp_get_reply_author_id($last_reply_id),
            'author_name' => bbp_get_reply_author_display_name($last_reply_id),
            'author_avatar' => bbpas_get_author_avatar_url(bbp_get_reply_author_id($last_reply_id)),
            'created' => get_post_time('c', true, $last_reply_id),
        );
    }
    
    return $topic_data;
}

// === Reply endpoint functions ===

function bbpas_get_replies($request) {
    if (!function_exists('bbp_get_reply_post_type')) {
        return new WP_Error('bbpress_not_active', __('bbPress is not active', 'bbpress-api-server'), array('status' => 500));
    }
    
    $topic_id = $request->get_param('topic_id');
    $per_page = $request->get_param('per_page');
    $page = $request->get_param('page');
    $order = $request->get_param('order');
    
    if (!bbp_is_topic($topic_id)) {
        return new WP_Error('invalid_topic', __('Invalid topic ID', 'bbpress-api-server'), array('status' => 404));
    }
    
    $args = array(
        'post_type' => bbp_get_reply_post_type(),
        'post_status' => bbp_get_public_status_id(),
        'post_parent' => $topic_id,
        'posts_per_page' => $per_page,
        'paged' => $page,
        'orderby' => 'date',
        'order' => $order,
    );
    
    $replies_query = new WP_Query($args);
    $replies = array();
    
    if ($replies_query->have_posts()) {
        while ($replies_query->have_posts()) {
            $replies_query->the_post();
            $reply_id = get_the_ID();
            
            $replies[] = bbpas_prepare_reply_data($reply_id);
        }
        wp_reset_postdata();
    }
    
    // Add pagination info to response
    $response = rest_ensure_response($replies);
    $response->header('X-WP-Total', $replies_query->found_posts);
    $response->header('X-WP-TotalPages', $replies_query->max_num_pages);
    
    return $response;
}

function bbpas_get_reply_details($request) {
    $reply_id = $request->get_param('id');
    
    if (!bbp_is_reply($reply_id)) {
        return new WP_Error('invalid_reply', __('Invalid reply ID', 'bbpress-api-server'), array('status' => 404));
    }
    
    return bbpas_prepare_reply_data($reply_id, true);
}

function bbpas_prepare_reply_data($reply_id, $with_content = false) {
    $reply_data = array(
        'id' => $reply_id,
        'permalink' => bbp_get_reply_permalink($reply_id),
        'topic_id' => bbp_get_reply_topic_id($reply_id),
        'topic_title' => bbp_get_topic_title(bbp_get_reply_topic_id($reply_id)),
        'forum_id' => bbp_get_reply_forum_id($reply_id),
        'created' => get_post_time('c', true, $reply_id),
        'author_id' => bbp_get_reply_author_id($reply_id),
        'author_name' => bbp_get_reply_author_display_name($reply_id),
        'author_avatar' => bbpas_get_author_avatar_url(bbp_get_reply_author_id($reply_id)),
        'excerpt' => bbp_get_reply_excerpt($reply_id, 100),
    );
    
    // Include full content if requested
    if ($with_content) {
        $reply_data['content'] = bbp_get_reply_content($reply_id);
    }
    
    return $reply_data;
}

// === User endpoint functions ===

function bbpas_get_user_activity($request) {
    $user_id = $request->get_param('id');
    $type = $request->get_param('type');
    $per_page = $request->get_param('per_page');
    
    if (!get_userdata($user_id)) {
        return new WP_Error('invalid_user', __('Invalid user ID', 'bbpress-api-server'), array('status' => 404));
    }
    
    $activity = array();
    
    // Get user's topics if requested
    if ($type === 'topics' || $type === 'all') {
        $topics = bbpas_get_user_topics($user_id, $per_page);
        if ($type === 'topics') {
            return $topics;
        } else {
            $activity['topics'] = $topics;
        }
    }
    
    // Get user's replies if requested
    if ($type === 'replies' || $type === 'all') {
        $replies = bbpas_get_user_replies($user_id, $per_page);
        if ($type === 'replies') {
            return $replies;
        } else {
            $activity['replies'] = $replies;
        }
    }
    
    // If type is 'all', we're returning both
    return $activity;
}

function bbpas_get_user_topics($user_id, $per_page = 10) {
    $args = array(
        'author' => $user_id,
        'post_type' => bbp_get_topic_post_type(),
        'post_status' => array(bbp_get_public_status_id()),
        'posts_per_page' => $per_page,
        'orderby' => 'date',
        'order' => 'DESC',
    );
    
    $query = new WP_Query($args);
    $topics = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $topics[] = bbpas_prepare_topic_data(get_the_ID());
        }
        wp_reset_postdata();
    }
    
    return $topics;
}

function bbpas_get_user_replies($user_id, $per_page = 10) {
    $args = array(
        'author' => $user_id,
        'post_type' => bbp_get_reply_post_type(),
        'post_status' => array(bbp_get_public_status_id()),
        'posts_per_page' => $per_page,
        'orderby' => 'date',
        'order' => 'DESC',
    );
    
    $query = new WP_Query($args);
    $replies = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $replies[] = bbpas_prepare_reply_data(get_the_ID());
        }
        wp_reset_postdata();
    }
    
    return $replies;
}

// === Search endpoint function ===

function bbpas_search($request) {
    $query = $request->get_param('query');
    $type = $request->get_param('type');
    $per_page = $request->get_param('per_page');
    $page = $request->get_param('page');
    
    if (empty($query)) {
        return new WP_Error('empty_search', __('Search query cannot be empty', 'bbpress-api-server'), array('status' => 400));
    }
    
    // Determine post types to search
    $post_types = array();
    switch ($type) {
        case 'forum':
            $post_types[] = bbp_get_forum_post_type();
            break;
        case 'topic':
            $post_types[] = bbp_get_topic_post_type();
            break;
        case 'reply':
            $post_types[] = bbp_get_reply_post_type();
            break;
        case 'all':
        default:
            $post_types = array(
                bbp_get_forum_post_type(),
                bbp_get_topic_post_type(),
                bbp_get_reply_post_type()
            );
            break;
    }
    
    $args = array(
        'post_type' => $post_types,
        'post_status' => 'publish',
        's' => $query,
        'posts_per_page' => $per_page,
        'paged' => $page,
        'orderby' => 'relevance',
    );
    
    $search_query = new WP_Query($args);
    $results = array();
    
    if ($search_query->have_posts()) {
        while ($search_query->have_posts()) {
            $search_query->the_post();
            $post_id = get_the_ID();
            $post_type = get_post_type($post_id);
            
            $result = array(
                'id' => $post_id,
                'type' => $post_type,
                'title' => get_the_title($post_id),
                'permalink' => get_permalink($post_id),
                'date' => get_post_time('c', true, $post_id),
                'excerpt' => get_the_excerpt($post_id),
            );
            
            // Add type-specific data
            if ($post_type === bbp_get_forum_post_type()) {
                $result['topic_count'] = bbp_get_forum_topic_count($post_id);
                $result['reply_count'] = bbp_get_forum_reply_count($post_id);
            } elseif ($post_type === bbp_get_topic_post_type()) {
                $result['forum_id'] = bbp_get_topic_forum_id($post_id);
                $result['forum_title'] = bbp_get_forum_title($result['forum_id']);
                $result['reply_count'] = bbp_get_topic_reply_count($post_id);
            } elseif ($post_type === bbp_get_reply_post_type()) {
                $result['topic_id'] = bbp_get_reply_topic_id($post_id);
                $result['topic_title'] = bbp_get_topic_title($result['topic_id']);
                $result['forum_id'] = bbp_get_reply_forum_id($post_id);
            }
            
            $results[] = $result;
        }
        wp_reset_postdata();
    }
    
    // Add pagination info to response
    $response = rest_ensure_response($results);
    $response->header('X-WP-Total', $search_query->found_posts);
    $response->header('X-WP-TotalPages', $search_query->max_num_pages);
    
    return $response;
}

// === Stats endpoint function ===

function bbpas_get_stats() {
    return array(
        'forums' => array(
            'total' => bbpas_count_total_forums(),
            'active' => bbpas_count_active_forums(),
        ),
        'topics' => array(
            'total' => bbpas_count_total_topics(),
            'open' => bbpas_count_open_topics(),
            'closed' => bbpas_count_closed_topics(),
        ),
        'replies' => array(
            'total' => bbpas_count_total_replies(),
        ),
        'users' => array(
            'total' => bbpas_count_total_users(),
        ),
        'last_24_hours' => array(
            'topics' => bbpas_count_topics_last_24_hours(),
            'replies' => bbpas_count_replies_last_24_hours(),
        ),
        'last_7_days' => array(
            'topics' => bbpas_count_topics_last_7_days(),
            'replies' => bbpas_count_replies_last_7_days(),
        ),
    );
}

// Helper functions for stats

function bbpas_count_total_forums() {
    $args = array(
        'post_type' => bbp_get_forum_post_type(),
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
    );
    $query = new WP_Query($args);
    return $query->found_posts;
}

function bbpas_count_active_forums() {
    // Active forums have at least one topic
    $args = array(
        'post_type' => bbp_get_forum_post_type(),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_bbp_topic_count',
                'value' => 0,
                'compare' => '>',
            ),
        ),
        'posts_per_page' => 1,
        'fields' => 'ids',
    );
    $query = new WP_Query($args);
    return $query->found_posts;
}

function bbpas_count_total_topics() {
    $args = array(
        'post_type' => bbp_get_topic_post_type(),
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
    );
    $query = new WP_Query($args);
    return $query->found_posts;
}

function bbpas_count_open_topics() {
    $args = array(
        'post_type' => bbp_get_topic_post_type(),
        'post_status' => bbp_get_public_status_id(),
        'meta_query' => array(
            array(
                'key' => '_bbp_status',
                'value' => 'closed',
                'compare' => '!=',
            ),
        ),
        'posts_per_page' => 1,
        'fields' => 'ids',
    );
    $query = new WP_Query($args);
    return $query->found_posts;
}

function bbpas_count_closed_topics() {
    $args = array(
        'post_type' => bbp_get_topic_post_type(),
        'post_status' => bbp_get_public_status_id(),
        'meta_query' => array(
            array(
                'key' => '_bbp_status',
                'value' => 'closed',
            ),
        ),
        'posts_per_page' => 1,
        'fields' => 'ids',
    );
    $query = new WP_Query($args);
    return $query->found_posts;
}

function bbpas_count_total_replies() {
    $args = array(
        'post_type' => bbp_get_reply_post_type(),
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
    );
    $query = new WP_Query($args);
    return $query->found_posts;
}

function bbpas_count_total_users() {
    $count = count_users();
    return $count['total_users'];
}

function bbpas_count_topics_last_24_hours() {
    $args = array(
        'post_type' => bbp_get_topic_post_type(),
        'post_status' => 'publish',
        'date_query' => array(
            array(
                'after' => '1 day ago',
            ),
        ),
        'posts_per_page' => 1,
        'fields' => 'ids',
    );
    $query = new WP_Query($args);
    return $query->found_posts;
}

function bbpas_count_replies_last_24_hours() {
    $args = array(
        'post_type' => bbp_get_reply_post_type(),
        'post_status' => 'publish',
        'date_query' => array(
            array(
                'after' => '1 day ago',
            ),
        ),
        'posts_per_page' => 1,
        'fields' => 'ids',
    );
    $query = new WP_Query($args);
    return $query->found_posts;
}

function bbpas_count_topics_last_7_days() {
    $args = array(
        'post_type' => bbp_get_topic_post_type(),
        'post_status' => 'publish',
        'date_query' => array(
            array(
                'after' => '7 days ago',
            ),
        ),
        'posts_per_page' => 1,
        'fields' => 'ids',
    );
    $query = new WP_Query($args);
    return $query->found_posts;
}

function bbpas_count_replies_last_7_days() {
    $args = array(
        'post_type' => bbp_get_reply_post_type(),
        'post_status' => 'publish',
        'date_query' => array(
            array(
                'after' => '7 days ago',
            ),
        ),
        'posts_per_page' => 1,
        'fields' => 'ids',
    );
    $query = new WP_Query($args);
    return $query->found_posts;
}

// Helper functions

function bbpas_get_author_avatar_url($user_id, $size = 96) {
    return get_avatar_url($user_id, array('size' => $size));
}

// === Legacy compatibility functions ===

function wbapi_test_connection() {
    $response = bbpas_test_connection();
    
    // Add a deprecation notice
    $response['deprecated'] = true;
    $response['notice'] = __('This endpoint is deprecated. Please use the new endpoint: /bbpas/v1/test', 'bbpress-api-server');
    
    return $response;
}

function wbapi_get_forums() {
    $response = bbpas_get_forums(new WP_REST_Request('GET', '/' . BBPAS_API_NAMESPACE . '/forums'));
    
    // Add a deprecation notice
    if (is_array($response->get_data())) {
        $data = $response->get_data();
        $data['deprecated'] = true;
        $data['notice'] = __('This endpoint is deprecated. Please use the new endpoint: /bbpas/v1/forums', 'bbpress-api-server');
        $response->set_data($data);
    }
    
    return $response;
}

function wbapi_get_topics($request) {
    $forum_id = $request->get_param('forum_id');
    $count = $request->get_param('count');
    
    // Create a new request object for the new API
    $new_request = new WP_REST_Request('GET', '/' . BBPAS_API_NAMESPACE . '/topics');
    $new_request->set_param('forum_id', $forum_id);
    $new_request->set_param('per_page', $count);
    
    $response = bbpas_get_topics($new_request);
    
    // Add a deprecation notice
    if (is_array($response->get_data())) {
        $data = $response->get_data();
        $data['deprecated'] = true;
        $data['notice'] = __('This endpoint is deprecated. Please use the new endpoint: /bbpas/v1/topics', 'bbpress-api-server');
        $response->set_data($data);
    }
    
    return $response;
}

function wbapi_get_forum_details($request) {
    $forum_id = $request->get_param('id');
    
    // Create a new request object for the new API
    $new_request = new WP_REST_Request('GET', '/' . BBPAS_API_NAMESPACE . '/forum/' . $forum_id);
    
    $response = bbpas_get_forum_details($new_request);
    
    // Add a deprecation notice if this is a valid response
    if (!is_wp_error($response)) {
        $response['deprecated'] = true;
        $response['notice'] = __('This endpoint is deprecated. Please use the new endpoint: /bbpas/v1/forum/{id}', 'bbpress-api-server');
    }
    
    return $response;
}

// Initialize the plugin

// Add action to check if bbPress is active and register REST routes
add_action('plugins_loaded', function() {
    if (bbpas_check_required_plugins()) {
        add_action('rest_api_init', 'bbpas_register_routes');
    }
});

// Add admin menu
add_action('admin_menu', function() {
    if (!bbpas_check_required_plugins()) {
        return;
    }
    
    add_submenu_page(
        'options-general.php',
        __('bbPress API Server', 'bbpress-api-server'),
        __('bbPress API', 'bbpress-api-server'),
        'manage_options',
        'bbpress-api-server',
        'bbpas_admin_page'
    );
});

// Admin page
function bbpas_admin_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('bbPress API Server', 'bbpress-api-server'); ?></h1>
        
        <div class="card">
            <h2><?php _e('API Information', 'bbpress-api-server'); ?></h2>
            <p><?php _e('This plugin provides REST API endpoints for bbPress forums, topics, and replies.', 'bbpress-api-server'); ?></p>
            <p><strong><?php _e('API Namespace:', 'bbpress-api-server'); ?></strong> <?php echo BBPAS_API_NAMESPACE; ?></p>
            <p><strong><?php _e('Plugin Version:', 'bbpress-api-server'); ?></strong> <?php echo BBPAS_VERSION; ?></p>
            <p><strong><?php _e('bbPress Version:', 'bbpress-api-server'); ?></strong> <?php echo bbp_get_version(); ?></p>
        </div>
        
        <div class="card">
            <h2><?php _e('Available Endpoints', 'bbpress-api-server'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Endpoint', 'bbpress-api-server'); ?></th>
                        <th><?php _e('Method', 'bbpress-api-server'); ?></th>
                        <th><?php _e('Description', 'bbpress-api-server'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/test</code></td>
                        <td>GET</td>
                        <td><?php _e('Test if the API is working', 'bbpress-api-server'); ?></td>
                    </tr>
                    <tr>
                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/forums</code></td>
                        <td>GET</td>
                        <td><?php _e('Get a list of forums', 'bbpress-api-server'); ?></td>
                    </tr>
                    <tr>
                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/forum/{id}</code></td>
                        <td>GET</td>
                        <td><?php _e('Get details of a specific forum', 'bbpress-api-server'); ?></td>
                    </tr>
                    <tr>
                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/topics</code></td>
                        <td>GET</td>
                        <td><?php _e('Get a list of topics, optionally filtered by forum', 'bbpress-api-server'); ?></td>
                    </tr>
                    <tr>
                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/topic/{id}</code></td>
                        <td>GET</td>
                        <td><?php _e('Get details of a specific topic', 'bbpress-api-server'); ?></td>
                    </tr>
                    <tr>
                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/replies</code></td>
                        <td>GET</td>
                        <td><?php _e('Get replies for a topic', 'bbpress-api-server'); ?></td>
                    </tr>
                    <tr>
                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/reply/{id}</code></td>
                        <td>GET</td>
                        <td><?php _e('Get details of a specific reply', 'bbpress-api-server'); ?></td>
                    </tr>
                    <tr>
                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/user/{id}/activity</code></td>
                        <td>GET</td>
                        <td><?php _e('Get forum activity for a specific user', 'bbpress-api-server'); ?></td>
                    </tr>
                    <tr>
                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/search</code></td>
                        <td>GET</td>
                        <td><?php _e('Search forums, topics, and replies', 'bbpress-api-server'); ?></td>
                    </tr>
                    <tr>
                        <td><code>/<?php echo BBPAS_API_NAMESPACE; ?>/stats</code></td>
                        <td>GET</td>
                        <td><?php _e('Get forum statistics', 'bbpress-api-server'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
                <div class="card">
    <h2><?php _e('Instructions', 'bbpress-api-server'); ?></h2>
    <ol>
        <li><?php _e('Install this plugin on a WordPress site with bbPress activated.', 'bbpress-api-server'); ?></li>
        <li><?php _e('Ensure that pretty permalinks are enabled (recommended to use "Post name" format).', 'bbpress-api-server'); ?></li>
        <li><?php _e('Use the API in the following ways:', 'bbpress-api-server'); ?>
            <ul>
                <li><?php _e('Directly call REST API endpoints.', 'bbpress-api-server'); ?></li>
                <li><?php _e('Use the official SDK (available on our website).', 'bbpress-api-server'); ?></li>
                <li><?php _e('Integrate with third-party services:', 'bbpress-api-server'); ?>
                    <br>- WooCommerce Forum Integration Plugin
                    <br>- LearnDash Course Community Plugin
                    <br>- WeChat Mini Program Integration Solution
                </li>
            </ul>
        </li>
        <li><?php _e('Advanced configuration recommendations:', 'bbpress-api-server'); ?>
            <ul>
                <li><?php _e('Enable caching in load-balanced environments.', 'bbpress-api-server'); ?></li>
                <li><?php _e('Use JWT for authentication (requires extension plugin).', 'bbpress-api-server'); ?></li>
                <li><?php _e('Customize response fields using the `bbpas_response_data` filter.', 'bbpress-api-server'); ?></li>
            </ul>
        </li>
    </ol>
    <p><?php _e('For complete developer documentation, visit:', 'bbpress-api-server'); ?> 
        <a href="https://wpsdk.com/?p=1076" target="_blank">
            https://wpsdk.com/?p=1076
        </a>
    </p>
</div>
    <?php
}

// Activation hook
register_activation_hook(__FILE__, function() {
    // Check if bbPress is active
    if (!class_exists('bbPress')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('bbPress API Server requires bbPress to be installed and activated.', 'bbpress-api-server'));
    }
    
    // Flush rewrite rules on activation
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Flush rewrite rules on deactivation
    flush_rewrite_rules();
});
