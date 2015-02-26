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

    /**
     *
     *   Callback function for option page
     *
     **/


    function add_options_page_callback() {
        $this->die_if_not_superadmin();

        if ( isset( $_POST['rlcb_action'] ) ) {
            $action = strtolower( $_POST['rlcb_action'] );
        } else {
            $action = 'show_option_page';
        }

        switch ($action) {
            case 'uppdatera blogg' :
                update_blog();
                break;
            default:
                break;

        }

        ?>

        <div class="wrap">
            <h2>Kategorisera blogg</h2>
        </div>

        <?php

        rlcb_manage_blog();
    }

    /**
     *
     *    Functions for updating categories and blogs.
     *
     **/


    function update_blogs() {
        global $wpdb;

        debug("Updating blogs .." . br);

        debug("Checking something" . br);
        if (! wp_verify_nonce($_POST['_wpnonce'], 'rlcb-blogs') ) {
            wp_die('Security check');
        }

        $tax = $wpdb->base_prefix . "rlcb_blog_tax";
        $groups = $wpdb->get_results("SELECT * FROM $tax WHERE 1;");
        $relationships = $wpdb->base_prefix . "rlcb_blog_tax_relationships";
        $blogs = wp_get_sites(array('limit' => 1000));
        $insert = "INSERT INTO $relationships (relationship_id , tax_id , blog_id ) VALUES";
        $first_new = false;
        $first_old = false;

        foreach($groups as $group){
            foreach($blogs as $blog){
                $new = 'new_'.$blog['blog_id'].'_'.$group->tax_id;

                if (isset($_POST[$new])){
                    if ($first_new) {
                        $insert .= ',';
                    }
                    $insert .='(null, "'.$group->tax_id.'", "'.$blog['blog_id'].'"  )';
                    $first_new = true;
                }

                $set = 'set_'.$blog['blog_id'].'_'.$group->tax_id;

                if (!isset($_POST[$set])){
                    $term_id = $wpdb->get_var("SELECT relationship_id FROM $relationships WHERE tax_id = ".$group->tax_id." AND blog_id = ".$blog['blog_id']." LIMIT 1 ");
                    $wpdb->query("DELETE FROM $relationships WHERE relationship_id = $term_id");
                }

            }
        }

        $sucinsert = $wpdb->query($insert.';');
        echo '<div id="message" class="updated fade"><p>Bloggarna har uppdaterats!</p></div>';
    }

    function update_blog() {
        global $wpdb, $blog_id;

        debug("Updating blog .." . br);

        debug("Checking something" . br);
        if (! wp_verify_nonce($_POST['_wpnonce'], 'rlcb-blog') ) {
            wp_die('Security check');
        }

        $tax = $wpdb->base_prefix . "rlcb_blog_tax";
        $groups = $wpdb->get_results("SELECT * FROM $tax WHERE 1;");
        $relationships = $wpdb->base_prefix . "rlcb_blog_tax_relationships";
        $insert = "INSERT INTO $relationships (relationship_id , tax_id , blog_id ) VALUES";
        $first_new = false;
        $first_old = false;

        foreach($groups as $group){
            $new = 'new_'.$blog_id.'_'.$group->tax_id;

            if (isset($_POST[$new])){
                if ($first_new) {
                    $insert .= ',';
                }
                $insert .='(null, "'.$group->tax_id.'", "'.$blog_id.'"  )';
                $first_new = true;
            }

            $set = 'set_'.$blog_id.'_'.$group->tax_id;

            if (!isset($_POST[$set])){
                $term_id = $wpdb->get_var("SELECT relationship_id FROM $relationships WHERE tax_id = ".$group->tax_id." AND blog_id = ".$blog_id." LIMIT 1 ");
                $wpdb->query("DELETE FROM $relationships WHERE relationship_id = $term_id");
            }
        }

        $sucinsert = $wpdb->query($insert.';');
        echo '<div id="message" class="updated fade"><p>Bloggen har uppdaterats!</p></div>';
    }

    /**
     *
     *    Functions for managing categories and blogs.
     *
     **/



    function rlcb_manage_blogs(){
        die_if_not_superadmin();

        global $wpdb;

        $tax = $wpdb->base_prefix . "rlcb_blog_tax";
        $groups = $wpdb->get_results("SELECT * FROM $tax WHERE 1;");
        $blogs = wp_get_sites(array('limit' => 1000));
        // echo "<pre>";
        // print_r($groups);
        // echo "</pre>";
        ?>

        <h3>Hantera bloggar</h3>
        <form method='post'>
            <?php wp_nonce_field('rlcb-blogs'); ?>
            <table class='widefat'>
                <thead>
                <tr>
                    <th>Blogg-ID</th>
                    <th>Namn</th>
                    <?php foreach($groups as $group) { ?>
                        <th><?= $group->tax_name ?></th>
                    <?php } ?>
                    <th>Namn</th>
                </tr>
                </thead>
                <tfoot>
                <tr>
                    <th>Blogg-ID</th>
                    <th>Namn</th>
                    <?php foreach($groups as $group) { ?>
                        <th><?= $group->tax_name ?></th>
                    <?php } ?>
                    <th>Namn</th>
                </tr>
                </tfoot>
                <tbody>

                <?php
                $relationships = $wpdb->base_prefix . "rlcb_blog_tax_relationships";
                $i = 0;
                foreach ($blogs as $blog){
                    echo "<tr";
                    echo ($i % 2 == 1) ? ' class="even"' : '';
                    echo ">";
                    echo "<td>". $blog['blog_id'] ."</td>";
                    echo "<td>". $blog['path'] ."</td>";
                    foreach($groups as $group){
                        $sql = 'SELECT relationship_id FROM '.$relationships.' WHERE tax_id = '.$group->tax_id.' AND blog_id = '.$blog['blog_id'].' ;';
                        $set = $wpdb->get_var($sql);
                        if ( $set )
                            echo "<td><input type='checkbox' name='set_".$blog['blog_id']."_".$group->tax_id."' value='".$blog['blog_id']."' checked='checked' ></td>";
                        else
                            echo "<td><input type='checkbox' name='new_".$blog['blog_id']."_".$group->tax_id."' value='".$blog['blog_id']."' ></td>";
                    }
                    echo "<td>". $blog['path'] ."</td>";
                    echo "</tr>";
                    $i++;
                } ?>

                </tbody>
            </table>

            <input type="submit" class="button-primary" name='rlcb_action' value='Uppdatera bloggar' />
            <input type="hidden" name="action/" value="update" />
        </form>

    <?php }

    function rlcb_manage_blog(){
        die_if_not_superadmin();

        global $wpdb, $blog_id;

        $relationships = $wpdb->base_prefix . "rlcb_blog_tax_relationships";

        $tax = $wpdb->base_prefix . "rlcb_blog_tax";
        $groups = $wpdb->get_results("SELECT * FROM $tax WHERE 1;");

        ?>

        <form method='post'>
            <?php wp_nonce_field('rlcb-blog'); ?>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">Kategorier</th>
                    <td>
                        <?php

                        foreach($groups as $group) {
                            $sql = 'SELECT relationship_id FROM '.$relationships.' WHERE tax_id = '.$group->tax_id.' AND blog_id = '.$blog_id.' ;';
                            $set = $wpdb->get_var($sql);

                            if ( $set ) {
                                echo "<label><input type=\"checkbox\" name=\"set_" . $blog_id . "_" . $group->tax_id . "\" value=\"$blog_id\" checked='checked' >$group->tax_name</label>" . br;
                            } else {
                                echo "<label><input type=\"checkbox\" name=\"new_" . $blog_id . "_" . $group->tax_id . "\" value=\"$blog_id\">$group->tax_name</label>" . br;
                            }
                        }

                        ?>
                    </td>
                </tr>
                </tbody>
            </table>
            <input type="submit" class="button-primary" name='rlcb_action' value='Uppdatera blogg' />
            <input type="hidden" name="action/" value="update" />
        </form>

    <?php }

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

    // === PUBLIC FUNCTIONS ===

    /* Get an array of all blog_ids with specificed category
     *
     * @param int $category_id ID of the Category
     * @return array $blogs all the blog_ids in selected Category
     */
    function rlcb_get_blogs_by_category_id($category_id){
        global $wpdb;
        $relationships = $wpdb->base_prefix . "rlcb_blog_tax_relationships";
        $category_id = (int) $category_id;
        $sql = "SELECT blog_id FROM $relationships WHERE tax_id = '%d'; ";
        $blogs = $wpdb->get_col( $wpdb->prepare($sql, $category_id) );
        return $blogs;
    }

    /* Get an array of all blog_ids with specificed category
     *
     * @param string $category_name name of the Category
     * @return array $blogs all the blog_ids in selected Category
     */
    function rlcb_get_blogs_by_category_name($category_name){
        return rlcb_get_blogs_by_category_id(rlcb_get_category_id_by_name($category_name));
    }

    /* Get an array of all blog_ids in not with specified category
     *
     * @param int $category_id ID of the Category
     * @return array $not_with_category all the blog_ids not in selected Category
     */
    function rlcb_get_exclude_category($category_id){
        global $wpdb;
        $category_id = (int) $category_id;
        $category = rlcb_get_blogs_by_category_id($category_id);
        $blogs = wp_get_sites(array('limit' => 1000));
        foreach ($blogs as $blog){
            if (!in_array($blog['blog_id'], $category) )
                $not_with_category[] = $blog['blog_id'];
        }
        return $not_with_category;
    }

    /* Gets an array of all category_ids of the specified blog
     *
     * @param int $blog_id ID of the blog
     * @return array $blogs all the category_ids of the specified blog
     */
    function rlcb_get_blogs_categories($blog_id){
        global $wpdb;
        $relationships = $wpdb->base_prefix . "rlcb_blog_tax_relationships";
        $sql = "SELECT tax_id FROM $relationships WHERE blog_id = '$blog_id';";
        $blogs = $wpdb->get_col($sql);
        return $blogs;
    }

    /* Gets the category ID of a category
     *
     * @param string $name the name of the category
     * @return string $tax_id ID of the category
     */
    function rlcb_get_category_id_by_name($name)
    {
        global $wpdb;

        $tax = $wpdb->base_prefix . "rlcb_blog_tax";
        $sql = "SELECT tax_id FROM $tax WHERE tax_name = '$name';";
        $tax_id = $wpdb->get_var($sql);
        return $tax_id;

    }
}

new Blog_Categories_Plugin();
?>