moodle-local-activitysetting
============================

A collection of report sources for Custom Reports (ReportBuilder) to see activity settings.

Requirements
------------

This plugin requires Moodle 4.5+

Motivation for this plugin
--------------------------

To find anomalies in activity settings without visiting individual setting pages or accessing the database directly.
Using the Report Builder (Custom report) gives you an easy and safe way to see
all the activities on the site level or under a certain category.
You can check consistency across different departments, and how other schools or colleges set up their assignments.
You don't need access to the Moodle database or know how to use SQL. Simply Drag & Drop.

For example: the activity completion is enabled on the activity level, but the completion tracking is disabled on the course level.

Installation
------------

Install the plugin like any other plugin to the folder
/local/activitysetting

See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins.


Usage & Settings
----------------

Go to Site Administration > Reports > Custom Reports
New report
Select one of the sources from the dropdown under the Activity Setting report section. If any of the included datasources
is not installed on your system, it will not appear in the dropdown.
You need to know the corresponding database tables and the meaning of their columns, but most of them are self-explanatory.

Entities included
-------------------
-   Assignment (mod_assign)
-   Course Modules (course_modules)

Datasources included
--------------------
-   Assignments ( = mod_assign + course_modules + course + course_categories)

Changelog
---------
See [CHANGELOG.md](CHANGELOG.md) for a complete release history.