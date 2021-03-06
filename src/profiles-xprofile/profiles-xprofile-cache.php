<?php
/**
 * Profiles XProfile Caching Functions.
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout Profiles.
 *
 * @package Profiles
 * @suprofilesackage XProfileCache
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Determine which xprofile fields do not have cached values for a user.
 *
 * @since 2.2.0
 *
 * @param int   $user_id   User ID to check.
 * @param array $field_ids XProfile field IDs.
 * @return array
 */
function profiles_xprofile_get_non_cached_field_ids( $user_id = 0, $field_ids = array() ) {
	$uncached_fields = array();

	foreach ( $field_ids as $field_id ) {
		$field_id  = (int) $field_id;
		$cache_key = "{$user_id}:{$field_id}";
		if ( false === wp_cache_get( $cache_key, 'profiles_xprofile_data' ) ) {
			$uncached_fields[] = $field_id;
		}
	}

	return $uncached_fields;
}

/**
 * Slurp up xprofilemeta for a specified set of profile objects.
 *
 * We do not use profiles_update_meta_cache() for the xprofile component. This is
 * because the xprofile component has three separate object types (group,
 * field, and data) and three corresponding cache groups. Using the technique
 * in profiles_update_meta_cache(), pre-fetching would take three separate database
 * queries. By grouping them together, we can reduce the required queries to
 * one.
 *
 * This function is called within a profiles_has_profile() loop.
 *
 * @since 2.0.0
 *
 * @param array $object_ids Multi-dimensional array of object_ids, keyed by
 *                          object type ('group', 'field', 'data').
 * @return bool
 */
function profiles_xprofile_update_meta_cache( $object_ids = array() ) {
	global $wpdb;

	// Bail if no objects.
	if ( empty( $object_ids ) ) {
		return false;
	}

	$profiles = profiles();

	// Define the array where uncached object IDs will be stored.
	$uncached_object_ids = array(
		'group',
		'field',
		'data'
	);

	// Define the cache groups for the 3 types of XProfile metadata.
	$cache_groups = array(
		'group' => 'xprofile_group_meta',
		'field' => 'xprofile_field_meta',
		'data'  => 'xprofile_data_meta',
	);

	// No reason to query yet.
	$do_query = false;

	// Loop through object types and look for uncached data.
	foreach ( $uncached_object_ids as $object_type ) {

		// Skip if empty object type.
		if ( empty( $object_ids[ $object_type ] ) ) {
			continue;
		}

		// Sanitize $object_ids passed to the function.
		$object_type_ids = wp_parse_id_list( $object_ids[ $object_type ] );

		// Get non-cached IDs for each object type.
		$uncached_object_ids[ $object_type ] = profiles_get_non_cached_ids( $object_type_ids, $cache_groups[ $object_type ] );

		// Set the flag to do the meta query.
		if ( ! empty( $uncached_object_ids[ $object_type ] ) && ( false === $do_query ) ) {
			$do_query = true;
		}
	}

	// Bail if no uncached items.
	if ( false === $do_query ) {
		return;
	}

	// Setup where conditions for query.
	$where_sql        = '';
	$where_conditions = array();

	// Loop through uncached objects and prepare to query for them.
	foreach ( $uncached_object_ids as $otype => $oids ) {

		// Skip empty object IDs.
		if ( empty( $oids ) ) {
			continue;
		}

		// Compile WHERE query conditions for uncached metadata.
		$oids_sql           = implode( ',', wp_parse_id_list( $oids ) );
		$where_conditions[] = $wpdb->prepare( "( object_type = %s AND object_id IN ({$oids_sql}) )", $otype );
	}

	// Bail if no where conditions.
	if ( empty( $where_conditions ) ) {
		return;
	}

	// Setup the WHERE query part.
	$where_sql = implode( " OR ", $where_conditions );

	// Attempt to query meta values.
	$meta_list = $wpdb->get_results( "SELECT object_id, object_type, meta_key, meta_value FROM {$profiles->profile->table_name_meta} WHERE {$where_sql}" );

	// Bail if no results found.
	if ( empty( $meta_list ) || is_wp_error( $meta_list ) ) {
		return;
	}

	// Setup empty cache array.
	$cache = array();

	// Loop through metas.
	foreach ( $meta_list as $meta ) {
		$oid    = $meta->object_id;
		$otype  = $meta->object_type;
		$okey   = $meta->meta_key;
		$ovalue = $meta->meta_value;

		// Force subkeys to be array type.
		if ( ! isset( $cache[ $otype ][ $oid ] ) || ! is_array( $cache[ $otype ][ $oid ] ) ) {
			$cache[ $otype ][ $oid ] = array();
		}

		if ( ! isset( $cache[ $otype ][ $oid ][ $okey ] ) || ! is_array( $cache[ $otype ][ $oid ][ $okey ] ) ) {
			$cache[ $otype ][ $oid ][ $okey ] = array();
		}

		// Add to the cache array.
		$cache[ $otype ][ $oid ][ $okey ][] = maybe_unserialize( $ovalue );
	}

	// Loop through data and cache to the appropriate object.
	foreach ( $cache as $object_type => $object_caches ) {

		// Determine the cache group for this data.
		$cache_group = $cache_groups[ $object_type ];

		// Loop through objects and cache appropriately.
		foreach ( $object_caches as $object_id => $object_cache ) {
			wp_cache_set( $object_id, $object_cache, $cache_group );
		}
	}
}

