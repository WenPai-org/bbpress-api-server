<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Register all REST API routes
function bbpas_register_routes() {
    // Test endpoint
    register_rest_route(BBPAS_API_NAMESPACE, '/test', array(
        'methods' => 'GET',
        'callback' => 'bbpas_test_connection',
        'permission_callback' => '__return_true',
    ));

    // Forum endpoints
    register_rest_route(BBPAS_API_NAMESPACE, '/forums', array(
        'methods' => 'GET',
        'callback' => 'bbpas_get_forums',
        'permission_callback' => '__return_true',
        'args' => array(
            'parent' => array('default' => null, 'validate_callback' => 'is_numeric'),
            'per_page' => array('default' => -1, 'validate_callback' => function($param) { return is_numeric($param) && ($param == -1 || ($param > 0 && $param <= 100)); }),
            'page' => array('default' => 1, 'validate_callback' => function($param) { return is_numeric($param) && $param > 0; }),
        ),
    ));

    register_rest_route(BBPAS_API_NAMESPACE, '/forum/(?P<id>\d+)', array(
        array(
            'methods' => 'GET',
            'callback' => 'bbpas_get_forum_details',
            'permission_callback' => '__return_true',
            'args' => array('id' => array('required' => true)),
        ),
        array(
            'methods' => 'POST',
            'callback' => 'bbpas_create_topic_in_forum',
            'permission_callback' => 'bbpas_create_permission_check',
            'args' => array(
                'id' => array('required' => true),
                'title' => array('required' => true, 'type' => 'string'),
                'content' => array('required' => true, 'type' => 'string'),
                'email' => array('required' => true, 'type' => 'string'),
            ),
        ),
    ));

    // Topic endpoints
    register_rest_route(BBPAS_API_NAMESPACE, '/topics', array(
        'methods' => 'GET',
        'callback' => 'bbpas_get_topics',
        'permission_callback' => '__return_true',
        'args' => array(
            'forum_id' => array('validate_callback' => 'is_numeric'),
            'per_page' => array('default' => 10, 'validate_callback' => function($param) { return is_numeric($param) && $param > 0 && $param <= 100; }),
            'page' => array('default' => 1, 'validate_callback' => function($param) { return is_numeric($param) && $param > 0; }),
            'orderby' => array('default' => 'activity', 'validate_callback' => function($param) { return in_array($param, ['date', 'title', 'activity', 'popularity']); }),
            'order' => array('default' => 'DESC', 'validate_callback' => function($param) { return in_array(strtoupper($param), ['ASC', 'DESC']); }),
        ),
    ));

    register_rest_route(BBPAS_API_NAMESPACE, '/topic/(?P<id>\d+)', array(
        array(
            'methods' => 'GET',
            'callback' => 'bbpas_get_topic_details',
            'permission_callback' => '__return_true',
            'args' => array('id' => array('required' => true), 'with_replies' => array('default' => false, 'validate_callback' => 'rest_is_boolean')),
        ),
        array(
            'methods' => 'POST',
            'callback' => 'bbpas_create_reply_to_topic',
            'permission_callback' => 'bbpas_create_permission_check',
            'args' => array(
                'id' => array('required' => true),
                'content' => array('required' => true, 'type' => 'string'),
                'email' => array('required' => true, 'type' => 'string'),
            ),
        ),
    ));

    // Reply endpoints
    register_rest_route(BBPAS_API_NAMESPACE, '/replies', array(
        'methods' => 'GET',
        'callback' => 'bbpas_get_replies',
        'permission_callback' => '__return_true',
        'args' => array(
            'topic_id' => array('required' => true, 'validate_callback' => 'is_numeric'),
            'per_page' => array('default' => 20, 'validate_callback' => function($param) { return is_numeric($param) && $param > 0 && $param <= 100; }),
            'page' => array('default' => 1, 'validate_callback' => function($param) { return is_numeric($param) && $param > 0; }),
            'order' => array('default' => 'ASC', 'validate_callback' => function($param) { return in_array(strtoupper($param), ['ASC', 'DESC']); }),
        ),
    ));

    register_rest_route(BBPAS_API_NAMESPACE, '/reply/(?P<id>\d+)', array(
        array(
            'methods' => 'GET',
            'callback' => 'bbpas_get_reply_details',
            'permission_callback' => '__return_true',
            'args' => array('id' => array('required' => true)),
        ),
        array(
            'methods' => 'POST',
            'callback' => 'bbpas_create_reply_to_reply',
            'permission_callback' => 'bbpas_create_permission_check',
            'args' => array(
                'id' => array('required' => true),
                'content' => array('required' => true, 'type' => 'string'),
                'email' => array('required' => true, 'type' => 'string'),
            ),
        ),
    ));

    // Topic tags endpoint
    register_rest_route(BBPAS_API_NAMESPACE, '/topic-tags', array(
        'methods' => 'GET',
        'callback' => 'bbpas_get_topic_tags',
        'permission_callback' => '__return_true',
    ));

    // Stats endpoint
    register_rest_route(BBPAS_API_NAMESPACE, '/stats', array(
        'methods' => 'GET',
        'callback' => 'bbpas_get_stats',
        'permission_callback' => '__return_true',
    ));
}

