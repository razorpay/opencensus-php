<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

// builds storefront and admin graphql mutations for shopify
class Mutations
{
     //get checkout details
     // TODO: remove shipping address?
     public function getCheckoutMutation() {
      return $this->sanitizeMutation('query ($id: ID!) {
        node(id: $id) {
            id
            ... on Checkout {
              id
              requiresShipping
              shippingAddress {
                firstName
                lastName
                phone
                city
                province
                address1
                country
                zip
              }
              note
              customAttributes {
                key
                value
              }
              paymentDue {
                amount
                currencyCode
              }
              webUrl
              orderStatusUrl
              taxExempt
              taxesIncluded
              currencyCode
              totalTax {
                amount
                currencyCode
              }
              lineItemsSubtotalPrice {
                amount
                currencyCode
              }
              subtotalPrice {
                amount
                currencyCode
              }
              totalPrice {
                amount
                currencyCode
              }
              completedAt
              createdAt
              updatedAt
              email
              discountApplications(first: 10) {
                edges {
                  node {
                    targetSelection
                    allocationMethod
                    targetType
                    value {
                      ... on MoneyV2 {
                        amount
                        currencyCode
                      }
                      ... on PricingPercentageValue {
                        percentage
                      }
                    }
                    ... on ManualDiscountApplication {
                      title
                      description
                    }
                    ... on DiscountCodeApplication {
                      code
                      applicable
                    }
                    ... on ScriptDiscountApplication {
                      title
                    }
                    ... on AutomaticDiscountApplication {
                      title
                    }
                  }
                },
                pageInfo {
                  hasNextPage
                  hasPreviousPage
                }
              }
              shippingLine {
                handle
                price {
                  amount
                  currencyCode
                }
                title
              }
              customAttributes {
                key
                value
              }
              lineItems(first: 250) {
                pageInfo {
                  hasNextPage
                  hasPreviousPage
                }
                edges {
                  cursor
                  node {
                    id
                    title
                    quantity
                    variant {
                      id
                      title
                      price {
                        amount
                        currencyCode
                      }
                      product {
                        id
                        handle
                        title
                        tags
                      }
                    }
                    customAttributes {
                      key
                      value
                    }
                    discountAllocations {
                      allocatedAmount {
                        amount
                        currencyCode
                      }
                      discountApplication {
                        targetSelection
                        allocationMethod
                        targetType
                        value {
                          ... on MoneyV2 {
                            amount
                            currencyCode
                          }
                          ... on PricingPercentageValue {
                            percentage
                          }
                        }
                        ... on ManualDiscountApplication {
                          title
                          description
                        }
                        ... on DiscountCodeApplication {
                          code
                          applicable
                        }
                        ... on ScriptDiscountApplication {
                          title
                        }
                        ... on AutomaticDiscountApplication {
                          title
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }');
     }

     public function getPollForShippingRatesMutation() {
      return $this->sanitizeMutation('query ($id: ID!) {
        node(id: $id) {
            id
            ... on Checkout {
              id
              ready
              availableShippingRates {
                ready
                shippingRates {
                  handle
                  title
                  price {
                    amount
                    currencyCode
                  }
                }
              }
            }
          }
        }');
     }

     public function checkoutAttributesUpdateMutation()
     {
        return $this->sanitizeMutation('
            mutation checkoutAttributesUpdateV2($checkoutId: ID!, $input: CheckoutAttributesUpdateV2Input!) {
                checkoutAttributesUpdateV2(checkoutId: $checkoutId, input: $input) {
                  checkout {
                    id
                    customAttributes {
                      key
                      value
                    }
                    note
                  }
                  checkoutUserErrors {
                    code
                    field
                    message
                  }
                }
              }');
     }

     // fetch one or more SKUs to capture details
     public function getCouponListMutation()
     {
       return $this->sanitizeMutation('{
        priceRules(first: 30,query:"status:active") {
          edges {
            node {
              id
              title
              target
              app {
                developerName
              }
              allocationMethod
              allocationLimit
              usageLimit
              usageCount
              oncePerCustomer
              startsAt
              endsAt
              features
              summary
              itemEntitlements {
                  ...on PriceRuleItemEntitlements {
                      targetAllLineItems
                  }
              }
              customerSelection {
                  ... on PriceRuleCustomerSelection {
                      forAllCustomers
                  }
                  customers(first:10) {
                      edges {
                          node {
                              id
                              email
                          }
                      }
                  }
              }
              prerequisiteQuantityRange {
                   ... on PriceRuleQuantityRange {
                       greaterThanOrEqualTo
                   }
              }
              prerequisiteShippingPriceRange {
                  ... on PriceRuleMoneyRange {
                       lessThanOrEqualTo
                  }
              }
              shippingEntitlements {
                  ... on PriceRuleShippingLineEntitlements {
                       countryCodes
                       includeRestOfWorld
                       targetAllShippingLines
                  }
              }
              prerequisiteSubtotalRange {
                  ... on PriceRuleMoneyRange {
                       greaterThanOrEqualTo
                  }
              }
              valueV2 {
                __typename
                ... on PricingPercentageValue {
                  percentage
                }
                ... on MoneyV2 {
                  amount
                  currencyCode
                }
              }
              discountCodes(first: 10) {
                edges {
                  node {
                    code
                    id
                  }
                }
              }
            }
            cursor
          }
          pageInfo {
            hasNextPage
          }
        }
      }');
     }

     //apply discount / coupon on checkout
     public function applyCouponMutation()
     {
       return $this->sanitizeMutation('mutation checkoutDiscountCodeApplyV2($discountCode: String!, $checkoutId: ID!) {
        checkoutDiscountCodeApplyV2(
          discountCode: $discountCode
          checkoutId: $checkoutId
        ) {
          checkout {
            id
            ready
            requiresShipping
            note
            paymentDue {
              amount
              currencyCode
            }
            webUrl
            orderStatusUrl
            taxExempt
            taxesIncluded
            currencyCode
            totalTax {
              amount
              currencyCode
            }
            lineItemsSubtotalPrice {
              amount
              currencyCode
            }
            subtotalPrice {
              amount
              currencyCode
            }
            totalPrice {
              amount
              currencyCode
            }
            completedAt
            createdAt
            updatedAt
            email
            discountApplications(first: 10) {
              pageInfo {
                hasNextPage
                hasPreviousPage
              }
              edges {
                node {
                  targetSelection
                  allocationMethod
                  targetType
                  value {
                    ... on MoneyV2 {
                      amount
                      currencyCode
                    }
                    ... on PricingPercentageValue {
                      percentage
                    }
                  }
                  ... on ManualDiscountApplication {
                    title
                    description
                  }
                  ... on DiscountCodeApplication {
                    code
                    applicable
                  }
                  ... on ScriptDiscountApplication {
                    title
                  }
                  ... on AutomaticDiscountApplication {
                    title
                  }
                }
              }
            }
            shippingLine {
              handle
              price {
                amount
                currencyCode
              }
              title
            }
            customAttributes {
              key
              value
            }
            order {
              id
              processedAt
              orderNumber
              subtotalPrice {
                amount
                currencyCode
              }
              totalShippingPrice {
                amount
                currencyCode
              }
              totalTax {
                amount
                currencyCode
              }
              totalPrice {
                amount
                currencyCode
              }
              currencyCode
              totalRefunded {
                amount
                currencyCode
              }
              customerUrl
              lineItems(first: 250) {
                pageInfo {
                  hasNextPage
                  hasPreviousPage
                }
                edges {
                  cursor
                  node {
                    title
                    quantity
                    customAttributes {
                      key
                      value
                    }
                  }
                }
              }
            }
            lineItems(first: 250) {
              pageInfo {
                hasNextPage
                hasPreviousPage
              }
              edges {
                cursor
                node {
                  id
                  variant{
                    product{
                      tags
                    }
                    price{
                      amount
                    }
                  }
                  title
                  quantity
                  customAttributes {
                    key
                    value
                  }
                  discountAllocations {
                    allocatedAmount {
                      amount
                      currencyCode
                    }
                    discountApplication {
                      targetSelection
                      allocationMethod
                      targetType
                      value {
                        ... on MoneyV2 {
                          amount
                          currencyCode
                        }
                        ... on PricingPercentageValue {
                          percentage
                        }
                      }
                      ... on ManualDiscountApplication {
                        title
                        description
                      }
                      ... on DiscountCodeApplication {
                        code
                        applicable
                      }
                      ... on ScriptDiscountApplication {
                        title
                      }
                      ... on AutomaticDiscountApplication {
                        title
                      }
                    }
                  }
                }
              }
            }
          }
          checkoutUserErrors {
            code
            field
            message
          }
        }
      }
      ');
     }

    // discount / coupon on checkout
    public function removeCouponMutation()
    {
      return $this->sanitizeMutation('mutation checkoutDiscountCodeRemove($checkoutId: ID!) {
        checkoutDiscountCodeRemove(checkoutId: $checkoutId) {
          checkout {
            id
            totalPrice {
              amount
              currencyCode
            }
          }
          checkoutUserErrors {
            code
            field
            message
          }
        }
      }');
    }

    // updates shipping address and fetches available shipping rates
    public function getUpdateShippingAddressMutation()
    {
      return $this->sanitizeMutation('mutation checkoutShippingAddressUpdateV2($shippingAddress: MailingAddressInput!, $checkoutId: ID!) {
        checkoutShippingAddressUpdateV2(
          shippingAddress: $shippingAddress
          checkoutId: $checkoutId
        ) {
          checkout {
            id
            requiresShipping
            totalPrice {
              amount
              currencyCode
            }
            availableShippingRates {
              ready
              shippingRates {
                handle
                title
                price {
                  amount
                  currencyCode
                }
              }
            }
            lineItems(first: 250) {
             edges{
              node{
               variant{
                requiresShipping
                }
               }
             }
           }
          }
          checkoutUserErrors {
            code
            field
            message
           }
         }
       }');

    }

    public function getcheckoutEmailUpdateMutation()
    {
      return $this->sanitizeMutation('mutation checkoutEmailUpdateV2($checkoutId: ID!, $email: String!) {
        checkoutEmailUpdateV2(checkoutId: $checkoutId, email: $email) {
          checkout {
            id
            email
            webUrl
          }
          checkoutUserErrors {
            code
            field
            message
          }
        }
      }');
    }

    public function getAddTagMutation()
    {
        return $this->sanitizeMutation('mutation addTags($id: ID!, $tags: [String!]!) {
          tagsAdd(id: $id, tags: $tags) {
            node {
              id
            }
            userErrors {
              message
            }
          }
        }');
    }

    public function getRemoveTagMutation()
    {
        return $this->sanitizeMutation('mutation removeTags($id: ID!, $tags: [String!]!) {
          tagsRemove(id: $id, tags: $tags) {
            node {
              id
            }
            userErrors {
              message
            }
          }
        }');
    }

    /**
     * Storefront graphql mutations
     */

    public function getProducts()
    {
      return $this->sanitizeMutation('{
        products(first:5) {
          edges {
            node {
              id
              title
              variants(first: 10) {
                edges {
                  node {
                    id
                    title
                  }
                }
              }
            }
          }
        }
      }');
    }

    public function getCreateCheckoutMutation()
    {
      return $this->sanitizeMutation('mutation checkoutCreate($input: CheckoutCreateInput!) {
        checkoutCreate(input: $input) {
          checkout {
            id
            ready
            requiresShipping
            note
            paymentDue {
              amount
              currencyCode
            }
            webUrl
            orderStatusUrl
            taxExempt
            taxesIncluded
            currencyCode
            totalTax {
              amount
              currencyCode
            }
            lineItemsSubtotalPrice {
              amount
              currencyCode
            }
            subtotalPrice {
              amount
              currencyCode
            }
            totalPrice {
              amount
              currencyCode
            }
            completedAt
            createdAt
            updatedAt
            email
            discountApplications(first: 10) {
              pageInfo {
                hasNextPage
                hasPreviousPage
              }
              edges {
                node {
                  targetSelection
                  allocationMethod
                  targetType
                  value {
                    ... on MoneyV2 {
                      amount
                      currencyCode
                    }
                    ... on PricingPercentageValue {
                      percentage
                    }
                  }
                  ... on ManualDiscountApplication {
                    title
                    description
                  }
                  ... on DiscountCodeApplication {
                    code
                    applicable
                  }
                  ... on ScriptDiscountApplication {
                    title
                  }
                  ... on AutomaticDiscountApplication {
                    title
                  }
                }
              }
            }
            shippingLine {
              handle
              price {
                amount
                currencyCode
              }
              title
            }
            customAttributes {
              key
              value
            }
            order {
              id
              processedAt
              orderNumber
              subtotalPrice {
                amount
                currencyCode
              }
              totalShippingPrice {
                amount
                currencyCode
              }
              totalTax {
                amount
                currencyCode
              }
              totalPrice {
                amount
                currencyCode
              }
              currencyCode
              totalRefunded {
                amount
                currencyCode
              }
              customerUrl
              lineItems(first: 250) {
                pageInfo {
                  hasNextPage
                  hasPreviousPage
                }
                edges {
                  cursor
                  node {
                    title
                    quantity
                    customAttributes {
                      key
                      value
                    }
                  }
                }
              }
            }
            lineItems(first: 250) {
              pageInfo {
                hasNextPage
                hasPreviousPage
              }
              edges {
                cursor
                node {
                  id
                  title
                  quantity
                  variant {
                    id
                    weight
                    image {
                      id
                      url
                    }
                    sku
                    title
                    price {
                      amount
                      currencyCode
                    }
                    product {
                      id
                      handle
                      title
                      description
                    }
                  }
                  customAttributes {
                    key
                    value
                  }
                  discountAllocations {
                    allocatedAmount {
                      amount
                      currencyCode
                    }
                    discountApplication {
                      targetSelection
                      allocationMethod
                      targetType
                      value {
                        ... on MoneyV2 {
                          amount
                          currencyCode
                        }
                        ... on PricingPercentageValue {
                          percentage
                        }
                      }
                      ... on ManualDiscountApplication {
                        title
                        description
                      }
                      ... on DiscountCodeApplication {
                        code
                        applicable
                      }
                      ... on ScriptDiscountApplication {
                        title
                      }
                      ... on AutomaticDiscountApplication {
                        title
                      }
                    }
                  }
                }
              }
            }
          }
          checkoutUserErrors {
            code
            field
            message
          }
          queueToken
        }
      }
      ');
    }

    /**
     * Storefront graphql disable coupon mutations
     */

    public function disableCouponMutation()
    {
      return $this->sanitizeMutation('mutation discountCodeDeactivate($id: ID!) {
        discountCodeDeactivate(id: $id) {
          codeDiscountNode {
            codeDiscount {
              ... on DiscountCodeBasic {
                title
                status
                startsAt
                endsAt
              }
            }
          }
          userErrors {
            field
            code
            message
          }
        }
      }');
    }

    protected function sanitizeMutation(string $mutation)
    {
        return str_replace(array("\r", "\n"), '', $mutation);
    }
}
