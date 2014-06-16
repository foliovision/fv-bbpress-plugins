<?php
/*
Plugin Name: SMTP mailer for bbPress
Plugin URI: http://bbpress.org/plugins/topic/bb-mail-smtp/
Description: Allows bbPress to send email through an SMTP server
Author: Sam Bauers
Author URI: http://bbpress.org/plugins/topic/bb-mail-smtp/other_notes/
Version: 100.0.1fv - includes Settings + SSL

Version History:
0.1		: Initial Release
*/

/**
 * SMTP mailer for bbPress version 0.1
 * 
 * ----------------------------------------------------------------------------------
 * 
 * Copyright (C) 2008 Sam Bauers (http://unlettered.org/)Body
 * 
 * ----------------------------------------------------------------------------------
 * 
 * LICENSE:
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA.
 * 
 * ----------------------------------------------------------------------------------
 * 
 * PHP version 4 and 5
 * 
 * ----------------------------------------------------------------------------------
 * 
 * @author    Sam Bauers <sam@wopr.com.au>
 * @copyright 2008 Sam Bauers
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v2
 * @version   0.1
 **/


class BB_SMTP_Mailer
{
	/* SETTINGS */
	
	// Set to 'smtp' to use an SMTP server
	var $mailer = 'smtp';
	
	// Set a specific name that emails will appear to come from
	var $from_name = 'Foliovision Support Forums';
	
	///
	var $secure = 'ssl';
	///
	
	// The hostname of the SMTP server
	var $smtp_host = 'smtp.sendgrid.net';
	//var $smtp_host = 'hosting.foliovision.net';
	
	// The port (default is 25)
	var $smtp_port = 465;
	
	// Use authentication (true or false)
	var $smtp_auth = true;
	
	// Authentication user
	var $smtp_user = 'website@foliovision.com';
	//var $smtp_user = 'webforms@foliovision.com';
	
	// Athentication password
	var $smtp_pass = 'KLrIUWKk0BfIsQDA';
	//var $smtp_pass = 'llv0Q16PZDV1r8';

	
	/* END SETTINGS */
	
	function BB_SMTP_Mailer()
	{
		add_filter('bb_mail_from_name', array(&$this, 'inject_from_name'));
		add_action('bb_phpmailer_init', array(&$this, 'inject_smtp_setting'));
	}

	function inject_from_name($unfiltered_name)
	{
		if ($this->from_name) {
			return $this->from_name;
		} else {
			return $unfiltered_name;
		}
	}

	function inject_smtp_setting($mailer_object)
	{
		if (!$mailer_object || !is_object($mailer_object) || !is_a($mailer_object, 'PHPMailer')) {
			return false;
		}
    
    $mailer_object->CharSet = "UTF-8";
    $mailer_object->AltBody = $mailer_object->Body;
    $mailer_object->Body = bb_autop(make_clickable($mailer_object->Body));
    /*$mailer_object->IsHTML = true;
    $mailer_object->ContentType = 'text/html';*/

		if ($this->mailer == 'smtp') {
		  ///
		  ///$mailer_object->SMTPDebug = true;
		  $mailer_object->SMTPSecure = $this->secure;
		  ///
			$mailer_object->Mailer = $this->mailer;
			$mailer_object->Host = $this->smtp_host;
			if ($this->smtp_port) {
				$mailer_object->Port = $this->smtp_port;
			}
			if ($this->smtp_auth) {
				$mailer_object->SMTPAuth = true;
				$mailer_object->Username = $this->smtp_user;
				$mailer_object->Password = $this->smtp_pass;
			}
		}
	}
}

$bb_smtp_mailer = new BB_SMTP_Mailer();
?>
