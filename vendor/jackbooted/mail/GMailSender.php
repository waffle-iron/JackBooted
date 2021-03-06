<?php
namespace Jackbooted\Mail;
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

use \Jackbooted\Config\Config;
use \Jackbooted\Security\Cryptography;

class GMailSender extends \PHPMailer {
    public function __construct ( $exceptions = false ) {
        parent::__construct ( $exceptions );

        //Tell PHPMailer to use SMTP
        $this->isSMTP();

        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $this->SMTPDebug = 0;

        //Ask for HTML-friendly debug output
        $this->Debugoutput = 'html';

        //Set the hostname of the mail server
        $this->Host = "smtp.gmail.com";

        //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        $this->Port = 587;

        //Set the encryption system to use - ssl (deprecated) or tls
        $this->SMTPSecure = 'tls';

        //Whether to use SMTP authentication
        $this->SMTPAuth = true;

        //Username to use for SMTP authentication
        $this->Username = Config::get( 'gmail.smtp.username', 'brettcraigdutton@gmail.com' );

        //Password to use for SMTP authentication
        $enPassword = Config::get( 'gmail.smtp.password', ':e:sWQlpSVotD8PGXxS5kAuHlq0gMH522qtQkCYCQ0DVBI=' );

        $cr = new Cryptography( Cryptography::RAND_KEY );
        $this->Password = $cr->decrypt( $enPassword );
    }
}