<?php

namespace Cookie_Setting_Yes;

defined( 'ABSPATH' ) || exit;

/** Blocks selected services when their code is printed directly in wp_footer. */
final class Raw_Script_Blocker {
	private $options;
	private $service_catalog;
	private $buffering = false;

	public function __construct( Options $options, Service_Catalog $service_catalog ) {
		$this->options         = $options;
		$this->service_catalog = $service_catalog;
		add_action( 'wp_footer', array( $this, 'start_buffer' ), 98 );
		add_action( 'wp_footer', array( $this, 'finish_buffer' ), PHP_INT_MAX );
	}

	public function start_buffer() {
		if ( is_admin() || empty( $this->options->get( 'enabled' ) ) || empty( $this->patterns() ) ) {
			return;
		}

		ob_start();
		$this->buffering = true;
	}

	public function finish_buffer() {
		if ( ! $this->buffering ) {
			return;
		}

		$this->buffering = false;
		$output          = ob_get_clean();
		if ( false === $output ) {
			return;
		}

		echo $this->block_raw_scripts( $output ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Existing footer markup is preserved, only script types are changed.
	}

	private function block_raw_scripts( $output ) {
		return preg_replace_callback(
			'#(<script\b([^>]*)>.*?</script\s*>)(\s*<noscript\b[^>]*>.*?</noscript\s*>)?#is',
			array( $this, 'block_match' ),
			$output
		);
	}

	private function block_match( $match ) {
		$script     = $match[1];
		$attributes = $match[2];
		$noscript   = $match[3] ?? '';
		$category   = $this->category_for( $script );

		if ( ! $category || $this->is_already_blocked( $attributes ) || $this->has_consent( $category ) ) {
			return $match[0];
		}

		$attributes = preg_replace( '/\s+(?:type|data-csy-category)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $attributes );
		$blocked    = '<script type="text/plain" data-csy-category="' . esc_attr( $category ) . '"' . $attributes . '>';
		$blocked   .= substr( $script, strpos( $script, '>' ) + 1 );

		// A no-JavaScript tracking pixel cannot wait for a visitor's consent, so suppress it too.
		if ( '' !== $noscript && $this->category_for( $noscript ) ) {
			$noscript = '<!-- Cookie Setting Yes: noscript tracker blocked until consent -->';
		}

		return $blocked . $noscript;
	}

	private function patterns() {
		return (array) $this->service_catalog->selected_raw_patterns( (array) $this->options->get( 'selected_services', array() ) );
	}

	private function category_for( $markup ) {
		$markup = strtolower( (string) $markup );
		foreach ( $this->patterns() as $pattern => $category ) {
			if ( '' !== $pattern && false !== strpos( $markup, strtolower( $pattern ) ) ) {
				return sanitize_key( $category );
			}
		}

		return '';
	}

	private function is_already_blocked( $attributes ) {
		return false !== stripos( $attributes, 'data-csy-category' ) || preg_match( '/\btype\s*=\s*(?:"text\/plain"|\'text\/plain\'|text\/plain)/i', $attributes );
	}

	private function has_consent( $category ) {
		$data = $this->options->consent_cookie();
		return is_array( $data ) && ! empty( $data['categories'][ $category ] ) && ! empty( $data['version'] ) && hash_equals( (string) $this->options->get( 'consent_version' ), (string) $data['version'] );
	}
}