/**
 * Clear cached XProfile field group data.
 *
 * @since 2.1.0
 *
 * @param object $group_obj Groub object to clear.
 */
function profiles_xprofile_clear_profile_groups_object_cache( $group_obj ) {
	wp_cache_delete( 'all',          'profiles_xprofile_groups' );
	wp_cache_delete( $group_obj->id, 'profiles_xprofile_groups' );
}
add_action( 'xprofile_group_after_delete', 'profiles_xprofile_clear_profile_groups_object_cache' );
add_action( 'xprofile_group_after_save',   'profiles_xprofile_clear_profile_groups_object_cache' );

/**
 * Clear cached XProfile fullname data for user.
 *
 * @since 2.1.0
 *
 * @param int $user_id ID of user whose fullname cache to delete.
 */
function profiles_xprofile_clear_profile_data_object_cache( $user_id = 0 ) {
	wp_cache_delete( 'profiles_user_fullname_' . $user_id, 'profiles' );
}
add_action( 'xprofile_updated_profile', 'profiles_xprofile_clear_profile_data_object_cache' );

/**
 * Clear the fullname cache when field 1 is updated.
 *
 * The xprofile_clear_profile_data_object_cache() will make this redundant in most
 * cases, except where the field is updated directly with profiles_xprofile_set_field_data().
 *
 * @since 2.0.0
 *
 * @param object $data Data object to clear.
 */
function profiles_xprofile_clear_fullname_cache_on_profile_field_edit( $data ) {
	if ( 1 == $data->field_id ) {
		wp_cache_delete( 'profiles_user_fullname_' . $data->user_id, 'profiles' );
	}
}
add_action( 'xprofile_data_after_save', 'profiles_xprofile_clear_fullname_cache_on_profile_field_edit' );

/**
 * Clear caches when a field object is modified.
 *
 * @since 2.0.0
 *
 * @param Profiles_XProfile_Field $field_obj Field object cache to delete.
 */
function profiles_xprofile_clear_profile_field_object_cache( $field_obj ) {

	// Clear default visibility level cache.
	wp_cache_delete( 'default_visibility_levels', 'profiles_xprofile' );

	// Modified fields can alter parent group status, in particular when
	// the group goes from empty to non-empty. Bust its cache, as well as
	// the global 'all' cache.
	wp_cache_delete( 'all',                'profiles_xprofile_groups' );
	wp_cache_delete( $field_obj->group_id, 'profiles_xprofile_groups' );
}
add_action( 'xprofile_fields_saved_field',   'profiles_xprofile_clear_profile_field_object_cache' );
add_action( 'xprofile_fields_deleted_field', 'profiles_xprofile_clear_profile_field_object_cache' );

/**
 * Clears member_type cache when a field's member types are updated.
 *
 * @since 2.4.0
 */
function profiles_xprofile_clear_member_type_cache() {
	wp_cache_delete( 'field_member_types', 'profiles_xprofile' );
}
add_action( 'profiles_xprofile_field_set_member_type', 'profiles_xprofile_clear_member_type_cache' );

/**
 * Clear caches when a user's updates a field data object.
 *
 * @since 2.0.0
 *
 * @param Profiles_XProfile_ProfileData $data_obj Field data object to delete.
 */
function profiles_xprofile_clear_profiledata_object_cache( $data_obj ) {
	wp_cache_delete( "{$data_obj->user_id}:{$data_obj->field_id}", 'profiles_xprofile_data' );
}
add_action( 'xprofile_data_after_save',   'profiles_xprofile_clear_profiledata_object_cache' );
add_action( 'xprofile_data_after_delete', 'profiles_xprofile_clear_profiledata_object_cache' );

/**
 * Clear fullname_field_id cache when profiles-xprofile-fullname-field-name is updated.
 *
 * Note for future developers: Dating from an early version of Profiles where
 * the fullname field (field #1) did not have a title that was editable in the
 * normal Profile Fields admin interface, we have the profiles-xprofile-fullname-field-name
 * option. In many places throughout Profiles, the ID of the fullname field
 * is queried using this setting. However, this is no longer strictly necessary,
 * because we essentially hardcode (in the xprofile admin save routine, as well
 * as the xprofile schema definition) that the fullname field will be 1. The
 * presence of the non-hardcoded versions (and thus this bit of cache
 * invalidation) is thus for backward compatibility only.
 *
 * @since 2.0.0
 */
function profiles_xprofile_clear_fullname_field_id_cache() {
	wp_cache_delete( 'fullname_field_id', 'profiles_xprofile' );
}
add_action( 'update_option_profiles-xprofile-fullname-field-name', 'profiles_xprofile_clear_fullname_field_id_cache' );

/**
 * Clear a field's caches.
 *
 * @since 2.4.0
 *
 * @param int|Profiles_XProfile_Field $field A field ID or a field object.
 * @return bool False on failure.
 */
function profiles_xprofile_clear_field_cache( $field ) {
	if ( is_numeric( $field ) ) {
		$field_id = (int) $field;
	} elseif ( $field instanceof Profiles_XProfile_Field ) {
		$field_id = (int) $field->id;
	}

	if ( ! isset( $field_id ) ) {
		return false;
	}

	wp_cache_delete( $field_id, 'profiles_xprofile_fields' );
	wp_cache_delete( $field_id, 'xprofile_meta' );
}
add_action( 'xprofile_field_after_save', 'profiles_xprofile_clear_field_cache' );

// List actions to clear super cached pages on, if super cache is installed.
add_action( 'xprofile_updated_profile', 'profiles_core_clear_cache' );
