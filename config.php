<?php
/** config.php - This file loads the various configuration options
 **
 ** Written by Brett Dutton of Jackbooted Software
 ** brett@brettdutton.com
 **
 ** This software is written and distributed under the GNU General Public
 ** License which means that its source code is freely-distributed and
 ** available to the general public.
 **
 **/

// Create the $config array
$config = [];
require_once dirname ( __FILE__ ) . '/config.default.php';
require_once dirname ( __FILE__ ) . '/config.local.php';

// Environment overrides not in version control
if ( file_exists( dirname ( __FILE__ ) . '/config.env.php' ) ) {
    require_once dirname ( __FILE__ ) . '/config.env.php';
}

require_once  $config['site_path'] . '/vendor/jackbooted/config/Cfg.php';
\Jackbooted\Config\Cfg::init ( $config );

// If you want to set everything as global scope then uncheck this
// \Jackbooted\Config\Config::setOverrideScope( \Jackbooted\Config\Config::GLOBAL_SCOPE );
