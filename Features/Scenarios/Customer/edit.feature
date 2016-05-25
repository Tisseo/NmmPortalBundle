Feature: Edit customer

    Background:
      Given I am logged in as Administrator

    Scenario: back to list page link
      When I am on "/admin/customer"
      And I follow "Editer"
      And I follow "Liste des clients"
      Then I should be on "/admin/customer"
