<?php
/**
 * Plugin Name:       Virtual Media Folders - AI Ability
 * Plugin URI:        https://github.com/soderlind/vmfa-ai-ability
 * Description:       Exposes Virtual Media Folders operations as WordPress Abilities API tools for AI agents and MCP adapters. Add-on for Virtual Media Folders.
 * Version:           1.1.0
 * Requires at least: 6.8
 * Requires PHP:      8.3
 * Requires Plugins:  virtual-media-folders
 * Author:            Per Soderlind
 * Author URI:        https://soderlind.no
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       vmfa-ai-ability
 * Domain Path:       /languages
 *
 * @package VMFAAiAbility
 */

declare(strict_types=1);

namespace VMFAAiAbility;

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'VMFA_AI_ABILITY_VERSION', '1.1.0' );
define( 'VMFA_AI_ABILITY_FILE', __FILE__ );
define( 'VMFA_AI_ABILITY_PATH', plugin_dir_path( __FILE__ ) );
define( 'VMFA_AI_ABILITY_URL', plugin_dir_url( __FILE__ ) );
define( 'VMFA_AI_ABILITY_BASENAME', plugin_basename( __FILE__ ) );

// Require Composer autoloader.
if ( file_exists( VMFA_AI_ABILITY_PATH . 'vendor/autoload.php' ) ) {
	require_once VMFA_AI_ABILITY_PATH . 'vendor/autoload.php';
}

// Update checker via GitHub releases.
if ( ! class_exists( \Soderlind\WordPress\GitHubUpdater::class ) ) {
	require_once __DIR__ . '/class-github-updater.php';
}
\Soderlind\WordPress\GitHubUpdater::init(
	github_url: 'https://github.com/soderlind/vmfa-ai-ability',
	plugin_file: VMFA_AI_ABILITY_FILE,
	plugin_slug: 'vmfa-ai-ability',
	name_regex: '/vmfa-ai-ability\.zip/',
	branch: 'main',
);

/**
 * Initialize the plugin.
 *
 * @return void
 */
function init(): void {
	// Boot the plugin.
	Plugin::get_instance()->init();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init', 15 );
