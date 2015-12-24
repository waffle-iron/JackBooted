<?php
namespace Jackbooted\Util;

use \Jackbooted\DB\DB;
use \Jackbooted\DB\DBTable;
/**
 * @copyright Confidential and copyright (c) 2016 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 *
 */

class CSV extends \Jackbooted\Util\JB {
    public static function output ( $table, $name='' ) {
        if ( ! is_object ( $table ) && ! is_array ( $table ) ) exit;
        if ( $name == '' ) $name = 'output' . Invocation::next();
        if ( ! preg_match ( '/^.*\.csv$/i', $name ) ) $name .= '.csv';

        header ( 'Content-type: application/octet-stream' );
        header ( 'Content-Disposition: attachment; filename="' . $name . '"' );
        $firstRow = true;

        if ( $table instanceof DBTable || is_array( $table ) ) {
            foreach ( $table as $row ) {
                // Output the headers
                if ( $firstRow ) echo join ( ',', array_keys ( $row ) ), "\n";
                $firstRow = false;

                // Output the data
                $firstValue = true;
                foreach ( $row as $key => $val ) {
                    if ( ! $firstValue ) echo ',';
                    $firstValue = false;
                    echo '"' . addcslashes ( $val, '"' ) . '"';
                }
                echo "\n";
            }
        }
        else if ( $table instanceof \PDOStatement ) {
            while ( $row = $table->fetch ( DB::FETCH_ASSOC ) ) {
                if ( $firstRow ) echo join ( ',', array_keys ( $row ) ), "\n";
                $firstRow = false;

                // Output the data
                $firstValue = true;
                foreach ( $row as $key => $val ) {
                    if ( ! $firstValue ) echo ',';
                    $firstValue = false;
                    echo '"' . addcslashes ( $val, '"' ) . '"';
                }
                echo "\n";
            }
        }
        exit;
    }
}