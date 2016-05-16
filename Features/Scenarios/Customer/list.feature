Feature: Customer List

    Background:
      Given I am logged in as Administrator

    Scenario: Show page link
      When I am on "/admin/customer"
      Then I should see an "#show-link" element

    Scenario: Edit page link
      When I am on "/admin/customer"
      Then I should see an "#edit-btn" element
