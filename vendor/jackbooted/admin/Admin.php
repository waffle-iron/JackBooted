<?php
namespace Jackbooted\Admin;

use \Jackbooted\Config\Cfg;
use \Jackbooted\DB\DB;
use \Jackbooted\DB\DBTable;
use \Jackbooted\Forms\CRUD;
use \Jackbooted\Forms\Request;
use \Jackbooted\Forms\Response;
use \Jackbooted\G;
use \Jackbooted\Html\Gravatar;
use \Jackbooted\Html\Lists;
use \Jackbooted\Html\Tag;
use \Jackbooted\Html\Validator;
use \Jackbooted\Html\WebPage;
use \Jackbooted\Security\Privileges;
/**
 * @copyright Confidential and copyright (c) 2015 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 */

class Admin extends WebPage  {
    const USER_SQL_MYSQL  = "SELECT fldUserID,CONCAT(fldFirstName, ' ',fldLastName) FROM tblUser";
    const USER_SQL_SQLITE  = "SELECT fldUserID,fldFirstName || ' ' || fldLastName FROM tblUser";
    const GROUP_SQL = 'SELECT fldGroupID,fldName FROM tblGroup';
    const LEVEL_SQL = 'SELECT fldUserTypeValue,UPPER(fldUserTypeName) FROM tblUserType';
    const TZ_SQL    = 'SELECT fldCode,fldTime FROM tblTimeZone';
    const PRIV_SQL  = 'SELECT fldSecPrivilegesID,fldName FROM tblSecPrivileges';

    private static $completeMenu;
    private static $userMenu;

    public static function init () {
        self::$completeMenu =  [ 'Manage Privileges' => __CLASS__ . '->managePrivileges()',
                                 'Manage Groups'     => __CLASS__ . '->manageGroups()',
                                 'User Accounts'     => __CLASS__ . '->editAccount()' ];
        self::$userMenu =  [];
        foreach ( self::$completeMenu as $title => $action ) {
            if ( Privileges::access ( $action ) === true ) self::$userMenu[$title] = $action;
        }
    }

    public static function getMenu () {
        return self::$userMenu;
    }

    public static function menu () {
        if ( Privileges::access ( __METHOD__ ) !== true || ! G::isLoggedIn () ) return '';

        $resp = new Response ();
        $html = Tag::hTag ( 'b' ) . 'Admin Menu' . Tag::_hTag ( 'b' ) .
                Tag::ul (  [ 'id' => 'menuList' ] );

        foreach ( self::getMenu () as $title => $action ) {
            $html .= Tag::li ( ) .
                       Tag::hRef ( '?' . $resp->action ( $action )->toUrl (), $title ) .
                     Tag::_li ( );
        }

        $html .= Tag::_ul ( );

        return $html;
    }

    private $resp;
    public function __construct () {
        parent::__construct();
        $this->resp = new Response ();
    }

    public function index () {
        return __METHOD__;
    }

