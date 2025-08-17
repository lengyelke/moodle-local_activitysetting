# Changelog

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