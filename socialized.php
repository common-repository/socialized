<?php

/**
 * Plugin Name: Socialized
 * Description: Add social media sharing buttons to your posts, pages, and custom post types that automatically track to a custom campaign with your Google Analytics!
 * Version: 4.0.0
 * Author: AuRise Creative
 * Author URI: https://aurisecreative.com/
 * Plugin URI: https://aurisecreative.com/socialized/
 * License: GPL v3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 5.8
 * Requires PHP: 5.6.20
 * Text Domain: socialized
 *
 * @package AuRise\Plugin\Socialized
 * @copyright Copyright (c) 2023 Tessa Watkins, AuRise Creative <tessa@aurisecreative.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

defined('ABSPATH') || exit; // Exit if accessed directly
defined('SOCIALIZED_DIR') || define('SOCIALIZED_DIR', __DIR__); // Define root directory
defined('SOCIALIZED_FILE') || define('SOCIALIZED_FILE', __FILE__);  // Define root file
defined('SOCIALIZED_VERSION') || define('SOCIALIZED_VERSION', '4.0.0'); // Define plugin version

require_once('includes/class-utilities.php'); // Load the utilities class
require_once('includes/class-settings.php'); // Load the settings class
require_once('includes/class-metabox.php'); // Load the metabox class
require_once('includes/class-frontend.php'); // Load the frontend class
require_once('includes/class-main.php'); // Load the main plugin class

/**
 * The global instance of the Main plugin class
 *
 * @var AuRise\Plugin\Socialized\Main
 *
 * @since 1.0.0
 */
$au_init_plugin = str_replace('-', '_', sanitize_key(dirname(plugin_basename(SOCIALIZED_FILE)))); // E.g. `plugin_folder`
global ${$au_init_plugin}; // I.e. `$plugin_folder`
${$au_init_plugin} = AuRise\Plugin\Socialized\Main::instance(); // Run once to init