    public function managePrivileges () {
        $extraColumns =  [ 'Mapping' =>  [ $this, 'managePrivilegesCallBack' ] ];

        return Tag::hTag ( 'h4' ) . 'Editing Priviliages: ' . Tag::_hTag ( 'h4' ) .
               CRUD::factory ( 'tblSecPrivileges',  [ 'topPager' => false,
                                                      'userCols' => $extraColumns ] )
                   ->index ();
    }
    public function managePrivilegesCallBack ( $id, $key ) {
        $concat =  ( Cfg::get( DB::DEF . '-driver' ) == 'mysql' ) ? "CONCAT(fldFirstName,' ',fldLastName)" : "fldFirstName || ' ' || fldLastName";

        $sql = <<<SQL
SELECT $concat Name
FROM tblUser
WHERE fldUserID IN ( SELECT DISTINCT fldUserID
                     FROM tblSecPrivUserMap
                     WHERE fldPrivilegeID=? )
UNION
SELECT fldName Name
FROM tblGroup
WHERE fldGroupID IN ( SELECT DISTINCT fldGroupID
                      FROM tblSecPrivUserMap
                      WHERE fldPrivilegeID=? )
UNION
SELECT fldUserTypeName Name
FROM tblUserType
WHERE fldUserTypeValue IN ( SELECT DISTINCT fldLevelID
                            FROM tblSecPrivUserMap
                            WHERE fldPrivilegeID=? )
SQL;

        $privs = DBTable::factory ( DB::DEF, $sql,  [ $key, $key, $key ], DB::FETCH_NUM )->getColumn ( 0 );
        $privsList = join ( ', ', $privs );
        if ( $privsList == '' ) $privsList = '*None*';

        $this->resp->action ( __CLASS__ . '->manageMappingPrivileges()' )
                   ->set ( 'IDX', $id )
                   ->set ( 'KEY', $key );

        return Tag::hRef ( '?' . $this->resp->toUrl (),
                           $privsList,
                            [ 'title' => 'Click here to edit the mappings for this priviliage' ] );
    }
    public function manageMappingPrivileges () {
        $key = Request::get ( 'KEY' );
        if ( $key == '' ) return 'KEY missing';

        $userSql = ( Cfg::get( DB::DEF . '-driver' ) == 'mysql' ) ? self::USER_SQL_MYSQL : self::USER_SQL_SQLITE;

        $crud = CRUD::factory ( 'tblSecPrivUserMap',  [ 'topPager' => false,
                                                        'where'    =>  [ 'fldPrivilegeID' => $key ] ] )
                    ->setColDisplay ( 'fldUserID',       [ CRUD::SELECT, $userSql, true ] )
                    ->setColDisplay ( 'fldGroupID',      [ CRUD::SELECT, self::GROUP_SQL, true ] )
                    ->setColDisplay ( 'fldLevelID',      [ CRUD::SELECT, self::LEVEL_SQL, true ] )
                    ->setColDisplay ( 'fldPrivilegeID',  CRUD::DISPLAY )
                    ->copyVarsFromRequest ( 'IDX' )
                    ->copyVarsFromRequest ( 'KEY' );

        $name = DB::oneValue ( DB::DEF, 'SELECT fldName FROM tblSecPrivileges WHERE fldSecPrivilegesID=?', $key );
        $html = Tag::hTag ( 'h4' ) .
                  'Editing Mapping for ' . $name .
                Tag::_hTag ( 'h4' ) .
                $crud->index ();

        return $html . $this->managePrivileges ();
    }

    public function manageGroups () {
        $extraColumns =  [ 'Users' =>  [ $this, 'manageGroupsCallBack' ] ];

        return Tag::hTag ( 'h4' ) . 'Editing Groups: ' . Tag::_hTag ( 'h4' ) .
               CRUD::factory ( 'tblGroup',  [ 'topPager' => false, 'userCols' => $extraColumns ] )
                   ->index ();
    }

    public function manageGroupsCallBack ( $id, $key ) {
        $concat = ( Cfg::get( DB::DEF . '-driver' ) == 'mysql' ) ? "CONCAT(fldFirstName,' ',fldLastName)" : "fldFirstName || ' ' || fldLastName";

        $sql = <<<SQL
SELECT $concat
FROM tblUser
WHERE fldUserID IN ( SELECT fldUserID
                     FROM tblUserGroupMap
                     WHERE fldGroupID=? )
SQL;
        $users = DBTable::factory ( DB::DEF, $sql, $key, DB::FETCH_NUM )->getColumn ( 0 );
        $userList = join ( ', ', $users );
        if ( $userList == '' ) $userList = '*None*';

        $this->resp->action ( __CLASS__ . "->manageUsersToGroups()" )
                   ->set ( 'IDX', $id )
                   ->set ( 'KEY', $key );

        return Tag::hRef ( '?' . $this->resp->toUrl (),
                           $userList,
                           [ 'title' => 'Click here to edit the users in this group' ] );
    }

    public function manageUsersToGroups ( ) {
        $key = Request::get ( 'KEY' );
        if ( $key == '' ) return 'KEY missing';

        $userSql = ( Cfg::get( DB::DEF . '-driver' ) == 'mysql' ) ? self::USER_SQL_MYSQL : self::USER_SQL_SQLITE;

        $row = DB::oneRow ( DB::DEF, 'SELECT * FROM tblGroup WHERE fldGroupID=?', $key );

        return Tag::hTag ( 'h4' ) .
                 Tag::e ( 'Editing Users in ' . $row['fldName'] . '(' . $row['fldLongName'] . ')' ) .
               Tag::_hTag ( 'h4' ) .
               CRUD::factory ( 'tblUserGroupMap',  [ 'topPager' => false,
                                                      'where'   => [ 'fldGroupID' => $key ] ] )
                   ->setColDisplay ( 'fldUserID',   [ CRUD::SELECT, $userSql, true ] )
                   ->setColDisplay ( 'fldGroupID',  CRUD::DISPLAY )
                   ->copyVarsFromRequest( 'KEY' )
                   ->index ();
    }

