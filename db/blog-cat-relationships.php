<?php

defined('ABSPATH') or die();

class Blog_Cat_Relationships_DB {

	public static function table_name() {
		global $wpdb;
		return $wpdb->base_prefix . "blog_cat_relationsships";
	}

	public static function create_table() {
		global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$sql = "CREATE TABLE IF NOT EXISTS " . Blog_Cat_Relationships_DB::table_name() . " (
                relationship_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                cat_id bigint(20) unsigned NOT NULL,
                blog_id bigint(20) unsigned NOT NULL,
                UNIQUE KEY relationship_id (relationship_id)
                )$charset_collate;";

		dbDelta($sql);
	}

	public static function add( $cat_ID, $blog_ID ) {
		global $wpdb;
		$query = $wpdb->prepare("INSERT INTO " . Blog_Cat_Relationships_DB::table_name() . " (cat_id, blog_id) VALUES ( %d, %d )", $cat_ID, $blog_ID);
		$ret = $wpdb->query( $query );
		if(!is_wp_error($ret)) {
			return $wpdb->insert_id;
		}
		return false;
	}

	public static function delete_all($id, $type = 'cat') {
		global $wpdb;
		$query = $wpdb->prepare("DELETE FROM " . Blog_Cat_Relationships_DB::table_name() . " WHERE " . $type . "_id = %d", $id);
		return $wpdb->query($query);
	}

	public static function get_blog_list( $cat_ID ) {
		global $wpdb;
		$query = $wpdb->prepare("SELECT blog_id FROM  " . Blog_Cat_Relationships_DB::table_name() . " WHERE cat_id = %d", $cat_ID);
		return $wpdb->get_col($query);
	}

	public static function get_cat_list($blog_ID)
	{
		global $wpdb;
		$query = $wpdb->prepare("SELECT cat_id FROM  " . Blog_Cat_Relationships_DB::table_name() . " WHERE blog_id = %d", $blog_ID);
		return $wpdb->get_col($query);
	}

}