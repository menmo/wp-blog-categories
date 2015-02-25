<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Blog_Cat_List_Table extends WP_List_Table {

    private $cat_table_name;
    private $cat_relations_table_name;

    function __construct($args) {

        $this->cat_table_name = $args['cat_table'];
        $this->cat_relations_table_name = $args['cat_relations_table'];

        parent::__construct( array_merge($args, array(
            'singular'=> 'wp_list_blog_category', //Singular label
            'plural' => 'wp_list_blog_categories', //plural label, also this well be one of the table css class
            'ajax'   => false //We won't support Ajax for this table
        )) );
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
            'cat_id' => array('cat_id', false),
            'blog_count' => array('blog_count', false)
        );
    }

    function prepare_items() {
        global $wpdb;

        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$this->cat_table_name}");

        /* -- Ordering parameters -- */
        //Parameters that are going to be used to order the result
        $sortable_columns = $this->get_sortable_columns();
        $order_by = 'cat_id';
        if(isset($_GET["orderby"]) && isset($sortable_columns[$_GET["orderby"]])) {
            $order_by = $_GET["orderby"];
        }

        $order = isset($_GET["order"]) && $_GET["order"] == 'desc' ? 'desc' : 'asc';

        /* -- Pagination parameters -- */
        //How many to display per page?
        $per_page = 20;
        $offset = 0;

        //Which page is this?
        $paged = !empty($_GET["paged"]) ? $_GET["paged"] : '';
        //Page Number
        if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
        //How many pages do we have in total?
        $total_pages = ceil($total_items/$per_page);
        //adjust the query to take pagination into account
        if(!empty($paged) && !empty($per_page)){
            $offset=($paged-1)*$per_page;
        }

        /* -- Preparing your query -- */
        $query = "SELECT `{$this->cat_table_name}`.`cat_id`, `{$this->cat_table_name}`.`cat_name`, COUNT(`{$this->cat_relations_table_name}`.`cat_id`) as blog_count
                FROM {$this->cat_table_name}
                LEFT JOIN {$this->cat_relations_table_name} ON `{$this->cat_table_name}`.`cat_id` = `{$this->cat_relations_table_name}`.`cat_id`
                GROUP BY `{$this->cat_table_name}`.`cat_id`, `{$this->cat_table_name}`.`cat_name` ORDER BY $order_by $order LIMIT $offset,$per_page";

        /* -- Register the pagination -- */
        $this->set_pagination_args( array(
            "total_items" => $total_items,
            "total_pages" => $total_pages,
            "per_page" => $per_page,
        ) );
        //The pagination links are automatically built according to those parameters

        /* -- Register the Columns -- */
        $this->_column_headers = array($this->get_columns(), array(), $sortable_columns);

        /* -- Fetch the items -- */
        $this->items = $wpdb->get_results($query);
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