    public function editAccount () {
        $resp = new Response ();
        $uid = G::get ( 'fldUserID' );
        $html = '';
        $props =  [];

        $userSql = ( Cfg::get( DB::DEF . '-driver' ) == 'mysql' ) ? self::USER_SQL_MYSQL : self::USER_SQL_SQLITE;

        if ( G::accessLevel ( Privileges::getSecurityLevel ( 'SITE ADMIN' ) ) ) {
            $uid = Request::get ( 'fldUserID', G::get ( 'fldUserID' ) );
            $props['where'] =  [ 'fldUserID' => G::get ( 'fldUserID' ) ];
            $html .= Tag::form () .
                       $resp->action ( sprintf ( '%s->%s()', __CLASS__, __FUNCTION__ ) )->toHidden () .
                       Tag::table ( ) .
                         Tag::tr () .
                           Tag::th () . 'User to edit' . Tag::_th () .
                           Tag::td () .
                             Lists::select ( 'fldUserID', $userSql,  [ 'onChange' => 'submit()',
                                                                       'default'  => $uid ] ) .
                           Tag::_td () .
                         Tag::_tr () .
                       Tag::_table () .
                     Tag::_form ();
        }

        $formName = 'Admin_editAccount';
        $valid = Validator::factory ( $formName )
                          ->addEqual ( 'fldPassword', 'fldPassword_CHK', 'Your passwords do not match' )
                          ->addLength ( 'fldPassword', 'Password must be at least 6 characters', 6, null, true )
                          ->addExists ( 'fldFirstName', 'You must enter your first name' )
                          ->addExists ( 'fldLastName', 'You must enter your last name' );

        $row = DB::oneRow ( DB::DEF, 'SELECT * FROM tblUser WHERE fldUserID=?', $uid );
        $html .= '<h2>Edit User Account</h2>' .
                 $valid->toHtml () .
                 Tag::form (  [ 'name' => $formName, 'onSubmit' => $valid->onSubmit() ] ) .
                   $resp->action ( sprintf ( '%s->%sSave()', __CLASS__, __FUNCTION__ ) )->set ( 'fldUserID', $uid )->toHidden () .
                   Tag::table ( );


        $html .=     Tag::tr () .
                       Tag::td () .
                         Tag::table ( ) .
                           Tag::tr () .
                             Tag::td () . 'User Name/Email' . Tag::_td () .
                             Tag::td () . Tag::text ( 'fldUser', $row['fldUser'] ) . Tag::_td () .
                           Tag::_tr () .
                           Tag::tr () .
                             Tag::td () . 'Old Password' . Tag::_td () .
                             Tag::td () . Tag::password ( 'fldPassword_OLD' ) . Tag::_td () .
                           Tag::_tr () .
                           Tag::tr () .
                             Tag::td () . 'Password' . Tag::_td () .
                             Tag::td () . Tag::password ( 'fldPassword' ) . Tag::_td () .
                           Tag::_tr () .
                           Tag::tr () .
                             Tag::td () . 'Confirm Password' . Tag::_td () .
                             Tag::td () . Tag::password ( 'fldPassword_CHK' ) . Tag::_td () .
                           Tag::_tr () .
                           Tag::tr () .
                             Tag::td () . 'Title' . Tag::_td () .
                             Tag::td () . Tag::text ( 'fldSalutation', $row['fldSalutation'] ) . Tag::_td () .
                           Tag::_tr () .
                           Tag::tr () .
                             Tag::td () . 'First Name' . Tag::_td () .
                             Tag::td () . Tag::text ( 'fldFirstName', $row['fldFirstName'] ) . Tag::_td () .
                           Tag::_tr () .
                           Tag::tr () .
                             Tag::td () . 'Last Name' . Tag::_td () .
                             Tag::td () . Tag::text ( 'fldLastName', $row['fldLastName'] ) . Tag::_td () .
                           Tag::_tr () .
                           Tag::tr () .
                             Tag::td () . 'Time Zone' . Tag::_td () .
                             Tag::td () .
                               Lists::select ( 'fldTimeZone', self::TZ_SQL,  [ 'default' => $row['fldTimeZone'] ] ) .
                             Tag::_td () .
                           Tag::_tr ();

        if ( G::accessLevel ( Privileges::getSecurityLevel ( 'SITE ADMIN' ) ) ) {
            $html .=       Tag::tr () .
                             Tag::td () . 'Security Level' . Tag::_td () .
                             Tag::td () .
                               Lists::select ( 'fldLevel', self::LEVEL_SQL,  [ 'default' =>  $row['fldLevel'] ] ).
                             Tag::_td () .
                           Tag::_tr () .
                           Tag::tr () .
                             Tag::td () . 'Login Fails' . Tag::_td () .
                             Tag::td () . Tag::text ( 'fldFails', $row['fldFails'] ) . Tag::_td () .
                           Tag::_tr ();
        }
        else {
            $html .=       Tag::tr () .
                             Tag::td () . 'Security Level' . Tag::_td () .
                             Tag::td () . Privileges::getSecurityLevel ( $row['fldLevel'] ) .Tag::_td () .
                           Tag::_tr () .
                           Tag::tr () .
                             Tag::td () . 'Login Fails' . Tag::_td () .
                             Tag::td () . $row['fldFails'] . Tag::_td () .
                           Tag::_tr ();
        }

        $html .=           Tag::tr () .
                             Tag::td (  [ 'colspan' => 2 ] ) .
                               Tag::submit ( 'Save' ) .
                             Tag::_td () .
                           Tag::_tr ();

        if ( G::accessLevel ( Privileges::getSecurityLevel ( 'SITE ADMIN' ) ) ) {
            $html .=       Tag::tr () .
                             Tag::td (  [ 'colspan' => 2 ] ) .
                               Tag::linkButton( '?' . $resp->action ( __CLASS__ . '->newUser()' )->toUrl(),
                                                'New User',
                                                [ 'title' => "Creates a new user" ] ) .
                             Tag::_td () .
                           Tag::_tr ();
        }

        $html .=         Tag::_table () .
                       Tag::_td () .
                       Tag::td (  [ 'valign' => 'top', 'align' => 'center' ] ) .
                         Tag::table ( ) .
                           Tag::tr () .
                             Tag::td (  [ 'valign' => 'top', 'align' => 'center' ] ) .
                               Gravatar::icon ( $row['fldUser'], 128 ) .
                             Tag::_td () .
                           Tag::_tr () .
                           Tag::tr () .
                             Tag::td () .
                               Tag::linkButton( Gravatar::getURL(), 'Change Picture',  [ 'target' => '_blank' ,
                                                                                          'title' => 'your gravatar is associated with your email address ' .
                                                                                                      $row['fldUser'] . ' (up to 24 hrs to change)' ] ) .
                             Tag::_td () .
                           Tag::_tr ();

        if ( G::accessLevel ( Privileges::getSecurityLevel ( 'SITE ADMIN' ) ) && $uid != G::get ( 'fldUserID' ) ) {
            $name = $row['fldFirstName'] . ' ' . $row['fldLastName'];
            $html .=       Tag::tr () .
                             Tag::td () .
                               Tag::linkButton( '?' . $resp->action ( __CLASS__ . '->loginAs()' )->set ( 'fldUser', $row['fldUser'] )->toUrl(),
                                                'Login as this User',
                                                [ 'title' => "Login as this user ($name)" ] ) .
                             Tag::_td () .
                           Tag::_tr ();
        }

        $html .=        Tag::_table () .
                       Tag::_td () .
                     Tag::_tr () .
                   Tag::_table () .
                 Tag::_form ();

        return $html;
    }
    public function loginAs  () {
        Login::loadPreferences ( Request::get ( 'fldUser' ) );
        Login::home ();
    }

