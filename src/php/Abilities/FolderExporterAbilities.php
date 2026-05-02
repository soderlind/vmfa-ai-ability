<?php
/**
 * Folder Exporter ability registrations.
 *
 * Active only when vmfa-folder-exporter is loaded (VMFA_FOLDER_EXPORTER_VERSION defined).
 *
 * @package VMFAAiAbility\Abilities
 */

declare(strict_types=1);

namespace VMFAAiAbility\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers MCP abilities for the Virtual Media Folders – Folder Exporter add-on.
 *
 * REST namespace: vmfa-folder-exporter/v1
 */
final class FolderExporterAbilities extends AbstractAbilities {

	private const CATEGORY_SLUG = 'vmfo-folder-exporter';

	/**
	 * Register the ability category.
	 */
	public static function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY_SLUG,
			[
				'label'       => __( 'Folder Exporter', 'vmfa-ai-ability' ),
				'description' => __( 'Abilities for exporting media folders as ZIP archives.', 'vmfa-ai-ability' ),
			]
		);
	}

	/**
	 * Register all Folder Exporter abilities.
	 */
	public static function register(): void {
		self::register_start_export();
		self::register_get_export_status();
		self::register_list_exports();
		self::register_delete_export();
	}

	// -----------------------------------------------------------------------
	// Ability registrations
	// -----------------------------------------------------------------------

	private static function register_start_export(): void {
		wp_register_ability(
			'vmfo-folder-exporter/start-export',
			[
				'label'               => __( 'Start Folder Export', 'vmfa-ai-ability' ),
				'description'         => __( 'Starts an asynchronous ZIP export of a folder and its optional sub-folders.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'folder_id'          => [
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'Folder term ID to export.', 'vmfa-ai-ability' ),
						],
						'include_children'   => [
							'type'        => 'boolean',
							'default'     => true,
							'description' => __( 'Include sub-folder contents in the export.', 'vmfa-ai-ability' ),
						],
						'include_manifest'   => [
							'type'        => 'boolean',
							'default'     => true,
							'description' => __( 'Include a CSV manifest in the ZIP.', 'vmfa-ai-ability' ),
						],
					],
					'required'             => [ 'folder_id' ],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_start_export' ],
				'permission_callback' => [ self::class, 'require_upload' ],
				'meta'                => self::mcp_meta( readonly: false, idempotent: false ),
			]
		);
	}

	private static function register_get_export_status(): void {
		wp_register_ability(
			'vmfo-folder-exporter/get-export-status',
			[
				'label'               => __( 'Get Export Status', 'vmfa-ai-ability' ),
				'description'         => __( 'Returns the status and metadata of a folder export job.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'job_id' => [
							'type'        => 'string',
							'description' => __( 'The export job ID returned by start-export.', 'vmfa-ai-ability' ),
						],
					],
					'required'             => [ 'job_id' ],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_get_export_status' ],
				'permission_callback' => [ self::class, 'require_upload' ],
				'meta'                => self::mcp_meta( readonly: true, idempotent: true ),
			]
		);
	}

	private static function register_list_exports(): void {
		wp_register_ability(
			'vmfo-folder-exporter/list-exports',
			[
				'label'               => __( 'List Exports', 'vmfa-ai-ability' ),
				'description'         => __( 'Returns all recent folder export jobs.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_list_exports' ],
				'permission_callback' => [ self::class, 'require_upload' ],
				'meta'                => self::mcp_meta( readonly: true, idempotent: true ),
			]
		);
	}

	private static function register_delete_export(): void {
		wp_register_ability(
			'vmfo-folder-exporter/delete-export',
			[
				'label'               => __( 'Delete Export', 'vmfa-ai-ability' ),
				'description'         => __( 'Deletes an export job and its associated ZIP file.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'job_id' => [
							'type'        => 'string',
							'description' => __( 'The export job ID to delete.', 'vmfa-ai-ability' ),
						],
					],
					'required'             => [ 'job_id' ],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_delete_export' ],
				'permission_callback' => [ self::class, 'require_upload' ],
				'meta'                => self::mcp_meta( readonly: false, idempotent: false, destructive: true ),
			]
		);
	}

	// -----------------------------------------------------------------------
	// Execute callbacks
	// -----------------------------------------------------------------------

	/** @param array<string, mixed> $input */
	public static function execute_start_export( array $input ): array|\WP_Error {
		return self::rest_request( 'POST', '/vmfa-folder-exporter/v1/exports', $input );
	}

	/** @param array<string, mixed> $input */
	public static function execute_get_export_status( array $input ): array|\WP_Error {
		$job_id = (string) ( $input['job_id'] ?? '' );

		if ( $job_id === '' ) {
			return new \WP_Error( 'ability_invalid_input', __( 'A valid job_id is required.', 'vmfa-ai-ability' ) );
		}

		return self::rest_request( 'GET', '/vmfa-folder-exporter/v1/exports/' . rawurlencode( $job_id ) );
	}

	/** @param array<string, mixed> $input */
	public static function execute_list_exports( array $input ): array|\WP_Error {
		return self::rest_request( 'GET', '/vmfa-folder-exporter/v1/exports' );
	}

	/** @param array<string, mixed> $input */
	public static function execute_delete_export( array $input ): array|\WP_Error {
		$job_id = (string) ( $input['job_id'] ?? '' );

		if ( $job_id === '' ) {
			return new \WP_Error( 'ability_invalid_input', __( 'A valid job_id is required.', 'vmfa-ai-ability' ) );
		}

		return self::rest_request( 'DELETE', '/vmfa-folder-exporter/v1/exports/' . rawurlencode( $job_id ) );
	}
}
