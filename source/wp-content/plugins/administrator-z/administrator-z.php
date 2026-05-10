<?php

/**
 * Plugin Name: Administrator Z
 * Description: Only for Administrator
 * Plugin URI: http://#
 * Author: quyle91
 * Author URI: http://quyle91.net
 * Version: 2026.05.10
 * License: GPL2
 * Text Domain: administrator-z
 * Domain Path: /languages
 * svn: https://plugins.svn.wordpress.org/administrator-z/
 *
 * Copyright (C) 2022 quyle91.net.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.If not, see <http://www.gnu.org/licenses/>.
 *
 *█████╗ ██████╗ ███╗ ███╗██╗███╗ ██╗██╗███████╗████████╗██████╗█████╗ ████████╗ ██████╗ ██████╗ ███████╗
 * ██╔══██╗██╔══██╗████╗ ████║██║████╗██║██║██╔════╝╚══██╔══╝██╔══██╗██╔══██╗╚══██╔══╝██╔═══██╗██╔══██╗╚══███╔╝
 * ███████║██║██║██╔████╔██║██║██╔██╗ ██║██║███████╗ ██║ ██████╔╝███████║ ██║ ██║ ██║██████╔╝███╔╝ 
 * ██╔══██║██║██║██║╚██╔╝██║██║██║╚██╗██║██║╚════██║ ██║ ██╔══██╗██╔══██║ ██║ ██║ ██║██╔══██╗ ███╔╝
 * ██║██║██████╔╝██║ ╚═╝ ██║██║██║ ╚████║██║███████║ ██║ ██║██║██║██║ ██║ ╚██████╔╝██║██║███████╗
 * ╚═╝╚═╝╚═════╝ ╚═╝ ╚═╝╚═╝╚═╝╚═══╝╚═╝╚══════╝ ╚═╝ ╚═╝╚═╝╚═╝╚═╝ ╚═╝╚═════╝ ╚═╝╚═╝╚══════╝
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('ADMINZ', true);
define('ADMINZ_VERSION', get_file_data(__FILE__, array('Version' => 'Version'), 'plugin')['Version'] ?? 'xxx');
define('ADMINZ_DATA_VERSION', 2);
define('ADMINZ_FILE', __FILE__);
define('ADMINZ_DIR', plugin_dir_path(__FILE__));
define('ADMINZ_DIR_URL', plugin_dir_url(__FILE__));
define('ADMINZ_BASENAME', plugin_basename(__FILE__));
define('ADMINZ_NAME', 'Administrator Z');
define('ADMINZ_SLUG', 'administrator-z');
require __DIR__ . "/vendor/autoload.php";