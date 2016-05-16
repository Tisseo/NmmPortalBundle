Feature: Show customer

    Background:
      Given I am logged in as Administrator

    Scenario: Show page link
      When I am on "/admin/customer"
      And I follow "CanalTP"
      Then I should see "CanalTP" in the "#title" element
      And I should see "Liste des clients"
      And I should see "Editer"
