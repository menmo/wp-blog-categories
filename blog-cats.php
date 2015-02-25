<?php

/**
 * Plugin Name: Blog Categories
 * Plugin URI:
 * Description: A plugin for categorizing blogs.
 * Version: 1.0
 * Author: Menmo AB
 * Author URI: http://www.menmo.se
 * License: GPL2
 */

require_once(plugin_dir_path(__FILE__) . 'blog-cat-list.php');

define("br", "<br />");

class Blog_Categories_Plugin {

    private $cat_table_name;
    private $cat_relations_table_name;

    function __construct() {

        defined('ABSPATH') or die();

        global $wpdb;

        $this->cat_table_name = $wpdb->base_prefix . "blog_cats";
        $this->cat_relations_table_name = $wpdb->base_prefix . "blog_cat_relationsships";

        register_activation_hook( __FILE__, array($this, 'activate') );

        add_action('network_admin_menu', array($this, 'add_menu') );
        add_action('wpmu_new_blog', array($this, 'new_blog') );
        add_action('admin_menu', array($this, 'add_options_page') );

    }

    public function activate(){

        $this->die_if_not_superadmin();

        global $wpdb;

        $charset_collate = '';

        if ( ! empty($wpdb->charset) )
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if ( ! empty($wpdb->collate) )
            $charset_collate .= " COLLATE $wpdb->collate";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE IF NOT EXISTS {$this->cat_table_name} (
                cat_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                cat_name varchar(200) DEFAULT NULL,
                UNIQUE KEY cat_id (cat_id)
                )$charset_collate;";


        dbDelta($sql);

        $sql = "CREATE TABLE IF NOT EXISTS {$this->cat_relations_table_name} (
                relationship_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                cat_id bigint(20) unsigned NOT NULL,
                blog_id bigint(20) unsigned NOT NULL,
                UNIQUE KEY relationship_id (relationship_id)
                )$charset_collate;";

