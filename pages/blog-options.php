<?php
/**
 * Page for selecting categories on each blog, found under options menu.
 */

$location = false; // Redirect
$cat_list = new Blog_Cat_List_Table();

switch ( $cat_list->current_action() ) {


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
	1 => __( 'Blog updated' ),
	2 => __( 'Blog not updated.' ),
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
$sites = wp_get_sites(array(
	'archived' => false,
	'deleted' => false
));

?>

<div class="wrap">
	<h2><?php _e('Categories') ?></h2>

	<div id="col-container">

		<form id="blog-cat-bulk-actions" method="get">
			<?php wp_nonce_field('bulk-blog-cat', '_wpnonce_bulk-blog-cat'); ?>
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<?php $cat_list->display(); ?>
		</form>

	</div>

</div>