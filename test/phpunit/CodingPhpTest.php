<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/CodingPhpTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security2.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (! defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (! defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
if (! defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (! defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (! defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (! defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (! defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no menu to show
}
if (! defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
}
if (! defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (! defined("NOLOGIN")) {
	define("NOLOGIN", '1');       // If this page is public (can be called outside logged session)
}

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class CodingPhpTest extends CommonClassTest
{
	/**
	 * testPHP
	 *
	 * @return string
	 */
	public function testPHP()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$filesarray = dol_dir_list(DOL_DOCUMENT_ROOT, 'files', 1, '\.php', null, 'fullname', SORT_ASC, 0, 1, '', 1);

		foreach ($filesarray as $key => $file) {
			if (preg_match('/\/(htdocs|html)\/includes\//', $file['fullname'])) {
				continue;
			}
			if (preg_match('/\/(htdocs|html)\/install\/doctemplates\/websites\//', $file['fullname'])) {
				continue;
			}
			if (preg_match('/\/(htdocs|html)\/custom\//', $file['fullname'])) {
				continue;
			}
			if (preg_match('/\/(htdocs|html)\/dolimed/', $file['fullname'])) {
				continue;
			}
			if (preg_match('/\/(htdocs|html)\/nltechno/', $file['fullname'])) {
				continue;
			}
			if (preg_match('/\/(htdocs|html)\/teclib/', $file['fullname'])) {
				continue;
			}

			//print 'Check php file '.$file['relativename']."\n";
			$filecontent = file_get_contents($file['fullname']);

			$this->verifyIsModuleEnabledOk($filecontent, "htdocs/{$file['relativename']}");

			if (preg_match('/\.class\.php/', $file['relativename'])
				|| preg_match('/boxes\/box_/', $file['relativename'])
				|| preg_match('/modules\/.*\/doc\/(doc|pdf)_/', $file['relativename'])
				|| preg_match('/modules\/(import|mailings|printing)\//', $file['relativename'])
				|| in_array($file['name'], array('modules_boxes.php', 'TraceableDB.php'))) {
				// Check into Class files
				if (! in_array($file['name'], array(
					'api.class.php',
					'commonobject.class.php',
					'conf.class.php',
					'html.form.class.php',
					'translate.class.php',
					'utils.class.php',
					'TraceableDB.php',
					'multicurrency.class.php'
				))) {
					// Must not find $db->
					$ok = true;
					$matches = array();
					// Check string $db-> inside a class.php file (it should be $this->db-> into such classes)
					preg_match_all('/'.preg_quote('$db->', '/').'/', $filecontent, $matches, PREG_SET_ORDER);
					foreach ($matches as $key => $val) {
						$ok = false;
						break;
					}
					//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
					$this->assertTrue($ok, 'Found string $db-> into a .class.php file in '.$file['relativename'].'. Inside a .class file, you should use $this->db-> instead.');
					//exit;
				}

				if (preg_match('/\.class\.php/', $file['relativename']) && ! in_array($file['relativename'], array(
					'adherents/canvas/actions_adherentcard_common.class.php',
					'contact/canvas/actions_contactcard_common.class.php',
					'compta/facture/class/facture.class.php',
					'core/class/commonobject.class.php',
					'core/class/extrafields.class.php',
					'core/class/html.form.class.php',
					'core/class/html.formfile.class.php',
					'core/class/html.formcategory.class.php',
					'core/class/html.formmail.class.php',
					'core/class/html.formother.class.php',
					'core/class/html.formsms.class.php',
					'core/class/html.formticket.class.php',
					'core/class/utils.class.php',
					'fourn/class/fournisseur.facture.class.php',
					'societe/canvas/actions_card_common.class.php',
					'societe/canvas/individual/actions_card_individual.class.php',
					'ticket/class/actions_ticket.class.php',
					'ticket/class/ticket.class.php',
					'webportal/class/context.class.php',
					'webportal/class/html.formcardwebportal.class.php',
					'webportal/class/html.formlistwebportal.class.php',
					'webportal/controllers/document.controller.class.php',
					'workstation/class/workstation.class.php',
				))) {
					// Must not find GETPOST
					$ok = true;
					$matches = array();
					// Check string GETPOSTFLOAT a class.php file (should not be found into classes)
					preg_match_all('/GETPOST\(["\'](....)/', $filecontent, $matches, PREG_SET_ORDER);
					foreach ($matches as $key => $val) {
						if (in_array($val[1], array('lang', 'forc', 'mass', 'conf'))) {
							continue;
						}
						//var_dump($val);
						$ok = false;
						break;
					}
					//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
					$this->assertTrue($ok, 'Found string GETPOST into a .class.php file in '.$file['relativename'].'.');
				}
			} else {
				// Check into Include files
				if (! in_array($file['name'], array(
					'objectline_view.tpl.php',
					'extrafieldsinexport.inc.php',
					'extrafieldsinimport.inc.php',
					'DolQueryCollector.php',
					'DoliStorage.php'
				))) {
					// Must not found $this->db->
					$ok = true;
					$matches = array();
					// Check string $this->db-> into a non class.php file (it should be $db-> into such classes)
					preg_match_all('/'.preg_quote('$this->db->', '/').'/', $filecontent, $matches, PREG_SET_ORDER);
					foreach ($matches as $key => $val) {
						$ok = false;
						break;
					}
					//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
					$this->assertTrue($ok, 'Found string "$this->db->" in '.$file['relativename']);
					//exit;
				}
			}

			// Check we don't miss top_httphead() into any ajax pages
			if (preg_match('/ajax\//', $file['relativename'])) {
				print "Analyze ajax page ".$file['relativename']."\n";
				$ok = true;
				$matches = array();
				preg_match_all('/top_httphead/', $filecontent, $matches, PREG_SET_ORDER);
				if (count($matches) == 0) {
					$ok = false;
				}
				//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
				$this->assertTrue($ok, 'Did not find top_httphead into the ajax page '.$file['relativename']);
				//exit;
			}

			// Check if a var_dump has been forgotten
			if (!preg_match('/test\/phpunit/', $file['fullname'])) {
				if (! in_array($file['name'], array('class.nusoap_base.php'))) {
					$ok = true;
					$matches = array();
					preg_match_all('/(.)\s*var_dump\(/', $filecontent, $matches, PREG_SET_ORDER);
					//var_dump($matches);
					foreach ($matches as $key => $val) {
						if ($val[1] != '/' && $val[1] != '*') {
							$ok = false;
							break;
						}
						break;
					}
					//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
					$this->assertTrue($ok, 'Found string var_dump that is not just after /* or // in '.$file['relativename']);
					//exit;
				}
			}

			// Check get_class followed by __METHOD__
			$ok = true;
			$matches = array();
			preg_match_all('/'.preg_quote('get_class($this)."::".__METHOD__', '/').'/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				$ok = false;
				break;
			}
			//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
			$this->assertTrue($ok, 'Found string get_class($this)."::".__METHOD__ that must be replaced with __METHOD__ only in '.$file['relativename']);
			//exit;

			// Check string $this->db->idate without quotes
			$ok = true;
			$matches = array();
			preg_match_all('/(..)\s*\.\s*\$this->db->idate\(/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				if ($val[1] != '\'"' && $val[1] != '\'\'') {
					$ok = false;
					break;
				}
				//if ($reg[0] != 'db') $ok=false;
			}
			//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
			$this->assertTrue($ok, 'Found a $this->db->idate to forge a sql request without quotes around this date field '.$file['relativename']);
			//exit;



			// Check sql string DELETE|OR|AND|WHERE|INSERT ... yyy = ".$xxx
			//  with xxx that is not 'thi' (for $this->db->sanitize) and 'db-' (for $db->sanitize). It means we forget a ' if string, or an (int) if int, when forging sql request.
			$ok = true;
			$matches = array();
			preg_match_all('/(DELETE|OR|AND|WHERE|INSERT)\s.*([^\s][^\s][^\s])\s*=\s*(\'|")\s*\.\s*\$(...)/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				if ($val[2] == 'ity' && $val[4] == 'con') {		// exclude entity = ".$conf->entity
					continue;
				}
				if ($val[2] == 'ame' && $val[4] == 'db-' && preg_match('/WHERE name/', $val[0])) {		// exclude name = ".$db->encrypt(
					continue;
				}
				if ($val[2] == 'ame' && $val[4] == 'thi' && preg_match('/WHERE name/', $val[0])) {		// exclude name = ".$this->db->encrypt(
					continue;
				}
				var_dump($matches);
				$ok = false;
				break;
			}
			//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
			$this->assertTrue($ok, 'Found non quoted or not casted var into sql request '.$file['relativename'].' - Bad.');
			//exit;

			// Check that forged sql string is using ' instead of " as string PHP quotes
			$ok = true;
			$matches = array();
			preg_match_all('/\$sql \.= \'\s*VALUES.*\$/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				//if ($val[1] != '\'"' && $val[1] != '\'\'') {
				var_dump($matches);
				$ok = false;
				break;
				//}
				//if ($reg[0] != 'db') $ok=false;
			}
			//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
			$this->assertTrue($ok, 'Found a forged SQL string that mix on same line the use of \' for PHP string and PHP variables into file '.$file['relativename'].' Use " to forge PHP string like this: $sql = "SELECT ".$myvar...');
			//exit;

			// Check that forged sql string is using ' instead of " as string PHP quotes
			$ok = true;
			$matches = array();
			preg_match_all('/\$sql \.?= \'SELECT.*\$/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				var_dump($matches);
				$ok = false;
				break;
			}
			$this->assertTrue($ok, 'Found a forged SQL string that mix on same line the use of \' for PHP string and PHP variables into file '.$file['relativename'].' Use " to forge PHP string like this: $sql = "SELECT ".$myvar...');

			// Check sql string VALUES ... , ".$xxx
			//  with xxx that is not 'db-' (for $db->escape). It means we forget a ' if string, or an (int) if int, when forging sql request.
			$ok = true;
			$matches = array();
			preg_match_all('/(VALUES).*,\s*"\s*\.\s*\$(...)/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				if ($val[1] == 'VALUES' && $val[2] == 'db-') {		// exclude $db->escape(
					continue;
				}
				if ($val[1] == 'VALUES' && $val[2] == 'thi' && preg_match('/this->db->encrypt/', $val[0])) {	// exclude ".$this->db->encrypt(
					continue;
				}
				var_dump($matches);
				$ok = false;
				break;
			}
			//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
			$this->assertTrue($ok, 'Found non quoted or not casted var into sql request '.$file['relativename'].' - Bad.');
			//exit;

			// Check '".$xxx non escaped

			// Check string   ='".$this->xxx   with xxx that is not 'escape'. It means we forget a db->escape when forging sql request.
			$ok = true;
			$matches = array();
			preg_match_all('/=\s*\'"\s*\.\s*\$this->(....)/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				if ($val[1] != 'db->' && $val[1] != 'esca') {
					$ok = false;
					break;
				}
			}
			//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
			$this->assertTrue($ok, 'Found non escaped string in building of a sql request (case 1) in '.$file['relativename'].' - Bad.');

			// Check string sql|set|WHERE|...'".$yyy->xxx   with xxx that is not 'escape', 'idate', .... It means we forget a db->escape when forging sql request.
			$ok = true;
			$matches = array();
			preg_match_all('/(sql|SET|WHERE|INSERT|VALUES|LIKE).+\s*\'"\s*\.\s*\$(.......)/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				if (! in_array($val[2], array('this->d', 'this->e', 'db->esc', 'dbs->es', 'dbs->id', 'mydb->e', 'dbsessi', 'db->ida', 'escaped', 'exclude', 'include'))) {
					$ok = false;	// This will generate error
					break;
				}
				//if ($reg[0] != 'db') $ok=false;
			}
			//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
			$this->assertTrue($ok, 'Found non escaped string in building of a sql request (case 2) in '.$file['relativename'].': '.$val[0].' - Bad.');
			//exit;

			// Check string sql|set...'.$yyy->xxx   with xxx that is not 'escape', 'idate', .... It means we forget a db->escape when forging sql request.
			$ok = true;
			$matches = array();
			preg_match_all('/(\$sql|SET\s|WHERE\s|INSERT\s|VALUES\s|VALUES\().+\s*\'\s*\.\s*\$(.........)/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				if (! in_array($val[2], array('this->db-', 'db->prefi', 'db->sanit', 'dbs->pref', 'dbs->sani', 'conf->ent', 'key : \'\')', 'key])."\')', 'excludefi', 'regexstri', ''))) {
					$ok = false;
					var_dump($matches);
					break;
				}
				//if ($reg[0] != 'db') $ok=false;
			}
			//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
			$this->assertTrue($ok, 'Found non escaped string in building of a sql request (case 3) in '.$file['relativename'].': '.$val[0].' - Bad.');
			//exit;

			// Checks with IN

			// Check string ' IN (".xxx' or ' IN (\'.xxx'  with xxx that is not '$this->db->sanitize' and not '$db->sanitize'. It means we forget a db->sanitize when forging sql request.
			$ok = true;
			$matches = array();
			preg_match_all('/\s+IN\s*\([\'"]\s*\.\s*(.........)/i', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				//var_dump($val);
				if (!in_array($val[1], array('$db->sani', '$this->db', 'getEntity', 'WON\',\'L', 'self::STA', 'Commande:', 'CommandeF', 'Entrepot:', 'Facture::', 'FactureFo', 'ExpenseRe', 'Societe::', 'Ticket::S'))) {
					$ok = false;
					break;
				}
				//if ($reg[0] != 'db') $ok=false;
			}
			//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
			$this->assertTrue($ok, 'Found non sanitized string in building of a IN or NOT IN sql request '.$file['relativename'].' - Bad.');
			//exit;

			// Check string ' IN (\'".xxx'   with xxx that is not '$this->db->sanitize' and not '$db->sanitize'. It means we forget a db->sanitize when forging sql request.
			$ok = true;
			$matches = array();
			preg_match_all('/\s+IN\s*\(\'"\s*\.\s*(.........)/i', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				//var_dump($val);
				if (!in_array($val[1], array('$db->sani', '$this->db', 'getEntity', 'WON\',\'L', 'self::STA', 'Commande:', 'CommandeF', 'Entrepot:', 'Facture::', 'FactureFo', 'ExpenseRe', 'Societe::', 'Ticket::S'))) {
					$ok = false;
					break;
				}
				//if ($reg[0] != 'db') $ok=false;
			}
			//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
			$this->assertTrue($ok, 'Found non sanitized string in building of a IN or NOT IN sql request '.$file['relativename'].' - Bad.');
			//exit;

			// Test that output of $_SERVER\[\'QUERY_STRING\'\] is escaped.
			$ok = true;
			$matches = array();
			preg_match_all('/(..............)\$_SERVER\[\'QUERY_STRING\'\]/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				if ($val[1] != 'scape_htmltag(' && $val[1] != 'ing_nohtmltag(' && $val[1] != 'dol_escape_js(') {
					$ok = false;
					break;
				}
			}
			$this->assertTrue($ok, 'Found a $_SERVER[\'QUERY_STRING\'] without dol_escape_htmltag neither dol_string_nohtmltag around it, in file '.$file['relativename'].'. Bad.');


			// Check GETPOST(... 'none');
			$ok = true;
			$matches = array();
			preg_match_all('/GETPOST\s*\(([^\)]+),\s*["\']none["\']/i', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				//var_dump($val);
				if (!in_array($val[1], array(
					"'replacestring'", "'htmlheader'", "'WEBSITE_HTML_HEADER'", "'WEBSITE_CSS_INLINE'", "'WEBSITE_JS_INLINE'", "'WEBSITE_MANIFEST_JSON'", "'PAGE_CONTENT'", "'WEBSITE_README'", "'WEBSITE_LICENSE'",
						'"mysqldump"', '"postgresqldump"',
						"'db_pass_root'", "'db_pass'", '"pass"', '"pass1"', '"pass2"', '"password"', "'password'",
						'"MAIN_MAIL_SMTPS_PW"', '"MAIN_MAIL_SMTPS_PW_EMAILING"', '"MAIN_MAIL_SMTPS_PW_TICKET"'))) {
					$ok = false;
					break;
				}
				//if ($reg[0] != 'db') $ok=false;
			}
			//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
			$this->assertTrue($ok, 'Found a GETPOST that use \'none\' as a parameter in file '.$file['relativename'].' and param is not an allowed parameter for using none - Bad.');
			//exit;


			// Test that first param of print_liste_field_titre is a translation key and not the translated value
			$ok = true;
			$matches = array();
			// Check string ='print_liste_field_titre\(\$langs'.
			preg_match_all('/print_liste_field_titre\(\$langs/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				$ok = false;
				break;
			}
			$this->assertTrue($ok, 'Found a use of print_liste_field_titre with first parameter that is a translated value instead of just the translation key in file '.$file['relativename'].'. Bad.');


			// Test we don't have <br />
			$ok = true;
			$matches = array();
			preg_match_all('/<br\s+\/>/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				if ($file['name'] != 'functions.lib.php') {
					$ok = false;
					break;
				}
			}
			$this->assertTrue($ok, 'Found a tag <br /> that is for xml in file '.$file['relativename'].'. You must use html syntax <br> instead.');


			// Test we don't have name="token" value="'.$_SESSION['newtoken'], we must use name="token" value="'.newToken() instead.
			$ok = true;
			$matches = array();
			preg_match_all('/name="token" value="\'\s*\.\s*\$_SESSION/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				if ($file['name'] != 'excludefile.php') {
					$ok = false;
					break;
				}
			}
			$this->assertTrue($ok, 'Found a forbidden string sequence into '.$file['relativename'].' : name="token" value="\'.$_SESSION[..., you must use a newToken() instead of $_SESSION[\'newtoken\'].');


			// Test we don't have preg_grep with a param without preg_quote
			$ok = true;
			$matches = array();
			preg_match_all('/preg_grep\(.*\$/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				if (strpos($val[0], 'preg_quote') === false) {
					$ok = false;
					break;
				}
			}
			$this->assertTrue($ok, 'Found a preg_grep with a param that is a $var but without preg_quote in file '.$file['relativename'].'.');


			// Test we don't have "if ($resql >"
			$ok = true;
			$matches = array();
			preg_match_all('/if \(\$resql >/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				$ok = false;
				break;
			}
			$this->assertTrue($ok, 'Found a if $resql with a > operator (when $resql is a boolean or resource) in file '.$file['relativename'].'. Please remove the > ... part.');

			// Test we don't have empty($user->hasRight
			$ok = true;
			$matches = array();
			preg_match_all('/empty\(\$user->hasRight/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				$ok = false;
				break;
			}
			$this->assertTrue($ok, 'Found code empty($user->hasRight in file '.$file['relativename'].'. empty() must not be used on a var not on a function.');

			// Test we don't have empty(DolibarrApiAccess::$user->hasRight
			$ok = true;
			$matches = array();
			preg_match_all('/empty\(DolibarrApiAccess::\$user->hasRight/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				$ok = false;
				break;
			}
			$this->assertTrue($ok, 'Found code empty(DolibarrApiAccess::$user->hasRight in file '.$file['relativename'].'. empty() must not be used on a var not on a function.');

			// Test we don't have empty($user->hasRight
			$ok = true;
			$matches = array();
			preg_match_all('/empty\(getDolGlobal/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				$ok = false;
				break;
			}
			$this->assertTrue($ok, 'Found code empty(getDolGlobal... in file '.$file['relativename'].'. empty() must be used on a var not on a function.');

			// Test we don't have @var array(
			$ok = true;
			$matches = array();
			preg_match_all('/@var\s+array\(/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				$ok = false;
				break;
			}
			$this->assertTrue($ok, 'Found a declaration @var array() instead of @var array in file '.$file['relativename'].'.');


			// Test we don't have CURDATE()
			$ok = true;
			$matches = array();
			preg_match_all('/CURDATE\(\)/', $filecontent, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				$ok = false;
				break;
			}
			$this->assertTrue($ok, 'Found a CURDATE\(\) into code. Do not use this SQL method in file '.$file['relativename'].'. You must use the PHP function dol_now() instead.');
		}

		return;
	}


	/**
	 * Verify that only known modules are used
	 *
	 * @param string $filecontent Contents to check for php code that uses a module name
	 * @param string $filename    File name for the contents (used for reporting)
	 *
	 * @return void
	 */
	private function verifyIsModuleEnabledOk(&$filecontent, $filename)
	{
		// Verify that only known modules are used
		preg_match_all("/isModEnabled\\(\s*[\"']([^\$\"']+)[\"']\\s*\\)/", $filecontent, $matches, PREG_SET_ORDER);

		foreach ($matches as $key => $val) {
			$module_name = $val[1];
			$this->assertModuleIsOk($module_name, "isModEnabled('$module_name') in '$filename'");
		}
	}

	/**
	 * Assert that the module name is ok, generate appropriate notifications
	 *
	 * @param string $module_name Module name to check
	 * @param string $message     Message to shown in case an assertion fails
	 *
	 * @return void
	 */
	private function assertModuleIsOk($module_name, $message = '')
	{
		if (isset(self::DEPRECATED_MODULE_MAPPING[$module_name])) {
			$new_name = self::DEPRECATED_MODULE_MAPPING[$module_name];
			print("\033[31mDeprecated module name, use '$new_name':\033[0m$message".PHP_EOL);

			//trigger_error("Deprecated module name, use '$new_name': $message", E_USER_NOTICE);
			//trigger_error("Deprecated module name, use '$new_name': $message", E_USER_DEPRECATED);
		} else {
			$this->assertTrue(
				isset(self::VALID_MODULE_MAPPING[$module_name]),
				"Unknown module: $message"
			);
		}
	}

	const DEPRECATED_MODULE_MAPPING = array(
		'actioncomm' => 'agenda',
		'adherent' => 'member',
		'adherent_type' => 'member_type',
		'banque' => 'bank',
		'categorie' => 'category',
		'commande' => 'order',
		'contrat' => 'contract',
		'entrepot' => 'stock',
		'facture' => 'invoice',
		'fichinter' => 'intervention',
		'product_fournisseur_price' => 'productsupplierprice',
		'product_price' => 'productprice',
		'projet'  => 'project',
		'propale' => 'propal',
		'socpeople' => 'contact',
	);
	const VALID_MODULE_MAPPING = array(
		'agenda' => 1,
		'member' => 1,
		'member_type' => 1,
		'bank' => 1,
		'category' => 1,
		'order' => 1,
		'contract' => 1,
		'stock' => 1,
		'invoice' => 1,
		'intervention' => 1,
		'productsupplierprice' => 1,
		'productprice' => 1,
		'project' => 1,
		'propal' => 1,
		'contact' => 1,
		'accounting' => 1,
		'ai' => 1,
		'anothermodule' => 1,
		'api' => 1,
		'asset' => 1,
		'barcode' => 1,
		'blockedlog' => 1,
		'bom' => 1,
		'bookcal' => 1,
		'bookmark' => 1,
		'cashdesk' => 1,
		'clicktodial' => 1,
		'comptabilite' => 1,
		'cron' => 1,
		'datapolicy' => 1,
		'debugbar' => 1,
		'deplacement' => 1,
		'don' => 1,
		'dynamicprices' => 1,
		'ecm' => 1,
		'ecotax' => 1,
		'emailcollector' => 1,
		'eventorganization' => 1,
		'expedition' => 1,
		'expensereport' => 1,
		'export' => 1,
		'externalsite' => 1,
		'fckeditor' => 1,
		'ficheinter' => 1,
		'fournisseur' => 1,
		'ftp' => 1,
		'google' => 1,
		'gravatar' => 1,
		'holiday' => 1,
		'hrm' => 1,
		'import' => 1,
		'incoterm' => 1,
		'intracommreport' => 1,
		'knowledgemanagement' => 1,
		'label' => 1,
		'ldap' => 1,
		'loan' => 1,
		'mailing' => 1,
		'mailman' => 1,
		'mailmanspip' => 1,
		'margin' => 1,
		'memcached' => 1,
		'modulebuilder' => 1,
		'mrp' => 1,
		'multicompany' => 1,
		'multicurrency' => 1,
		'mymodule' => 1,
		'notification' => 1,
		'numberwords' => 1,
		'openstreetmap' => 1,
		'opensurvey' => 1,
		'partnership' => 1,
		'paybox' => 1,
		'paymentbybanktransfer' => 1,
		'paypal' => 1,
		'paypalplus' => 1,
		'prelevement' => 1,
		'product' => 1,
		'productbatch' => 1,
		'receiptprinter' => 1,
		'reception' => 1,
		'recruitment' => 1,
		'resource' => 1,
		'salaries' => 1,
		'service' => 1,
		'socialnetworks' => 1,
		'societe' => 1,
		'stocktransfer' => 1,
		'stripe' => 1,
		'supplier_invoice' => 1,
		'supplier_order' => 1,
		'supplier_proposal' => 1,
		'syslog' => 1,
		'takepos' => 1,
		'tax' => 1,
		'ticket' => 1,
		'user' => 1,
		'variants' => 1,
		'webhook' => 1,
		'webportal' => 1,
		'webservices' => 1,
		'website' => 1,
		'workflow' => 1,
		'workstation' => 1,
		'zapier' => 1,
	);
}
