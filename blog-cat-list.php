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
            'col_cat_id' => __('ID'),
            'col_cat_name' => __('Name'),
            'col_cat_blog_count' => __('Count')
        );
        return $columns;
    }

    public function get_sortable_columns() {
        return $sortable = array(
            'col_cat_id'=>'cat_id',
            'col_cat_name'=>'cat_name',
            'col_cat_blog_count'=>'blog_count'
        );
    }

    function prepare_items() {
        global $wpdb, $_wp_column_headers;
        $screen = get_current_screen();

        /* -- Preparing your query -- */
        $query = "SELECT `{$this->cat_table_name}`.`cat_id`, `{$this->cat_table_name}`.`cat_name`, COUNT(`{$this->cat_relations_table_name}`.`cat_id`) as blog_count
                    FROM {$this->cat_table_name}
                    LEFT JOIN {$this->cat_relations_table_name} ON `{$this->cat_table_name}`.`cat_id` = `{$this->cat_relations_table_name}`.`cat_id`
                    GROUP BY `{$this->cat_table_name}`.`cat_id`, `{$this->cat_table_name}`.`cat_name`";

        /* -- Ordering parameters -- */
        //Parameters that are going to be used to order the result
        $orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'ASC';
        $order = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : '';
        if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }

        /* -- Pagination parameters -- */
        //Number of elements in your table?
        $totalitems = $wpdb->query($query); //return the total number of affected rows
        //How many to display per page?
        $perpage = 20;
        //Which page is this?
        $paged = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
        //Page Number
        if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
        //How many pages do we have in total?
        $totalpages = ceil($totalitems/$perpage);
        //adjust the query to take pagination into account
        if(!empty($paged) && !empty($perpage)){
            $offset=($paged-1)*$perpage;
            $query.=' LIMIT '.(int)$offset.','.(int)$perpage;
        }

        /* -- Register the pagination -- */
        $this->set_pagination_args( array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage,
        ) );
        //The pagination links are automatically built according to those parameters

        /* -- Register the Columns -- */
        $columns = $this->get_columns();
        $_wp_column_headers[$screen->id]=$columns;

        /* -- Fetch the items -- */
        $this->items = $wpdb->get_results($query);
    }

    function column_default( $item, $column_name ) {
        return $item[substr($column_name, 4)];
        switch( $column_name ) {
            case 'ID':
            case 'name':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
    }
}