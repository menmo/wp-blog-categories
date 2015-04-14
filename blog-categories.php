<?php

/**
 * Plugin Name: Blog Categories
 * Plugin URI: https://github.com/menmo/wp-blog-categories
 * Description: A plugin for categorizing blogs.
 * Version: 1.0
 * Author: Menmo AB
 * Author URI: http://www.menmo.se
 * License: GPL2
 */

require_once(plugin_dir_path(__FILE__) . 'db/blog-cats.php');
require_once(plugin_dir_path(__FILE__) . 'db/blog-cat-relationships.php');
require_once(plugin_dir_path(__FILE__) . 'blog-cat-list.php');

define("br", "<br />");

class Blog_Categories_Plugin {

    function __construct() {

        defined('ABSPATH') or die();

        register_activation_hook( __FILE__, array($this, 'activate') );

        add_action('network_admin_menu', array($this, 'add_menu') );
        //add_action('wpmu_new_blog', array($this, 'new_blog') );
        add_action('admin_menu', array($this, 'add_options_page') );
    }

    public function activate(){
        $this->die_if_not_superadmin();
	    Blog_Cats_DB::create_table();
	    Blog_Cat_Relationships_DB::create_table();
    }

    public function add_menu() {
        $page_title = __('Categories');
        $menu_title = __('Categories');
        $capability = 'manage_sites';
        $menu_slug = 'blog-cats';
        $function = array($this, 'blog_cats_page');
        add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, 'dashicons-category' );
    }

    public function blog_cats_page() {
        $this->die_if_not_superadmin();
	    include(plugin_dir_path(__FILE__) . 'pages/manage-blog-cats.php');
    }

    function new_blog() {
        exit( wp_redirect( admin_url( 'network/index.php?page=blog-cats' ) ) );
    }

    function add_options_page() {
        $page_title = __('Blog Categories');
        $menu_title = __('Categories');
        $capability = 'manage_sites';
        $menu_slug = 'blog-cats-select';
        $function = array($this, 'manage_blog_cats_page');
        add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function);
    }

    public function manage_blog_cats_page() {
        $this->die_if_not_superadmin();
        include(plugin_dir_path(__FILE__) . 'pages/manage-blog.php');
    }

    function die_if_not_superadmin() {
        if( !is_super_admin() ) {
            wp_die( __('You do not have permission to access this page.') );
        }
    }
}

new Blog_Categories_Plugin();

// Public methods

function blog_categories_get_categories($args = array()) {
    return Blog_Cats_DB::get_list($args);
}

function blog_categories_get_latest_posts($cat_ID, $avatar_size = 48) {
    $blogs = Blog_Cat_Relationships_DB::get_blog_list($cat_ID);
    $result = array();
    foreach($blogs as $blog) {
        $details = get_blog_details($blog);

        if($details && $details->archived == 0 && $details->deleted == 0) {
            switch_to_blog($blog);
            $posts = get_posts(array(
                'posts_per_page'   => 1
            ));

            if(!empty($posts)) {
                $result[] = array(
                    'blog' => $details,
                    'post' => $posts[0],
                    'permalink' => get_permalink($posts[0]->ID),
                    'avatar' => get_avatar( $posts[0]->post_author, $avatar_size )
                );
            }
            restore_current_blog();
        }
    };

    usort($result, function($a, $b) {
        return strcmp($b['post']->post_date,  $a['post']->post_date);
    });

    return $result;
}

?>