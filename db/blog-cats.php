<?php

defined('ABSPATH') or die();

class Blog_Cats_DB {

	private static function table_name() {
		global $wpdb;
		return $wpdb->base_prefix . "blog_cats";
	}

	public static function create_table() {
		global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$sql = "CREATE TABLE IF NOT EXISTS " . Blog_Cats_DB::table_name() . " (
                cat_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                cat_name varchar(200) DEFAULT NULL,
                UNIQUE KEY cat_id (cat_id)
                )$charset_collate;";

		dbDelta($sql);
	}

	public static function add($name) {
		global $wpdb;
		$query = $wpdb->prepare("INSERT INTO " . Blog_Cats_DB::table_name() . " (cat_name) VALUES ( %s )", $name);
		$ret = $wpdb->query( $query );
		if(!is_wp_error($ret)) {
			return $wpdb->insert_id;
			wp_cache_delete('list', 'blog_cats');
		}
		return false;
	}

	public static function delete($cat_ID) {
		global $wpdb;
		$sql = "DELETE FROM " . Blog_Cats_DB::table_name() . " WHERE cat_id = $cat_ID";
		wp_cache_delete('list', 'blog_cats');
		return $wpdb->query( $sql );
	}

	public static function get($cat_ID) {
		global $wpdb;
		$sql = "SELECT * FROM " . Blog_Cats_DB::table_name() . " WHERE cat_id = $cat_ID";
		return $wpdb->get_row( $sql );
	}

	public static function update($cat_ID, $name) {
		global $wpdb;
		$query = $wpdb->prepare("UPDATE " . Blog_Cats_DB::table_name() . " SET cat_name = %s WHERE cat_id = %d", $name, $cat_ID);
		return $wpdb->query( $query );
	}

	public static function count() {
		global $wpdb;
		return $wpdb->get_var("SELECT COUNT(*) FROM " . Blog_Cats_DB::table_name());
	}

	public static function get_list($args = array()) {
		if(! $list = wp_cache_get('list', 'blog_cats')) {


			$args = array_merge(array(
					'order_by' => 'cat_id',
					'order' => 'ASC',
					'offset' => 0,
					'limit' => 20
			), $args);

			$cat_table_name = Blog_Cats_DB::table_name();
			$cat_relationships_table_name = Blog_Cat_Relationships_DB::table_name();

			$query = "SELECT `{$cat_table_name}`.`cat_id`, `{$cat_table_name}`.`cat_name`, COUNT(`{$cat_relationships_table_name}`.`cat_id`) as blog_count
                FROM {$cat_table_name}
                LEFT JOIN {$cat_relationships_table_name} ON `{$cat_table_name}`.`cat_id` = `{$cat_relationships_table_name}`.`cat_id`
                GROUP BY `{$cat_table_name}`.`cat_id`, `{$cat_table_name}`.`cat_name` ORDER BY {$args['order_by']} {$args['order']} LIMIT {$args['offset']},{$args['limit']}";

			global $wpdb;
		 	$list = $wpdb->get_results($query);
			wp_cache_set('list', $list, 'blog_cats');
		}
		return $list;
	}
}