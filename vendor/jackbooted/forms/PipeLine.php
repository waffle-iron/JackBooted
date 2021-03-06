<?php
namespace Jackbooted\Forms;

use \Jackbooted\Config\Cfg;
/**
 * @copyright Confidential and copyright (c) 2016 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 */

/**
 * Class for Managing Form Variables
 */
abstract class PipeLine extends \Jackbooted\Util\JB implements \Iterator{

    protected static $log;

    public static function init () {
        self::$log = \Jackbooted\Util\Log4PHP::logFactory ( __CLASS__ );
    }

    protected $formVars =  [];

    public function __construct () {
        parent::__construct();
    }

    public function clear () {
        $this->formVars = [];
    }

    public function getRaw ( $key ) {
        $oldValues = Cfg::turnOffErrorHandling ();
        eval ( '$value = $this->formVars' . $key . ';' );
        if ( ! isset ( $value ) ) $value = '';
        Cfg::turnOnErrorHandling ( $oldValues );
        return $value;
    }

    public function count () {
        return count ( $this->formVars );
    }

    /**
     * Iterator function.
     *
     * @since 1.0
     * @return array
     */
    public function current ( ) {
        return current ( $this->formVars );
    }

    /**
     * Iterator function.
     *
     * @since 1.0
     * @return integer
     */
    public function key ( ){
        return key ( $this->formVars )  ;
    }

    /**
     * Iterator function.
     *
     * @since 1.0
     * @return void
     */
    public function next ( ){
        next ( $this->formVars );
    }

    /**
     * Iterator function.
     *
     * @since 1.0
     * @return void
     */
    public function rewind ( ){
        reset ( $this->formVars );
    }

    /**
     * Iterator function.
     *
     * @since 1.0
     * @return boolean
     */
    public function valid (){
        return current ( $this->formVars ) !== false;
    }
}