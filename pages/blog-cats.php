<?php
/**
 * Main page for managing blog categories
 */

add_screen_option( 'per_page', array( 'label' => __('Count'), 'default' => 20, 'option' => 'edit_blog_cats_per_page' ) );

$location = false; // Redirect
$cat = false; // Current cat
$subtitle = __('Add New Category');

$cat_list = new Blog_Cat_List_Table(array(
	'cat_table' => $this->cat_table_name,
	'cat_relations_table' => $this->cat_relations_table_name
));

switch ( $cat_list->current_action() ) {

	case 'add':

		check_admin_referer('blog-cat', '_wpnonce_blog-cat');

		if (!current_user_can('manage_sites'))
			wp_die(__('Cheatin&#8217; uh?'), 403);

		$ret = Blog_Cats::add($_POST['blog-cat-name']);

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

		Blog_Cats::delete( $_REQUEST['cat_id'] );

		$location = 'admin.php?page='.$_REQUEST['page'];

		$location = add_query_arg( 'message', 2, $location );

		break;

	case 'bulk-delete':

		check_admin_referer('bulk-blog-cat', '_wpnonce_bulk-blog-cat');

		if (!current_user_can('manage_sites'))
			wp_die(__('Cheatin&#8217; uh?'), 403);

		$tags = (array) $_REQUEST['delete_cats'];
		foreach ( $tags as $cat_ID ) {
			Blog_Cats::delete( $cat_ID );
		}

		$location = 'admin.php?page='.$_REQUEST['page'];

		$location = add_query_arg( 'message', 6, $location );

		break;

	case 'edit':

		$subtitle = __( 'Edit Category' );

		$cat_ID = (int) $_REQUEST['cat_id'];

		$cat = Blog_Cats::get( $cat_ID );
		if ( ! $cat )
			wp_die( __( 'You attempted to edit an item that doesn&#8217;t exist. Perhaps it was deleted?' ) );

		break;

	case 'edited':

		check_admin_referer('blog-cat', '_wpnonce_blog-cat');

		if (!current_user_can('manage_sites'))
			wp_die(__('Cheatin&#8217; uh?'), 403);

		$cat_ID = (int) $_POST['cat_id'];

		$cat = Blog_Cats::get( $cat_ID );
		if ( ! $cat )
			wp_die( __( 'You attempted to edit an item that doesn&#8217;t exist. Perhaps it was deleted?' ) );

		$ret = Blog_Cats::update($cat_ID, $_POST['blog-cat-name']);

		$location = 'admin.php?page='.$_REQUEST['page'];

		if ( $ret && !is_wp_error( $ret ) )
			$location = add_query_arg( 'message', 3, $location );
		else
			$location = add_query_arg( 'message', 5, $location );
		break;
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
	<h2><?php _e('Categories') ?></h2>

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
					<h3><?php echo $subtitle ?></h3>
					<form id="blog-cat" method="post" action="" class="validate">
						<input type="hidden" name="action" value="<?php echo $cat ? 'edited' : 'add' ?>" />
						<input type="hidden" name="cat_id" value="<?php echo $cat ? $cat->cat_id : '' ?>" />
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