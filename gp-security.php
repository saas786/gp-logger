<?php

class GP_Security extends GP_Plugin {

	var $meta_key = 'security_warning';

	public function __construct() {
		parent::__construct();
		GP::$router->add( '/security', array( 'GP_Route_Security', 'security' ) );
		add_action( 'warning_discarded', array( $this, 'warning_discarded' ), 10, 5 );
	}

	function warning_discarded( $project, $translation_set, $translation, $tag, $user ){
		$meta_data = compact( 'translation', 'tag', 'user' );

		$meta_data['time'] = time();

		switch ( $tag ) {
			case 'urls': //TODO: implement in core GP, see https://glotpress.trac.wordpress.org/ticket/307
			case 'tags':
				$warnings = maybe_unserialize( $this->get_set_security_warning( $translation_set ) );
				if ( is_array( $warnings ) ) {
					$warnings[] = $meta_data;
				} else {
					$warnings = array( $meta_data );
				}
				gp_update_meta( $translation_set, $this->meta_key, $warnings, 'translation_set' );
				break;
		}

	}

	function get_set_security_warning( $set_id ) {
		return gp_get_meta( 'translation_set', $set_id, $this->meta_key );
	}

	function clear_security_warning( $set_id, $meta_data ) {
		return gp_delete_meta( $set_id, $this->meta_key, $meta_data );
	}

	function sets_with_warnings() {
		global $gpdb;
		//TODO: meta by object type and key method
		return $gpdb->get_results( $gpdb->prepare( "SELECT * FROM `$gpdb->meta` WHERE `object_type` = %s AND `meta_key` = %s", 'translation_set', $this->meta_key ) );
	}
}

GP::$plugins->security = new GP_Security;


class GP_Route_Security extends GP_Route_Main {

	function __construct() {
		$this->template_path = dirname( __FILE__ ) . '/templates/';
	}

	 function security() {
		$warnings = array();
		$sets = GP::$plugins->security->sets_with_warnings();
		foreach ( $sets as $set ) {
			$meta_data = unserialize( $set->meta_value );
			$_translation_set = GP::$translation_set->get( $set->object_id );
			$_path= GP::$project->get( $_translation_set->project_id )->path;
			foreach ( $meta_data as $w ) {
				$warning = new stdClass();
				$warning->time = $w['time'];
				$warning->translation_set = $_translation_set->locale .  '/'  . $_translation_set->slug;
				$warning->project = $_path;
				$warning->translation = GP::$translation->get( $w['translation'] )->translation_0;
				$warning->user = GP::$user->get( $w['user'] )->user_nicename;
				$warning->tag = $w['tag'];
				$warnings[] = $warning;
			}
		}

		$this->tmpl('security', get_defined_vars() );
	}
}