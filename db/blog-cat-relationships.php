<?php

defined('ABSPATH') or die();

class Blog_Cat_Relationships {

	public static function table_name() {
		global $wpdb;
		return $wpdb->base_prefix . "blog_cat_relationsships";
	}

	public static function create_table() {
		global $wpdb;

		$charset_collate = '';

		if ( ! empty($wpdb->charset) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty($wpdb->collate) )
			$charset_collate .= " COLLATE $wpdb->collate";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$sql = "CREATE TABLE IF NOT EXISTS {Blog_Cat_Relationships::table_name()} (
                relationship_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                cat_id bigint(20) unsigned NOT NULL,
                blog_id bigint(20) unsigned NOT NULL,
                UNIQUE KEY relationship_id (relationship_id)
                )$charset_collate;";

		dbDelta($sql);
	}

}