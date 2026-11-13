<?php

namespace Cookie_Setting_Yes;

defined( 'ABSPATH' ) || exit;

/** Defers native WordPress oEmbed iframes until external content is accepted. */
final class Iframe_Blocker {
	private $options;

	public function __construct( Options $options ) {
		$this->options = $options;
		add_filter( 'embed_oembed_html', array( $this, 'block_oembed' ), 20, 4 );
	}

	public function block_oembed( $html, $url, $attr, $post_id ) {
		if ( $this->has_consent() || false === stripos( $html, '<iframe' ) ) {
			return $html;
		}

		$blocked = preg_replace( '/\ssrc=("|\')(.*?)\1/i', ' data-csy-src=$1$2$1', $html, 1 );
		if ( null === $blocked || $blocked === $html ) {
			return $html;
		}

		return '<div class="csy-embed-placeholder" data-csy-embed>' . $blocked . '<button type="button" class="csy-button csy-embed-placeholder__button csy-open-preferences">' . esc_html__( 'Allow external content', 'cookie-setting-yes' ) . '</button></div>';
	}

	private function has_consent() {
		$data = $this->options->consent_cookie();
		return is_array( $data ) && ! empty( $data['categories']['external'] ) && ! empty( $data['version'] ) && hash_equals( (string) $this->options->get( 'consent_version' ), (string) $data['version'] );
	}
}
