#!/usr/bin/perl

use lib '/usr/local/cpanel';
use Cpanel::DataStore;

my $app = {
	url => '/wm/cpanel_integration/cpanel.php',
	displayname => 'AfterLogic Webmail',
	icon => '/wm/cpanel_integration/cpanelwmlogo.png',
};

Cpanel::DataStore::store_ref('/var/cpanel/webmail/webmail_alwebmail.yaml', $app) || die("could not write webmail registration file");

