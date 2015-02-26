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
        add_action('wpmu_new_blog', array($this, 'new_blog') );
        add_action('admin_menu', array($this, 'add_options_page') );
    }

    public function activate(){
        $this->die_if_not_superadmin();
	    Blog_Cats::create_table();
	    Blog_Cat_Relationships::create_table();
    }

    public function add_menu() {
        $page_title = __('Categories');
        $menu_title = __('Categories');
        $capability = 'manage_sites';
        $menu_slug = 'blog-cats';
        $function = array($this, 'blog_cats_page');
        add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, 'dashicons-category' );
    }

	/**
	 * Show the main page for managing blog categories
	 */
    public function blog_cats_page() {
        $this->die_if_not_superadmin();
	    include(plugin_dir_path(__FILE__) . 'pages/blog-cats.php');
    }

    /**
     *
     *   Redirect to settings page after site creation
     *
     **/

    function new_blog_categories() {
        exit( wp_redirect( admin_url( 'network/index.php?page=rlcb-blog-tags-submenu' ) ) );
    }

    /**
     *
     *   Add categorize option page
     *
     **/

    function add_options_page() {
        $page_title = 'Kategorisera blogg';
        $menu_title = 'Kategorisera blogg';
        $capability = 'manage_sites';
        $menu_slug = 'blog-cats-options';
        $function = array($this, 'add_options_page_callback');
        add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function);
    }

    /*
    *
    *   Helper functions
    *
    */

    function die_if_not_superadmin() {
        if( is_super_admin() == false ) {
            wp_die( __('You do not have permission to access this page.') );
        }
    }


}

new Blog_Categories_Plugin();
?>