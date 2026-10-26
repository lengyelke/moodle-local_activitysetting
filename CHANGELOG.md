# Changelog

## v1.4.0 [unknown date, unreleased]

> [!NOTE]
> To improve performance and support more advanced **Gradebook reporting**, most of the `Gradebook Category Settings` are now available via a new dedicated entity.


### Removed
> [!WARNING]
> Important information, please read it before upgrading the plugin
- The following three columns and their corresponding filters from the `Grade Item Settings` entity have been removed!
  - Gradebook Category Path
  - Gradebook Category Depth
  - Top Subcategory Name
- If you have created Custom Reports using any of the columns listed above, **those reports will need to be updated after the upgrade**.
- These columns are replaced by equivalent columns in a new, dedicated **Gradebook Category Settings** entity.


### Added

- New `Gradebook Category Settings` entity with the following Columns + Filters
  - Category Path (replacing Gradebook Category Path from Grade Item Settings)
  - Category Depth (replacing Gradebook Category Depth from Grade Item Settings)
  - Top subcategory name (replacing Top subcategory name from Grade Item Settings)
  - Full name
  - Aggregation
  - Keep the highest
  - Drop the lowest
  - Exclude empty grades
  - Include outcomes in aggregation
  - Hidden
  - Last modified
  - and many more...

- The new `Gradebook Category Settings` entity is available in all the other existing datasources, replacing the three `Grade Item Settings` columns, see Removed section below

- There is a new `Gradebook Category Settings` datasource on its own, including
  - Gradebook Category Settings
  - Course
  - Course category
  - Grade Item Settings

- New columns and filters in the `Grade Item Settings` entity
  - Multiplicator, Offset, Grade display type, Grade display decimal points, Scale name

- New settings in relevant activities due to new Moodle release
  - Since Moodle 5.3
    - A new `Number of optional markers` option is availabe in **Assignments** - corresponding Column+Filter are available in the `Assignment Settings` entity.
    - A new `Maximum allowed displays` setting is available in **Quizzes** under Safe Exam Browser - corresponding Column+Filter are available in the `Quiz Settings` entity.
  - Since Moodle 5.2
    - A new `Allow browser access to screen` setting is available in **Quizzes** under Safe Exam Browser - corresponding value is available in the `Safe Exam Browser Config Yes/No fields` within the `Quiz Settings` entity.

### Fixed
- Force language filter never worked properly in the `Course Module Settings` entity
- Course Section and Course Module availability (Access restrictions) should be converted faster to human readable format even in large reports
- It turns out that Moodle is using a "default-to-ON" logic for the **AI tools**. NULL in AI tools related fields is treated as YES, therefore the two relevant Columns + Filters were not working properly in the previous version of the plugin.
  - Allow AI tools in this activity
    - NULL or 1 = Yes
    - 0 = No
  - Enabled AI features for this activity
    - NULL = All of them are enabled without mentioning anything in the field
    - {"summarise_text":0,"explain_text":0} 0 = No, missing feature (generate_text) is set to Yes
    - {"summarise_text":0,"explain_text":1,"generate_text":0} 1 = Yes; 0 = No


## v1.3.1 (2026-10-17)

### Added

- New settings in relevant activities due to new Moodle release
  - Since Moodle 5.3
    - A new `duedate` field added to Quiz - Column+Filter in the Quiz entity
  - Since Moodle 5.1
    - Allow AI tools in this activity - Column+Filter in the Course Module entity
    - Enabled AI features for this activity - Column+Filter in the Course Module entity

### Fixed

- In the Quiz entity the 'Require the use of Safe Exam Browser' was mistreated. The 'Not set' option has been removed, so the filter should work now.
- In the assignment entity two different columns had the same key
  - the `submissionattachments` column was called `submissionattachments`
  - but unfortunately the `alwaysshowdescription` column was also called `submissionattachments`
-  In the assignment entity one filter was using the wrong field
   - the `alwaysshowdescription` filter was using the `nosubmissions` field's value

### Deprecated
  - The following columns are currently available through the `Grade Item Setting` entity:
    - Gradebook Category Path
    - Gradebook Category Depth
    - Top Subcategory Name
  - In the next version, **these columns will be removed from the Grade Item Settings entity** and replaced by equivalent columns in a new, dedicated **Grade Category Settings** entity.
  - This change will allow reports to work with grade categories directly and provide greater flexibility when reporting on gradebook structures.
  - How does this affect existing reports?
    - If you have created custom reports using any of the columns listed above, those reports will need to be updated after the next upgrade.
    - You will need to remove the old category columns from the report and add the corresponding columns from the new Grade Category entity.
    - Reports that do not use these columns will not be affected.


## v1.3.0 (2026-07-20)

### Added

- Allow offline attempt was missing from Quiz

- New settings in relevant activities due to new Moodle release
  - Since Moodle 5.0
    - Grade Penalty in Assignment - Column+Filter + dedicated applicable column+filter
    - Pre-create attempts in Quiz - Column+Filter + dedicated applicable column+filter
  - Since Moodle 5.1
    - Show replies immediately in Forum - Column+Filter
  - Since Moodle 5.2
    - Multiple markers in Assignment
      - Markercount Column+Filter
      - Multimark method Column+Filter
      - Multimark rounding Column+Filter

- New feature: Activity name with a link

- New features in grade item entity
```text
    Gradebook
        ├─ FORMATIVE
        │  └─ Quizzes
        └─ SUMMATIVE
          └─ Exams
             └─ Final Exam
```
  - Gradebook Category path Column+Filter [Gradebook / Summative / Exams]
  - Gradebook Category depth Column+Filter [3]
  - Top Gradebook SubCategory name Column+Filter [Summative]


### Fixed

- Course Modules datasource never worked with Grade Items properly due to the missing module type match

## v1.2.1 (2026-05-03)

Same as v1.2.0

- Moodle 5.2 is supported and compatible.
  - New 5.2 specific settings are not yet available (i.e. Assignment multiple markers)


## v1.2.0 (2025-11-18)

### Added

- The Course Module entity now has a new column called 'Activity Plugin Type'
  - = modules.name

- New Course Modules datasource without a specific activity (showing all)
  - Course Module Settings
    - New 'Activity Plugin Type' column, can be used as filter
  - Course
  - Course Category
  - Course Section Settings
  - Grade Item Settings

- New Course Sections datasource (without any activity)
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
and it is called 'Last modified' across the entities.
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