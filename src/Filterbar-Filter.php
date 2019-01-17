<?php

/**
 * Class Tribe__Events__Filterbar__Filters__Artist
 *
 * Based on Tribe__Events__Filterbar__Filters__Organizer
 */
class Tribe__Events__Filterbar__Filters__Artist extends Tribe__Events__Filterbar__Filter {
	public $type = 'select';

	public function get_admin_form() {
		$title = $this->get_title_field();
		$type  = $this->get_multichoice_type_field();

		return $title . $type;
	}

	protected function get_values() {
		/** @var wpdb $wpdb */
		global $wpdb;
		// get artist IDs associated with published events
		$artist_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT m.meta_value FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID=m.post_id WHERE p.post_type=%s AND p.post_status='publish' AND m.meta_key=%s AND m.meta_value > 0",
				Tribe__Events__Main::POSTTYPE,
				Tribe__Extension__Artist_Linked_Post_Type::instance()->get_linked_post_type_custom_field_key()
			)
		);
		array_filter( $artist_ids );
		if ( empty( $artist_ids ) ) {
			return array();
		}

		/**
		 * Filter Total Artists in Filter Bar
		 * Use this with caution, this will load artists on the front-end, may be slow
		 * The base limit is 200 for safety reasons
		 *
		 *
		 * @parm int  200 posts per page limit
		 * @parm array $artist_ids   ids of artists attached to events
		 */
		$limit = apply_filters( Tribe__Extension__Artist_Linked_Post_Type::POST_TYPE_KEY . '_filter_bar_limit', 200, $artist_ids );

		$artists = get_posts(
			array(
				'post_type'        => Tribe__Extension__Artist_Linked_Post_Type::POST_TYPE_KEY,
				'posts_per_page'   => $limit,
				'suppress_filters' => false,
				'post__in'         => $artist_ids,
				'post_status'      => 'publish',
				'orderby'          => 'title',
				'order'            => 'ASC',
			)
		);

		$artists_array = array();
		foreach ( $artists as $artist ) {
			$artists_array[] = array(
				'name'  => $artist->post_title,
				'value' => $artist->ID,
			);
		}

		return $artists_array;
	}

	protected function setup_join_clause() {
		global $wpdb;
		$this->joinClause = $wpdb->prepare(
			"INNER JOIN {$wpdb->postmeta} AS artist_filter ON ({$wpdb->posts}.ID = artist_filter.post_id AND artist_filter.meta_key=%s)",
			Tribe__Extension__Artist_Linked_Post_Type::instance()->get_linked_post_type_custom_field_key()
		);
	}

	protected function setup_where_clause() {
		if ( is_array( $this->currentValue ) ) {
			$artist_ids = implode( ',', array_map( 'intval', $this->currentValue ) );
		} else {
			$artist_ids = esc_attr( $this->currentValue );
		}

		$this->whereClause = " AND artist_filter.meta_value IN ($artist_ids) ";
	}
}
