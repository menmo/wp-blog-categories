<?php
/**
 * Page for selecting categories on each blog, found under options menu.
 */

$location = false; // Redirect

if (isset($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        case 'edited':
            check_admin_referer('blog-cat', '_wpnonce_blog-cat');

            if (!current_user_can('manage_sites'))
                wp_die(__('Cheatin&#8217; uh?'), 403);

            $blog_id = get_current_blog_id();
            Blog_Cat_Relationships_DB::delete_all($blog_id, 'blog');

            $cats = $_POST['blog-cats'];

            foreach($cats as $cat_ID) {
                Blog_Cat_Relationships_DB::add($cat_ID, $blog_id);
            }

            $location = 'options-general.php?page='.$_REQUEST['page'];

            $location = add_query_arg( 'message', 1, $location );

            break;
    }
}

if (!$location && !empty($_REQUEST['_wp_http_referer'])) {
    $location = remove_query_arg(array('_wp_http_referer', '_wpnonce'), wp_unslash($_SERVER['REQUEST_URI']));
}

if ($location) {
    if (!empty($_REQUEST['paged'])) {
        $location = add_query_arg('paged', (int)$_REQUEST['paged'], $location);
    }
    wp_redirect($location);
    exit;
}

$messages = array(
    0 => '', // Unused. Messages start at index 1.
    1 => __('Blog updated')
);

$message = false;
if (isset($_REQUEST['message']) && ($msg = (int)$_REQUEST['message'])) {
    if (isset($messages[$msg]))
        $message = $messages[$msg];
}

if ($message) : ?>
    <div id="message" class="updated"><p><?php echo $message; ?></p></div>
    <?php $_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
endif;

$cats = Blog_Cats_DB::get_list();
$blog_cats = Blog_Cat_Relationships_DB::get_cat_list(get_current_blog_id());

?>

<div class="wrap">

    <div id="col-left">
        <div class="col-wrap">
            <h2><?php _e('Categories') ?></h2>

            <br class="clear"/>

            <div class="form-wrap">
                <form id="blog-cat-select" method="post" action="" class="validate">
                    <?php wp_nonce_field('blog-cat', '_wpnonce_blog-cat'); ?>
                    <input type="hidden" name="action" value="edited" />
                    <table class="widefat">
                        <?php foreach ($cats as $i => $cat) { ?>
                            <tr <?php if ($i % 2 == 0) { ?>class="alternate"<?php } ?>>
                                <td>
                                    <label>
                                        <input type="checkbox" name="blog-cats[]" value="<?php echo $cat->cat_id ?>" <?php if (in_array($cat->cat_id, $blog_cats)) { ?>checked="checked"<?php } ?>/>
                                        <?php echo $cat->cat_name ?>
                                    </label>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>

                    <br class="clear"/>

                    <?php submit_button(__('Update')); ?>
                </form>
            </div>
        </div>
    </div>

</div>