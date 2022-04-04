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
              paymentDue
              paymentDueV2 {
                amount
                currencyCode
              }
              webUrl
              orderStatusUrl
              taxExempt
              taxesIncluded
              currencyCode
              totalTax
              totalTaxV2 {
                amount
                currencyCode
              }
              lineItemsSubtotalPrice {
                amount
                currencyCode
              }
              subtotalPrice
              subtotalPriceV2 {
                amount
                currencyCode
              }
              totalPrice
              totalPriceV2 {
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
                      description
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
                price
                priceV2 {
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
                      priceV2 {
                        amount
                        currencyCode
                      }
                      product {
                        id
                        handle
                        title
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
                          description
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
                  priceV2 {
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
            mutation checkoutAttributesUpdate($checkoutId: ID!, $input: CheckoutAttributesUpdateInput!) {
                checkoutAttributesUpdate(checkoutId: $checkoutId, input: $input) {
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
            paymentDue
            paymentDueV2 {
              amount
              currencyCode
            }
            webUrl
            orderStatusUrl
            taxExempt
            taxesIncluded
            currencyCode
            totalTax
            totalTaxV2 {
              amount
              currencyCode
            }
            lineItemsSubtotalPrice {
              amount
              currencyCode
            }
            subtotalPrice
            subtotalPriceV2 {
              amount
              currencyCode
            }
            totalPrice
            totalPriceV2 {
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
                    description
                  }
                  ... on AutomaticDiscountApplication {
                    title
                  }
                }
              }
            }
            shippingLine {
              handle
              price
              priceV2 {
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
              subtotalPrice
              subtotalPriceV2 {
                amount
                currencyCode
              }
              totalShippingPrice
              totalShippingPriceV2 {
                amount
                currencyCode
              }
              totalTax
              totalTaxV2 {
                amount
                currencyCode
              }
              totalPrice
              totalPriceV2 {
                amount
                currencyCode
              }
              currencyCode
              totalRefunded
              totalRefundedV2 {
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
                        description
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
            totalPrice
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
            totalPrice
            availableShippingRates {
              ready
              shippingRates {
                handle
                title
                priceV2 {
                  amount
                  currencyCode
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
            paymentDue
            paymentDueV2 {
              amount
              currencyCode
            }
            webUrl
            orderStatusUrl
            taxExempt
            taxesIncluded
            currencyCode
            totalTax
            totalTaxV2 {
              amount
              currencyCode
            }
            lineItemsSubtotalPrice {
              amount
              currencyCode
            }
            subtotalPrice
            subtotalPriceV2 {
              amount
              currencyCode
            }
            totalPrice
            totalPriceV2 {
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
                    description
                  }
                  ... on AutomaticDiscountApplication {
                    title
                  }
                }
              }
            }
            shippingLine {
              handle
              price
              priceV2 {
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
              subtotalPrice
              subtotalPriceV2 {
                amount
                currencyCode
              }
              totalShippingPrice
              totalShippingPriceV2 {
                amount
                currencyCode
              }
              totalTax
              totalTaxV2 {
                amount
                currencyCode
              }
              totalPrice
              totalPriceV2 {
                amount
                currencyCode
              }
              currencyCode
              totalRefunded
              totalRefundedV2 {
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
                    title
                    priceV2 {
                      amount
                      currencyCode
                    }
                    product {
                      id
                      handle
                      title
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
                        description
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

    protected function sanitizeMutation(string $mutation)
    {
        return str_replace(array("\r", "\n"), '', $mutation);
    }
}
