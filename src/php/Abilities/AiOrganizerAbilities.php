<?php
/**
 * AI Organizer ability registrations.
 *
 * Active only when vmfa-ai-organizer is loaded (VMFA_AI_ORGANIZER_VERSION defined).
 *
 * @package VMFAAiAbility\Abilities
 */

declare(strict_types=1);

namespace VMFAAiAbility\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers MCP abilities for the Virtual Media Folders – AI Organizer add-on.
 *
 * REST namespace: vmfa/v1  (scan endpoints registered by AI Organizer on the shared namespace).
 */
final class AiOrganizerAbilities extends AbstractAbilities {

	private const CATEGORY_SLUG = 'vmfo-ai-organizer';

	/**
	 * Register the ability category.
	 */
	public static function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY_SLUG,
			[
				'label'       => __( 'AI Organizer', 'vmfa-ai-ability' ),
				'description' => __( 'Abilities for AI-powered batch media organisation.', 'vmfa-ai-ability' ),
			]
		);
	}

	/**
	 * Register all AI Organizer abilities.
	 */
	public static function register(): void {
		self::register_start_scan();
		self::register_get_scan_status();
		self::register_cancel_scan();
	}

	// -----------------------------------------------------------------------
	// Ability registrations
	// -----------------------------------------------------------------------

	private static function register_start_scan(): void {
		wp_register_ability(
			'vmfo-ai-organizer/start-scan',
			[
				'label'               => __( 'Start AI Organizer Scan', 'vmfa-ai-ability' ),
				'description'         => __( 'Starts an AI-powered scan that analyses unassigned media and suggests or applies folder assignments.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'mode'    => [
							'type'        => 'string',
							'description' => __( 'Scan mode: "analyze" to preview suggestions, "apply" to assign folders immediately.', 'vmfa-ai-ability' ),
						],
						'dry_run' => [
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'When true, analyse media but do not persist folder assignments.', 'vmfa-ai-ability' ),
						],
					],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_start_scan' ],
				'permission_callback' => [ self::class, 'require_manage_options' ],
				'meta'                => self::mcp_meta( readonly: false, idempotent: false ),
			]
		);
	}

	private static function register_get_scan_status(): void {
		wp_register_ability(
			'vmfo-ai-organizer/get-scan-status',
			[
				'label'               => __( 'Get AI Organizer Scan Status', 'vmfa-ai-ability' ),
				'description'         => __( 'Returns the current progress of the running AI Organizer scan.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_get_scan_status' ],
				'permission_callback' => [ self::class, 'require_upload' ],
				'meta'                => self::mcp_meta( readonly: true, idempotent: true ),
			]
		);
	}

	private static function register_cancel_scan(): void {
		wp_register_ability(
			'vmfo-ai-organizer/cancel-scan',
			[
				'label'               => __( 'Cancel AI Organizer Scan', 'vmfa-ai-ability' ),
				'description'         => __( 'Cancels the currently running AI Organizer scan.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_cancel_scan' ],
				'permission_callback' => [ self::class, 'require_manage_options' ],
				'meta'                => self::mcp_meta( readonly: false, idempotent: true ),
			]
		);
	}

	// -----------------------------------------------------------------------
	// Execute callbacks
	// -----------------------------------------------------------------------

	/** @param array<string, mixed> $input */
	public static function execute_start_scan( array $input ): array|\WP_Error {
		return self::rest_request( 'POST', '/vmfa/v1/scan', $input );
	}

	/** @param array<string, mixed> $input */
	public static function execute_get_scan_status( array $input ): array|\WP_Error {
		return self::rest_request( 'GET', '/vmfa/v1/scan/status' );
	}

	/** @param array<string, mixed> $input */
	public static function execute_cancel_scan( array $input ): array|\WP_Error {
		return self::rest_request( 'POST', '/vmfa/v1/scan/cancel' );
	}
}
