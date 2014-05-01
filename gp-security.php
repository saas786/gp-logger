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
				gp_update_meta( $translation_set, $this->meta_key, $meta_data, 'translation_set' );
				break;

		}

	}

	function has_security_warning( $set_id ) {
		return gp_get_meta( $set_id, 'translation_set', $this->meta_key );
	}

	function clear_security_warning( $set_id, $meta_data ) {
		return gp_delete_meta( $set_id, 'security_warning', $meta_data );
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
		$sets = GP::$plugins->security->sets_with_warnings();
		foreach ( $sets as $set ) {
			$meta_data = unserialize( $set->meta_value );
			$warning = new stdClass();
			$warning->time = $meta_data['time'];
			$_translation_set = GP::$translation_set->get( $set->object_id );
			$warning->translation_set = $_translation_set->locale .  '/'  . $_translation_set->slug;
			$warning->project = GP::$project->get( $_translation_set->project_id )->path;
			$warning->translation = GP::$translation->get( $meta_data['translation'] )->translation_0;
			$warning->user = GP::$user->get( $meta_data['user'] )->user_nicename;
			$warning->tag = $meta_data['tag'];
			$warnings[$set->object_id] = $warning;
		}

		$this->tmpl('security', get_defined_vars() );
	}
}