// Test connection
function bbpas_test_connection() {
    if (!function_exists('bbp_get_version')) {
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

// Permission check for creation
function bbpas_create_permission_check() {
    return current_user_can('publish_topics') || current_user_can('publish_replies');
}

// Get all forums
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
    if ($parent !== null) {
        $args['post_parent'] = $parent;
    }
    $forums_query = new WP_Query($args);
    $forums = array();
    if ($forums_query->have_posts()) {
        while ($forums_query->have_posts()) {
            $forums_query->the_post();
            $forum_id = get_the_ID();
            if (!bbp_is_forum_public($forum_id)) {
                continue;
            }
            $forums[] = bbpas_prepare_forum_data($forum_id);
        }
        wp_reset_postdata();
    }
    $response = rest_ensure_response($forums);
    $response->header('X-WP-Total', $forums_query->found_posts);
    $response->header('X-WP-TotalPages', $forums_query->max_num_pages);
    return $response;
}

// Get single forum details
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

// Get all topics
function bbpas_get_topics($request) {
    if (!function_exists('bbp_get_topic_post_type')) {
        return new WP_Error('bbpress_not_active', __('bbPress is not active', 'bbpress-api-server'), array('status' => 500));
    }
    $forum_id = $request->get_param('forum_id');
    $per_page = $request->get_param('per_page');
    $page = $request->get_param('page');
    $orderby = $request->get_param('orderby');
    $order = $request->get_param('order');
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
    if (in_array($orderby, ['activity', 'popularity'])) {
        $args['meta_key'] = $meta_key;
    }
    if ($forum_id) {
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
    $sticky_topics = array();
    if ($forum_id) {
        $sticky_topics = bbp_get_stickies($forum_id);
    }
    $topics_query = new WP_Query($args);
    $topics = array();
    if ($page == 1 && !empty($sticky_topics) && $forum_id) {
        foreach ($sticky_topics as $sticky_id) {
            if (bbp_is_topic($sticky_id) && bbp_is_topic_published($sticky_id)) {
                $topics[] = bbpas_prepare_topic_data($sticky_id);
            }
        }
    }
    if ($topics_query->have_posts()) {
        while ($topics_query->have_posts()) {
            $topics_query->the_post();
            $topic_id = get_the_ID();
            if ($page == 1 && !empty($sticky_topics) && in_array($topic_id, $sticky_topics)) {
                continue;
            }
            $topics[] = bbpas_prepare_topic_data($topic_id);
        }
        wp_reset_postdata();
    }
    $response = rest_ensure_response($topics);
    $response->header('X-WP-Total', $topics_query->found_posts);
    $response->header('X-WP-TotalPages', $topics_query->max_num_pages);
    return $response;
}

// Get single topic details
function bbpas_get_topic_details($request) {
    $topic_id = $request->get_param('id');
    $with_replies = $request->get_param('with_replies');
    if (!bbp_is_topic($topic_id)) {
        return new WP_Error('invalid_topic', __('Invalid topic ID', 'bbpress-api-server'), array('status' => 404));
    }
    $topic_data = bbpas_prepare_topic_data($topic_id, true);
    if ($with_replies) {
        $replies_request = new WP_REST_Request('GET', '/' . BBPAS_API_NAMESPACE . '/replies');
        $replies_request->set_param('topic_id', $topic_id);
        $topic_data['replies'] = bbpas_get_replies($replies_request);
    }
    return $topic_data;
}

// Get replies for a topic
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
    $response = rest_ensure_response($replies);
    $response->header('X-WP-Total', $replies_query->found_posts);
    $response->header('X-WP-TotalPages', $replies_query->max_num_pages);
    return $response;
}

// Get single reply details
function bbpas_get_reply_details($request) {
    $reply_id = $request->get_param('id');
    if (!bbp_is_reply($reply_id)) {
        return new WP_Error('invalid_reply', __('Invalid reply ID', 'bbpress-api-server'), array('status' => 404));
    }
    return bbpas_prepare_reply_data($reply_id, true);
}

// Create new topic in forum
function bbpas_create_topic_in_forum($request) {
    $forum_id = $request['id'];
    if (!bbp_is_forum($forum_id)) {
        return new WP_Error('invalid_forum', __('Invalid forum ID', 'bbpress-api-server'), array('status' => 404));
    }
    if (bbp_is_forum_category($forum_id)) {
        return new WP_Error('forum_category', __('Cannot create topics in a category', 'bbpress-api-server'), array('status' => 400));
    }
    $title = sanitize_text_field($request['title']);
    $content = wp_kses_post($request['content']);
    $email = sanitize_email($request['email']);
    $user = get_user_by('email', $email);
    if (!$user) {
        return new WP_Error('invalid_user', __('Invalid email or user not found', 'bbpress-api-server'), array('status' => 403));
    }
    $topic_id = bbp_insert_topic(
        array(
            'post_parent' => $forum_id,
            'post_title' => $title,
            'post_content' => $content,
            'post_author' => $user->ID,
        ),
        array('forum_id' => $forum_id)
    );
    if (is_wp_error($topic_id)) {
        return $topic_id;
    }
    return array(
        'id' => $topic_id,
        'forum_id' => $forum_id,
        'author_id' => $user->ID,
        'message' => __('Topic created successfully', 'bbpress-api-server'),
    );
}

// Create reply to topic
function bbpas_create_reply_to_topic($request) {
    $topic_id = $request['id'];
    if (!bbp_is_topic($topic_id)) {
        return new WP_Error('invalid_topic', __('Invalid topic ID', 'bbpress-api-server'), array('status' => 404));
    }
    $content = wp_kses_post($request['content']);
    $email = sanitize_email($request['email']);
    $user = get_user_by('email', $email);
    if (!$user) {
        return new WP_Error('invalid_user', __('Invalid email or user not found', 'bbpress-api-server'), array('status' => 403));
    }
    $forum_id = bbp_get_topic_forum_id($topic_id);
    $title = 'RE: ' . bbp_get_topic_title($topic_id);
    $reply_id = bbp_insert_reply(
        array(
            'post_parent' => $topic_id,
            'post_title' => $title,
            'post_content' => $content,
            'post_author' => $user->ID,
        ),
        array(
            'forum_id' => $forum_id,
            'topic_id' => $topic_id,
        )
    );
    if (is_wp_error($reply_id)) {
        return $reply_id;
    }
    return array(
        'id' => $reply_id,
        'topic_id' => $topic_id,
        'forum_id' => $forum_id,
        'author_id' => $user->ID,
        'message' => __('Reply created successfully', 'bbpress-api-server'),
    );
}

// Create reply to reply
function bbpas_create_reply_to_reply($request) {
    $reply_id = $request['id'];
    if (!bbp_is_reply($reply_id)) {
        return new WP_Error('invalid_reply', __('Invalid reply ID', 'bbpress-api-server'), array('status' => 404));
    }
    $content = wp_kses_post($request['content']);
    $email = sanitize_email($request['email']);
    $user = get_user_by('email', $email);
    if (!$user) {
        return new WP_Error('invalid_user', __('Invalid email or user not found', 'bbpress-api-server'), array('status' => 403));
    }
    $topic_id = bbp_get_reply_topic_id($reply_id);
    $forum_id = bbp_get_topic_forum_id($topic_id);
    $title = bbp_get_reply_title($reply_id);
    $new_reply_id = bbp_insert_reply(
        array(
            'post_parent' => $topic_id,
            'post_title' => $title,
            'post_content' => $content,
            'post_author' => $user->ID,
        ),
        array(
            'forum_id' => $forum_id,
            'topic_id' => $topic_id,
            'reply_to' => $reply_id,
        )
    );
    if (is_wp_error($new_reply_id)) {
        return $new_reply_id;
    }
    return array(
        'id' => $new_reply_id,
        'reply_to_id' => $reply_id,
        'topic_id' => $topic_id,
        'forum_id' => $forum_id,
        'author_id' => $user->ID,
        'message' => __('Reply created successfully', 'bbpress-api-server'),
    );
}

// Get topic tags
function bbpas_get_topic_tags() {
    $tags = get_terms(array(
        'taxonomy' => 'topic-tag',
        'orderby' => 'count',
        'order' => 'DESC',
    ));
    return $tags ? $tags : null;
}

// Get statistics
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
