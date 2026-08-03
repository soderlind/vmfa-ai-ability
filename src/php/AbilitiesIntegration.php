<?php
/**
 * WordPress Abilities API integration — orchestrator.
 *
 * Delegates category and ability registration to per-plugin Abilities classes.
 * Add-on classes are only loaded when their plugin is active (detected via
 * the constant each add-on defines during bootstrap).
 *
 * @package VMFAAiAbility
 */

declare(strict_types=1);

namespace VMFAAiAbility;

defined( 'ABSPATH' ) || exit;

use VMFAAiAbility\Abilities\AiOrganizerAbilities;
use VMFAAiAbility\Abilities\BaseFolderAbilities;
use VMFAAiAbility\Abilities\FolderExporterAbilities;
use VMFAAiAbility\Abilities\MediaCleanupAbilities;
use VMFAAiAbility\Abilities\RulesEngineAbilities;

/**
 * Orchestrates Abilities API registration for all active VMFA plugins.
 *
 * Category and ability registration is delegated to per-plugin Abilities classes
 * under the VMFAAiAbility\Abilities namespace. Add-on classes are only invoked
 * when the corresponding plugin constant is defined (set during plugin bootstrap).
 */
final class AbilitiesIntegration {

	/**
	 * Initialize abilities hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wp_abilities_api_categories_init', [ self::class, 'register_categories' ] );
		add_action( 'wp_abilities_api_init', [ self::class, 'register_abilities' ] );

		// WP 7.1: fires for every ability invocation (incl. denied/short-circuited).
		// Harmless on earlier versions — the hook simply never fires.
		add_action( 'wp_ability_invoked', [ self::class, 'record_invocation' ], 10, 3 );

		// Give every schema property a Title Case `title` for REST/MCP/AI clients
		// (WP 7.1 schema convention). Centralized so all abilities stay consistent.
		add_filter( 'wp_register_ability_args', [ self::class, 'add_schema_titles' ], 10, 2 );
	}

	/**
	 * Inject a humanized Title Case `title` into each schema property that lacks one.
	 *
	 * Runs on `wp_register_ability_args` for VMFA abilities only. Any explicitly
	 * provided `title` is preserved. Derived titles are English (not translated);
	 * translatable `description` values remain the primary human-readable metadata.
	 *
	 * @param array<string, mixed> $args Ability registration args.
	 * @param string               $name Ability name.
	 * @return array<string, mixed>
	 */
	public static function add_schema_titles( array $args, string $name ): array {
		if ( ! str_starts_with( $name, 'vmfo/' ) && ! str_starts_with( $name, 'vmfo-' ) ) {
			return $args;
		}

		foreach ( [ 'input_schema', 'output_schema' ] as $key ) {
			if ( isset( $args[ $key ] ) && is_array( $args[ $key ] ) ) {
				$args[ $key ] = self::title_schema( $args[ $key ] );
			}
		}

		return $args;
	}

	/**
	 * Recursively add a `title` to every property node of a JSON Schema fragment.
	 *
	 * @param array<string, mixed> $schema Schema fragment.
	 * @return array<string, mixed>
	 */
	private static function title_schema( array $schema ): array {
		if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) ) {
			foreach ( $schema['properties'] as $prop => $definition ) {
				if ( ! is_array( $definition ) ) {
					continue;
				}
				if ( ! isset( $definition['title'] ) ) {
					$definition['title'] = self::humanize_key( (string) $prop );
				}
				$schema['properties'][ $prop ] = self::title_schema( $definition );
			}
		}

		// Recurse into array item schemas.
		if ( isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
			$schema['items'] = self::title_schema( $schema['items'] );
		}

		return $schema;
	}

	/**
	 * Convert a snake/kebab-case key into a Title Case label (e.g. "folder_id" → "Folder ID").
	 *
	 * @param string $key Property key.
	 * @return string
	 */
	private static function humanize_key( string $key ): string {
		$label = ucwords( str_replace( [ '_', '-' ], ' ', $key ) );

		// Normalize common initialisms.
		return preg_replace(
			[ '/\bIds\b/', '/\bId\b/', '/\bUrl\b/', '/\bUri\b/', '/\bApi\b/' ],
			[ 'IDs', 'ID', 'URL', 'URI', 'API' ],
			$label
		);
	}

	/**
	 * Re-dispatch a safe audit event for each ability invocation.
	 *
	 * Runs on the WP 7.1 `wp_ability_invoked` action. Only VMFA-owned abilities
	 * are reported. Raw input is deliberately omitted because it may contain
	 * sensitive data; integrators opt in by listening for `vmfa_ai_ability_invoked`.
	 *
	 * @param string $ability_name Fully-qualified ability name.
	 * @param mixed  $input        Raw, unnormalized input (intentionally not forwarded).
	 * @param mixed  $ability      The WP_Ability instance.
	 * @return void
	 */
	public static function record_invocation( string $ability_name, $input, $ability = null ): void {
		unset( $input, $ability );

		// All VMFA abilities are namespaced vmfo/ or vmfo-<addon>/.
		if ( ! str_starts_with( $ability_name, 'vmfo/' ) && ! str_starts_with( $ability_name, 'vmfo-' ) ) {
			return;
		}

		/**
		 * Fires when a VMFA ability is invoked, for auditing/telemetry.
		 *
		 * @param array<string, mixed> $event {
		 *     @type string $ability   Ability name.
		 *     @type int    $user_id   Current user ID (0 if none).
		 *     @type int    $timestamp Unix timestamp of the invocation.
		 * }
		 */
		do_action(
			'vmfa_ai_ability_invoked',
			[
				'ability'   => $ability_name,
				'user_id'   => get_current_user_id(),
				'timestamp' => time(),
			]
		);
	}

	/**
	 * Register ability categories for all active plugins.
	 *
	 * @return void
	 */
	public static function register_categories(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		// Base plugin (always active — it is a hard dependency of this plugin).
		BaseFolderAbilities::register_category();

		// Add-ons: only register their category when the add-on is active.
		if ( defined( 'VMFA_RULES_ENGINE_VERSION' ) ) {
			RulesEngineAbilities::register_category();
		}

		if ( defined( 'VMFA_MEDIA_CLEANUP_VERSION' ) ) {
			MediaCleanupAbilities::register_category();
		}

		if ( defined( 'VMFA_FOLDER_EXPORTER_VERSION' ) ) {
			FolderExporterAbilities::register_category();
		}

		if ( defined( 'VMFA_AI_ORGANIZER_VERSION' ) ) {
			AiOrganizerAbilities::register_category();
		}
	}

	/**
	 * Register abilities for all active plugins.
	 *
	 * @return void
	 */
	public static function register_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		// Base plugin (always active).
		BaseFolderAbilities::register();

		// Add-ons: only register when the add-on is active.
		if ( defined( 'VMFA_RULES_ENGINE_VERSION' ) ) {
			RulesEngineAbilities::register();
		}

		if ( defined( 'VMFA_MEDIA_CLEANUP_VERSION' ) ) {
			MediaCleanupAbilities::register();
		}

		if ( defined( 'VMFA_FOLDER_EXPORTER_VERSION' ) ) {
			FolderExporterAbilities::register();
		}

		if ( defined( 'VMFA_AI_ORGANIZER_VERSION' ) ) {
			AiOrganizerAbilities::register();
		}
	}
}

