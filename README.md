<a href="https://travis-ci.com/catalyst/moodle-report_activitylog">
<img src="https://travis-ci.com/catalyst/moodle-report_activitylog.svg?branch=master">
</a>

Moodle Activity Log
=====================================

* [What is this?](#what-is-this)
* [How does it work?](#how-does-it-work)
* [Features](#features)
* [Branches](#branches)
* [Installation](#installation)
* [Support](#support)

What is this?
-------------

This plugin enables tracking the history of activity settings over time. It 
displays each setting that gets updated, as well as what the setting was 
changed from and to.   

How does it work?
-----------------

Following installation, the plugin will create a record of each activity and store
its current settings (`report_activitylog_settings`). Any subsequent time the plugin's settings are updated there is
a hook that compares the new settings against the previously stored settings. Any
changes are logged in the plugin's log table (`report_activitylog`). 

Features
--------

* Site and course level use activity settings history 
* Filtering against courses and activities
* Exporting of data

Branches
--------

| Moodle version    | Branch      | PHP  |
| ----------------- | ----------- | ---- |
| Moodle 3.5 to 3.8 | master      | 7.0+ |
| Moodle 3.9        | master      | 7.2+ |

Installation
------------

1. Use git to clone it into your source:

   ```sh
   git clone git@github.com:catalyst/moodle-report_activitylog.git report/activitylog
   ```

2. Then run the Moodle upgrade.

3. Wait for the cron to run, or manually run the cron if necessary.

When the plugin has installed the reports will be accessible from Site Administration -> 
Reports for the site level report, or within Course Administration -> Reports for the
course level reports.

Support
-------

If you have issues please log them in github here

https://github.com/catalyst/moodle-report_activitylog/issues

This plugin was developed by Catalyst IT Australia:

https://www.catalyst-au.net/

<img alt="Catalyst IT" src="https://cdn.rawgit.com/CatalystIT-AU/moodle-auth_saml2/master/pix/catalyst-logo.svg" width="400">
