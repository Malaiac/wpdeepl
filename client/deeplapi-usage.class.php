<?php

class DeepLApiUsage extends DeepLApi {
	protected $endPoint 	= 'usage';
 	public $allow_cache 	= false;
 	protected $http_mode 	= 'GET';

 	public function getCurrentCharacterCount() {
		if( !isset( $this->response ) || !array_key_exists( 'character_count', $this->response ) ) {
			return false;
		}
		return $this->response['character_count'];
	}

	public function getCharacterLimit() {
 		if( !isset( $this->response ) || !array_key_exists( 'character_limit', $this->response ) ) {
 			return false;
 		}
 		return $this->response['character_limit'];
 	}
}