        dbDelta($sql);
    }

    public function add_menu() {
        $page_title = __('Categories');
        $menu_title = __('Categories');
        $capability = 'manage_sites';
        $menu_slug = 'blog-cats-menu';
        $function = array($this, 'blog_cats_list');
        add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, 'dashicons-category' );
    }

    public function blog_cats_list() {
        $this->die_if_not_superadmin();

        add_screen_option( 'per_page', array( 'label' => __('Count'), 'default' => 20, 'option' => 'edit_blog_cats_per_page' ) );

        $location = false;
        $title = __('Add New Category');

        $cat_list = new Blog_Cat_List_Table(array(
            'cat_table' => $this->cat_table_name,
            'cat_relations_table' => $this->cat_relations_table_name
        ));

        switch ( $cat_list->current_action() ) {

            case 'add':

                check_admin_referer('blog-cat', '_wpnonce_blog-cat');

                if (!current_user_can('manage_sites'))
                    wp_die(__('Cheatin&#8217; uh?'), 403);

                $ret = $this->add_blog_cat($_POST['blog-cat-name']);

                $location = 'admin.php?page='.$_REQUEST['page'];

                if ($ret && !is_wp_error($ret))
                    $location = add_query_arg('message', 1, $location);
                else
                    $location = add_query_arg('message', 4, $location);

                break;

            case 'delete':

                if (!current_user_can('manage_sites'))
                    wp_die(__('Cheatin&#8217; uh?'), 403);

                if ( ! isset( $_REQUEST['cat_id'] ) ) {
                    break;
                }

                $this->delete_blog_cat( $_REQUEST['cat_id'] );

                $location = 'admin.php?page='.$_REQUEST['page'];

                $location = add_query_arg( 'message', 2, $location );

                break;

            case 'bulk-delete':

                check_admin_referer('bulk-blog-cat', '_wpnonce_bulk-blog-cat');

                if (!current_user_can('manage_sites'))
                    wp_die(__('Cheatin&#8217; uh?'), 403);

                $tags = (array) $_REQUEST['delete_cats'];
                foreach ( $tags as $cat_ID ) {
                    $this->delete_blog_cat( $cat_ID );
                }

                $location = 'admin.php?page='.$_REQUEST['page'];

                $location = add_query_arg( 'message', 6, $location );

                break;

            case 'edit':

                $title = __('Edit category');

                $cat_ID = (int) $_REQUEST['cat_id'];

                $cat = $this->get_blog_cat( $cat_ID );
                if ( ! $cat )
                    wp_die( __( 'You attempted to edit an item that doesn&#8217;t exist. Perhaps it was deleted?' ) );
        }

        if ( ! $location && ! empty( $_REQUEST['_wp_http_referer'] ) ) {
            $location = remove_query_arg( array('_wp_http_referer', '_wpnonce'), wp_unslash($_SERVER['REQUEST_URI']) );
        }

        if ( $location ) {
            if ( ! empty( $_REQUEST['paged'] ) ) {
                $location = add_query_arg( 'paged', (int) $_REQUEST['paged'], $location );
            }
            wp_redirect( $location );
            exit;
        }

        $messages = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __( 'Category added.' ),
            2 => __( 'Category deleted.' ),
            3 => __( 'Category updated.' ),
            4 => __( 'Category not added.' ),
            5 => __( 'Category not updated.' ),
            6 => __( 'Categories deleted.' )
        );

        $message = false;
        if ( isset( $_REQUEST['message'] ) && ( $msg = (int) $_REQUEST['message'] ) ) {
            if ( isset( $messages[ $msg ] ) )
                $message = $messages[ $msg ];
        }

        if ( $message ) : ?>
            <div id="message" class="updated"><p><?php echo $message; ?></p></div>
            <?php $_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
        endif;

        $cat_list->prepare_items();

                ?>

        <div class="wrap">
            <h2><?php _e('Blog categories') ?></h2>

            <div id="col-container">

                <div id="col-right">
                    <div class="col-wrap">
                        <form id="blog-cat-bulk-actions" method="get">
                            <?php wp_nonce_field('bulk-blog-cat', '_wpnonce_bulk-blog-cat'); ?>
                            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                            <?php $cat_list->display(); ?>
                        </form>

                        <br class="clear" />
                    </div>
                </div><!-- /col-right -->

                <div id="col-left">
                    <div class="col-wrap">
                        <div class="form-wrap">
                            <h3><?php echo $title ?></h3>
                            <form id="blog-cat" method="post" action="" class="validate">
                                <input type="hidden" name="action" value="<?php echo $cat ? 'edited' : 'add' ?>" />
                                <?php wp_nonce_field('blog-cat', '_wpnonce_blog-cat'); ?>
                                <div class="form-field form-required term-name-wrap">
                                    <label for="blog-cat-name"><?php _ex( 'Name', 'term name' ); ?></label>
                                    <input name="blog-cat-name" id="blog-cat-name" type="text" value="<?php echo $cat ? $cat->cat_name : '' ?>" size="40" aria-required="true" />
                                </div>

                                <?php submit_button( $cat ? __('Update') : __('Add New Category') ); ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    <?php

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
    private function add_blog_cat($name) {
        global $wpdb;
        $sql = "INSERT INTO {$this->cat_table_name} (cat_name) VALUES (\"$name\")";
        return $wpdb->query( $sql );
    }

    private function delete_blog_cat($cat_ID) {
        global $wpdb;
        $sql = "DELETE FROM {$this->cat_table_name} WHERE cat_id = $cat_ID";
        return $wpdb->query( $sql );
    }

    private function get_blog_cat($cat_ID) {
        global $wpdb;
        $sql = "SELECT * FROM {$this->cat_table_name} WHERE cat_id = $cat_ID";
        return $wpdb->get_row( $sql );
    }

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