@mod @mod_socialwiki

Feature: Internal links
  In order to use internal links
  As a user
  The different types of links should properly link to either search results or a page version

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Teacher   | 1        | teacher@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on

  Scenario Outline: Edit page links
    When I add a "Social Wiki" to section "1" and I fill the form with:
      | Social Wiki name | Links Socialwiki |
      | Default format   | <format>         |
    And I follow "Links Socialwiki"
    And I follow "Pages"
    And I press "Make a new Page"
    And I set the field "New page title" to "Linked"
    And I press "Create page"
    And I set the field "<format> format" to "This is a test page"
    And I press "Save"
    Then I should see "This is a test page" in the ".wikipage" "css_element"

    When I follow "Edit"
    And I set the field "<format> format" to "This is an updated test page"
    And I press "Save"
    Then I should see "This is an updated test page" in the ".wikipage" "css_element"

    When I follow "Edit"
    And I set the field "<format> format" to "This is [[Linker]] an updated test page"
    And I press "Save"
    Then I should see "This is Linker an updated test page" in the ".wikipage" "css_element"

    When I follow "Linker"
    And the field "New page title" matches value "Linker"
    And I press "Create page"
    And I set the field "<format> format" to "[[Linked]] [[Linked@.]]"
    And I press "Save"
    Then I should see "LinkedLinked" in the ".wikipage" "css_element"

    When I click on ".wikipage a:nth-of-type(1)" "css_element"
    Then I should see "Search results for: Linked"
    And I press the "back" button in the browser

    When I click on ".wikipage a:nth-of-type(2)" "css_element"
    Then I should see "This is Linker an updated test page"

    Examples:
      | format |
      | HTML   |
      | Creole |
      | NWiki  |