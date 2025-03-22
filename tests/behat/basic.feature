@local @local_activitysetting
Feature: Basic tests for Activity Setting Report

  @javascript
  Scenario: Plugin local_activitysetting appears in the list of installed additional plugins
    Given I log in as "admin"
    When I navigate to "Plugins > Plugins overview" in site administration
    And I follow "Additional plugins"
    Then I should see "Activity Setting Report"
    And I should see "local_activitysetting"
