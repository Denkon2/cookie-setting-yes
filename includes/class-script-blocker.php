<?php

namespace Cookie_Setting_Yes;

defined( 'ABSPATH' ) || exit;

/** Blocks registered WordPress script handles until a category is accepted. */
final class Script_Blocker {
	private $options;
	private $service_catalog;

	public function __construct( Options $options, Service_Catalog $service_catalog ) {
		$this->options         = $options;
		$this->service_catalog = $service_catalog;
		add_filter( 'script_loader_tag', array( $this, 'block_script' ), 20, 3 );
	}

	public function block_script( $tag, $handle, $src ) {
		$handles = array_merge(
			$this->service_catalog->selected_handles( (array) $this->options->get( 'selected_services', array() ) ),
			(array) $this->options->get( 'script_handles', array() )
		);
		if ( empty( $handles[ $handle ] ) || 'necessary' === $handles[ $handle ] || $this->has_consent( $handles[ $handle ] ) ) {
			return $tag;
		}

		$category = esc_attr( sanitize_key( $handles[ $handle ] ) );
		return preg_replace( '/<script\b/i', '<script type="text/plain" data-csy-category="' . $category . '"', $tag, 1 );
	}

	private function has_consent( $category ) {
		$data = $this->options->consent_cookie();
		return is_array( $data ) && ! empty( $data['categories'][ $category ] ) && ! empty( $data['version'] ) && hash_equals( (string) $this->options->get( 'consent_version' ), (string) $data['version'] );
	}
}
