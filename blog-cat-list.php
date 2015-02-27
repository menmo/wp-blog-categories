<?php


if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Blog_Cat_List_Table extends WP_List_Table {

    function __construct() {

        parent::__construct( array(
            'singular'=> 'wp_list_blog_category', //Singular label
            'plural' => 'wp_list_blog_categories', //plural label, also this well be one of the table css class
            'ajax'   => false //We won't support Ajax for this table
        ));
    }

    function get_columns(){
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'cat_name' => __('Name'),
            'cat_id' => __('ID'),
            'blog_count' => __('Blog count')
        );
        return $columns;
    }

    public function get_sortable_columns() {
        return $sortable = array(
            'cat_name' => array('cat_name', false),
            'cat_id' => array('cat_id', true),
            'blog_count' => array('blog_count', true)
        );
    }

    function prepare_items() {

        $total_items = Blog_Cats_DB::count();
	    $list_args = array(
		    'limit' => 20
	    );

        /* -- Ordering parameters -- */
        //Parameters that are going to be used to order the result
        $sortable_columns = $this->get_sortable_columns();
        if(isset($_GET["orderby"]) && isset($sortable_columns[$_GET["orderby"]])) {
            $list_args['order_by'] = $_GET["orderby"];
        }

        if(isset($_GET["order"])) {
	        $list_args['order'] = $_GET["order"] == 'desc' ? 'desc' : 'asc';
        };

        /* -- Pagination parameters -- */
        //Which page is this?
        $paged = !empty($_GET["paged"]) ? $_GET["paged"] : '';
        //Page Number
        if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
        //How many pages do we have in total?
        $total_pages = ceil($total_items/$list_args['limit']);
        //adjust the query to take pagination into account
        if(!empty($paged) && !empty($per_page)){
            $offset=($paged-1)*$per_page;
        }

        /* -- Register the pagination -- */
        $this->set_pagination_args( array(
            "total_items" => $total_items,
            "total_pages" => $total_pages,
            "per_page" => $list_args['limit'],
        ) );
        //The pagination links are automatically built according to those parameters

        /* -- Register the Columns -- */
        $this->_column_headers = array($this->get_columns(), array(), $sortable_columns);

        /* -- Fetch the items -- */
        $this->items = Blog_Cats_DB::get_list($list_args);
    }

    function column_default( $item, $column_name ) {
        return $item->{$column_name};
    }

    function column_cat_name($item) {
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&cat_id=%s">%s</a>',$_REQUEST['page'],'edit',$item->cat_id, __('Edit')),
            'delete'    => sprintf('<a href="?page=%s&action=%s&cat_id=%s">%s</a>',$_REQUEST['page'],'delete',$item->cat_id, __('Delete')),
        );

        return sprintf('%1$s %2$s', $item->cat_name, $this->row_actions($actions) );
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="delete_cats[]" value="%s" />', $item->cat_id
        );
    }

    function get_bulk_actions() {
        $actions = array(
            'bulk-delete'    => 'Delete'
        );
        return $actions;
    }
}