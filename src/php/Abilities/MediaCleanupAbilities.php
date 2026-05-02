<?php
/**
 * Media Cleanup ability registrations.
 *
 * Active only when vmfa-media-cleanup is loaded (VMFA_MEDIA_CLEANUP_VERSION defined).
 *
 * @package VMFAAiAbility\Abilities
 */

declare(strict_types=1);

namespace VMFAAiAbility\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers MCP abilities for the Virtual Media Folders – Media Cleanup add-on.
 *
 * REST namespace: vmfa-cleanup/v1
 */
final class MediaCleanupAbilities extends AbstractAbilities {

	private const CATEGORY_SLUG = 'vmfo-media-cleanup';

	/**
	 * Register the ability category.
	 */
	public static function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY_SLUG,
			[
				'label'       => __( 'Media Cleanup', 'vmfa-ai-ability' ),
				'description' => __( 'Abilities for detecting and managing unused, duplicate, and oversized media.', 'vmfa-ai-ability' ),
			]
		);
	}

	/**
	 * Register all Media Cleanup abilities.
	 */
	public static function register(): void {
		self::register_start_scan();
		self::register_get_scan_status();
		self::register_cancel_scan();
		self::register_get_stats();
		self::register_list_results();
		self::register_archive();
		self::register_trash();
		self::register_delete();
	}

	// -----------------------------------------------------------------------
	// Ability registrations
	// -----------------------------------------------------------------------

	private static function register_start_scan(): void {
		wp_register_ability(
			'vmfo-cleanup/start-scan',
			[
				'label'               => __( 'Start Media Cleanup Scan', 'vmfa-ai-ability' ),
				'description'         => __( 'Starts a background scan for unused, duplicate, or oversized media.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'types' => [
							'type'        => 'array',
							'items'       => [
								'type' => 'string',
								'enum' => [ 'unused', 'duplicate', 'oversized' ],
							],
							'description' => __( 'Scan types to run. Defaults to all types.', 'vmfa-ai-ability' ),
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
			'vmfo-cleanup/get-scan-status',
			[
				'label'               => __( 'Get Cleanup Scan Status', 'vmfa-ai-ability' ),
				'description'         => __( 'Returns the current progress of the running cleanup scan.', 'vmfa-ai-ability' ),
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
			'vmfo-cleanup/cancel-scan',
			[
				'label'               => __( 'Cancel Cleanup Scan', 'vmfa-ai-ability' ),
				'description'         => __( 'Cancels the currently running cleanup scan.', 'vmfa-ai-ability' ),
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

	private static function register_get_stats(): void {
		wp_register_ability(
			'vmfo-cleanup/get-stats',
			[
				'label'               => __( 'Get Cleanup Stats', 'vmfa-ai-ability' ),
				'description'         => __( 'Returns dashboard statistics for unused, duplicate, and oversized media.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_get_stats' ],
				'permission_callback' => [ self::class, 'require_upload' ],
				'meta'                => self::mcp_meta( readonly: true, idempotent: true ),
			]
		);
	}

	private static function register_list_results(): void {
		wp_register_ability(
			'vmfo-cleanup/list-results',
			[
				'label'               => __( 'List Cleanup Results', 'vmfa-ai-ability' ),
				'description'         => __( 'Returns paginated scan results filterable by type.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'type'     => [
							'type'        => 'string',
							'enum'        => [ 'unused', 'duplicate', 'oversized', 'flagged', 'trash' ],
							'description' => __( 'Filter results by this type.', 'vmfa-ai-ability' ),
						],
						'page'     => [
							'type'        => 'integer',
							'minimum'     => 1,
							'default'     => 1,
							'description' => __( 'Page number.', 'vmfa-ai-ability' ),
						],
						'per_page' => [
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => 100,
							'default'     => 20,
							'description' => __( 'Items per page.', 'vmfa-ai-ability' ),
						],
					],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_list_results' ],
				'permission_callback' => [ self::class, 'require_upload' ],
				'meta'                => self::mcp_meta( readonly: true, idempotent: true ),
			]
		);
	}

	private static function register_archive(): void {
		wp_register_ability(
			'vmfo-cleanup/archive',
			[
				'label'               => __( 'Archive Media', 'vmfa-ai-ability' ),
				'description'         => __( 'Moves media items to an archive folder.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'attachment_ids' => [
							'type'        => 'array',
							'minItems'    => 1,
							'uniqueItems' => true,
							'items'       => [ 'type' => 'integer', 'minimum' => 1 ],
							'description' => __( 'Attachment IDs to archive.', 'vmfa-ai-ability' ),
						],
					],
					'required'             => [ 'attachment_ids' ],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_archive' ],
				'permission_callback' => [ self::class, 'require_manage_categories' ],
				'meta'                => self::mcp_meta( readonly: false, idempotent: true ),
			]
		);
	}

	private static function register_trash(): void {
		wp_register_ability(
			'vmfo-cleanup/trash',
			[
				'label'               => __( 'Trash Media', 'vmfa-ai-ability' ),
				'description'         => __( 'Moves media items to the WordPress trash. Can be restored.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'attachment_ids' => [
							'type'        => 'array',
							'minItems'    => 1,
							'uniqueItems' => true,
							'items'       => [ 'type' => 'integer', 'minimum' => 1 ],
							'description' => __( 'Attachment IDs to trash.', 'vmfa-ai-ability' ),
						],
					],
					'required'             => [ 'attachment_ids' ],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_trash' ],
				'permission_callback' => [ self::class, 'require_manage_options' ],
				'meta'                => self::mcp_meta( readonly: false, idempotent: true, destructive: true ),
			]
		);
	}

	private static function register_delete(): void {
		wp_register_ability(
			'vmfo-cleanup/delete',
			[
				'label'               => __( 'Permanently Delete Media', 'vmfa-ai-ability' ),
				'description'         => __( 'Permanently deletes media items and their files from disk. This action cannot be undone.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'attachment_ids' => [
							'type'        => 'array',
							'minItems'    => 1,
							'uniqueItems' => true,
							'items'       => [ 'type' => 'integer', 'minimum' => 1 ],
							'description' => __( 'Attachment IDs to permanently delete.', 'vmfa-ai-ability' ),
						],
					],
					'required'             => [ 'attachment_ids' ],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_delete' ],
				'permission_callback' => [ self::class, 'require_manage_options' ],
				'meta'                => self::mcp_meta( readonly: false, idempotent: false, destructive: true ),
			]
		);
	}

	// -----------------------------------------------------------------------
	// Execute callbacks
	// -----------------------------------------------------------------------

	/** @param array<string, mixed> $input */
	public static function execute_start_scan( array $input ): array|\WP_Error {
		return self::rest_request( 'POST', '/vmfa-cleanup/v1/scan', $input );
	}

	/** @param array<string, mixed> $input */
	public static function execute_get_scan_status( array $input ): array|\WP_Error {
		return self::rest_request( 'GET', '/vmfa-cleanup/v1/scan/status' );
	}

	/** @param array<string, mixed> $input */
	public static function execute_cancel_scan( array $input ): array|\WP_Error {
		return self::rest_request( 'POST', '/vmfa-cleanup/v1/scan/cancel' );
	}

	/** @param array<string, mixed> $input */
	public static function execute_get_stats( array $input ): array|\WP_Error {
		return self::rest_request( 'GET', '/vmfa-cleanup/v1/stats' );
	}

	/** @param array<string, mixed> $input */
	public static function execute_list_results( array $input ): array|\WP_Error {
		$params = array_filter( [
			'type'     => $input['type'] ?? null,
			'page'     => $input['page'] ?? null,
			'per_page' => $input['per_page'] ?? null,
		], static fn( mixed $v ): bool => $v !== null );

		return self::rest_request( 'GET', '/vmfa-cleanup/v1/results', $params );
	}

	/** @param array<string, mixed> $input */
	public static function execute_archive( array $input ): array|\WP_Error {
		return self::rest_request( 'POST', '/vmfa-cleanup/v1/actions/archive', [
			'ids' => $input['attachment_ids'] ?? [],
		] );
	}

	/** @param array<string, mixed> $input */
	public static function execute_trash( array $input ): array|\WP_Error {
		return self::rest_request( 'POST', '/vmfa-cleanup/v1/actions/trash', [
			'ids' => $input['attachment_ids'] ?? [],
		] );
	}

	/** @param array<string, mixed> $input */
	public static function execute_delete( array $input ): array|\WP_Error {
		return self::rest_request( 'POST', '/vmfa-cleanup/v1/actions/delete', [
			'ids' => $input['attachment_ids'] ?? [],
		] );
	}
}