    public function checkOldPassword  ( $uid, $pw ) {
        if ( Cfg::get( DB::DEF . '-driver' ) == 'mysql' ) {
            return 1 == DB::oneValue ( DB::DEF,
                                       'SELECT COUNT(*) FROM tblUser WHERE fldPassword=PASSWORD(?) AND fldUserID=?',
                                        [  $pw, $uid ] );
        }
        else {
            return 1 == DB::oneValue ( DB::DEF,
                                       'SELECT COUNT(*) FROM tblUser WHERE fldPassword=? AND fldUserID=?',
                                        [ hash( 'md5', $pw ), $uid ] );
        }

    }

    public function editAccountSave  () {
        $uid = Request::get ( 'fldUserID', G::get ( 'fldUserID' ) );
        $messages =  [];
        $sqls     =  [];
        $params   =  [];

        $pw  = Request::get ( 'fldPassword' );
        $pwCheck = Request::get ( 'fldPassword_CHK' );
        $pwOld = Request::get ( 'fldPassword_OLD' );
        if ( $pw != '' && $pwCheck!= '' ) {
            if ( ! $this->checkOldPassword ( $uid, $pwOld ) ) {
                $messages[] = '<font color=red>Old Password is not correct<font>';
            }
            else if ( $pw != $pwCheck ) {
                $messages[] = '<font color=red>Passwords are not the same<font>';
            }
            else if ( $pwOld == $pw ) {
                $messages[] = '<font color=red>No Change, old and new passwords same<font>';
            }
            else {
                if ( Cfg::get( DB::DEF . '-driver' ) == 'mysql' ) {
                    $sqls[] = 'UPDATE tblUser SET fldPassword=PASSWORD(?),fldModified=UNIX_TIMESTAMP() WHERE fldUserID=?';
                    $params[] =  [ $pw, $uid ];
                }
                else {
                    $sqls[] = 'UPDATE tblUser SET fldPassword=?,fldModified=strftime(\'%s\',\'now\') WHERE fldUserID=?';
                    $params[] =  [ hash( 'md5', $pw ), $uid ];
                }
            }
        }

        $sqls[] = 'UPDATE tblUser SET fldSalutation=?,fldModified='.time().' WHERE fldUserID=?';
        $params[] =  [ Request::get ( 'fldSalutation' ), $uid ];

        if ( Request::get ( 'fldFirstName' ) == '' ) {
            $messages[] = '<font color=red>First name cannot be empty<font>';
        }
        else {
            $sqls[] = 'UPDATE tblUser SET fldFirstName=?,fldModified='.time().' WHERE fldUserID=?';
            $params[] =  [ Request::get ( 'fldFirstName' ), $uid ];
        }

        if ( Request::get ( 'fldLastName' ) == '' ) {
            $messages[] = '<font color=red>Last name cannot be empty<font>';
        }
        else {
            $sqls[] = 'UPDATE tblUser SET fldLastName=?,fldModified='.time().' WHERE fldUserID=?';
            $params[] =  [ Request::get ( 'fldLastName' ), $uid ];
        }

        if ( Request::get ( 'fldTimeZone' ) != '' ) {
            $sqls[] = 'UPDATE tblUser SET fldTimeZone=?,fldModified='.time().' WHERE fldUserID=?';
            $params[] =  [ Request::get ( 'fldTimeZone' ), $uid ];
        }

        if ( Request::get ( 'fldUser' ) != '' ) {
            $sqls[] = 'UPDATE tblUser SET fldUser=?,fldModified='.time().' WHERE fldUserID=?';
            $params[] =  [ Request::get ( 'fldUser' ), $uid ];
        }

        if ( Request::get ( 'fldLevel' ) != '' ) {
            $sqls[] = 'UPDATE tblUser SET fldLevel=?,fldModified='.time().' WHERE fldUserID=?';
            $params[] =  [ Request::get ( 'fldLevel' ), $uid ];
        }

        if ( count ( $messages ) != 0 ) {
            return join ('<br>', $messages ) . $this->editAccount ();
        }
        else {
            foreach ( $sqls as $idx => $sql ) {
                DB::exec ( DB::DEF, $sql, $params[$idx] );
            }
            if ( $uid == G::get ( 'fldUserID' ) ) {
                foreach ( DB::oneRow ( DB::DEF, 'SELECT * FROM tblUser WHERE fldUserID=?', $uid ) as $key => $val ) {
                    G::set ( $key, $val );
                }
            }
            return 'Sucessfully updated user account details' .
                   $this->editAccount ();
        }
    }
}