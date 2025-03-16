<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Prepare forum data
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

// Prepare topic data
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
    if ($with_content) {
        $topic_data['content'] = bbp_get_topic_content($topic_id);
    }
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

// Prepare reply data
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
    if ($with_content) {
        $reply_data['content'] = bbp_get_reply_content($reply_id);
    }
    return $reply_data;
}

// Get author avatar URL
function bbpas_get_author_avatar_url($user_id, $size = 96) {
    return get_avatar_url($user_id, array('size' => $size));
}

// Count total forums
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

// Count active forums
function bbpas_count_active_forums() {
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

// Count total topics
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

// Count open topics
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

// Count closed topics
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

// Count total replies
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

// Count total users
function bbpas_count_total_users() {
    $count = count_users();
    return $count['total_users'];
}

// Count topics in last 24 hours
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

// Count replies in last 24 hours
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

// Count topics in last 7 days
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

// Count replies in last 7 days
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
