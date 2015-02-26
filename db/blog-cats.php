<?php

defined('ABSPATH') or die();

class Blog_Cats {

	private static function table_name() {
		global $wpdb;
		return $wpdb->base_prefix . "blog_cats";
	}

	public static function create_table() {
		global $wpdb;

		$charset_collate = '';

		if ( ! empty($wpdb->charset) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty($wpdb->collate) )
			$charset_collate .= " COLLATE $wpdb->collate";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$sql = "CREATE TABLE IF NOT EXISTS {Blog_Cats::table_name()} (
                cat_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                cat_name varchar(200) DEFAULT NULL,
                UNIQUE KEY cat_id (cat_id)
                )$charset_collate;";

		dbDelta($sql);
	}

	public static function add($name) {
		global $wpdb;
		$query = $wpdb->prepare("INSERT INTO " . Blog_Cats::table_name() . " (cat_name) VALUES ( %s )", $name);
		return $wpdb->query( $query );
	}

	public static function delete($cat_ID) {
		global $wpdb;
		$sql = "DELETE FROM " . Blog_Cats::table_name() . " WHERE cat_id = $cat_ID";
		return $wpdb->query( $sql );
	}

	public static function get($cat_ID) {
		global $wpdb;
		$sql = "SELECT * FROM " . Blog_Cats::table_name() . " WHERE cat_id = $cat_ID";
		return $wpdb->get_row( $sql );
	}

	public static function update($cat_ID, $name) {
		global $wpdb;
		$query = $wpdb->prepare("UPDATE " . Blog_Cats::table_name() . " SET cat_name = %s WHERE cat_id = %d", $name, $cat_ID);
		return $wpdb->query( $query );
	}

	public static function count() {
		global $wpdb;
		return $wpdb->get_var("SELECT COUNT(*) FROM " . Blog_Cats::table_name());
	}

	public static function get_list($args) {
		$args = array_merge(array(
			'order_by' => 'cat_id',
			'order' => 'ASC',
			'offset' => 0,
			'limit' => 20
		), $args);

		$cat_table_name = Blog_Cats::table_name();
		$cat_relationships_table_name = Blog_Cat_Relationships::table_name();

		$query = "SELECT `{$cat_table_name}`.`cat_id`, `{$cat_table_name}`.`cat_name`, COUNT(`{$cat_relationships_table_name}`.`cat_id`) as blog_count
                FROM {$cat_table_name}
                LEFT JOIN {$cat_relationships_table_name} ON `{$cat_table_name}`.`cat_id` = `{$cat_relationships_table_name}`.`cat_id`
                GROUP BY `{$cat_table_name}`.`cat_id`, `{$cat_table_name}`.`cat_name` ORDER BY {$args['order_by']} {$args['order']} LIMIT {$args['offset']},{$args['limit']}";

		global $wpdb;
		return $wpdb->get_results($query);
	}
}