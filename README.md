# Roundcube plugin: Automatic addressbook NG

Automatic addressbook NG plugin collects email addresses from sent email, and stores them in configured addressbook,
and optionally assigns a group to them.

(Loosely based on sblaisot/automatic_addressbook.)



## Motivation

Existing solutions use special DB tables and/or create dedicated addressbooks, which seems redundant.
Using existing addressbook and optionally assigning collected contacts into a special group is sufficient.



## Installation

The usual way Roundcube plugins are installed, see here:
https://github.com/roundcube/roundcubemail/wiki/Installation#install-dependecies



## Configuration

See sample config file in config/. Specified config settings may be overriden in your roundcube-dir/config/config.inc.php file.

DO NOT TRY TO USE `plugins/automatic_addressbook_ng/config/config.inc.php`, it will be ignored.
