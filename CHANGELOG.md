# Changelog

## v1.2.0 (2025-11-18)

### Added

- The Course Module entity now has a new column called 'Activity Plugin Type'
  - = modules.name

- New Course Modules Datasource without a specific activity (showing all)
  - Course Module Settings
    - New 'Activity Plugin Type' column, can be used as filter
  - Course
  - Course Category
  - Course Section Settings
  - Grade Item Settings

- New Course Sections Datasource (without any activity)
  - Course Sections Settings
  - Course
  - Course category

### Changed

- Moving deprecated lang strings from core_grades to local_activitysetting for 501+ compatibility
  - 'hiddenuntildate'
  - 'locktimedate'

### Fixed

- The Course Module 'idnumber' column is now text rather than integer
- SCORM stress test: Scorm generator requires a current user
- The 'Access restrictions' column in Course Module Settings worked for Assignment activity only, now it should work for all types
- The 'Access restrictions' column only worked for simple restrictions and not for all types (i.e. Activity completion)
- PHPUnit stress test came back with Avg related errors

### Known issues

- The 'Access restriction' column can break the report if it contains 'Grade' restriction
  - Refreshing the page can help
  - Download won't work

## v1.1.1 (2025-08-17)

Fixing all the PHP coding errors and making the 'Last modified' column & filter available for all entities (where it is applicable).

### Added

- Timemodified (last modified) column
  - Quiz
  - Scorm
- Timemodified (last modified) filter
  - Quiz
  - Scorm
  - Course section
  - Grade item

### Changed

- The name of the Timemodified (last modified) column comes from the local_activitysetting language file for consistency,
and it is called 'Last modified' accross the entities.
  - Assign
  - Forum


### Fixed

- PHP coding style warnings and errors

## v1.1.0 (2025-08-16)

### Added

- New Course section entity to all existing datasources
  - Section name
  - Section number
  - Visible
  - Access restrictions
  - Component
  - Last modified
- Missing add_joins() to Forum columns and filters
- Missing SCORM Appearance columns and filters when it opens a new window
  -  Width
  -  Height
  -  Options (Prevented by some browsers)

## v1.0.0 (2025-07-05)

-   Initial release