moodle-local-activitysetting
============================

Requirements
------------

This plugin requires Moodle 4.5+

Motivation for this plugin
--------------------------

To be able to find anomalies in activity settings without visiting individual setting pages or access the database directly.
Using the Report Builder (Custom report) gives you an easy and safe way to see
all the activities on the site level or under a certain category.
You can check consistency accross different deparments, how different schools or colleges set up their assignments.
You don't need access to the Moodle database or know how to use SQL. Simply drag&drop.

For example: the activity completion is enabled on the activity level, but the completion tracking is disabled on the course level.

Installation
------------

Install the plugin like any other plugin to folder
/local/activitysetting

See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins


Usage & Settings
----------------

Go to Site Administration > Reports > Custom Reports
New report
Select one of the sources from the dropdown under the Avtivity Setting report section
You need to know the corresponding database tables and the meaning of their columns

Entities included
-------------------
Assignment (mod_assign)
Course Modules (course_modules)

Datasources included
--------------------
Assignments ( = mod_assign + course_modules + course + course_categories)

Changelog
---------
See [CHANGELOG.md](CHANGELOG.md) for a complete release history.