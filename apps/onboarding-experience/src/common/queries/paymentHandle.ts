import { gql } from 'graphql-tag';

/**
 * Query to fetch the merchant's current payment handle information
 * Returns payment handle details including title, slug and URL
 */
export const MERCHANT_PAYMENT_HANDLE_QUERY = gql`
  query merchantPaymentHandle {
    merchantPaymentHandle {
      __typename
      ... on MerchantPaymentHandleSuccessResponse {
        paymentHandle {
          title
          paymentHandleSlug
          url
        }
        code
        success
        message
      }
      ... on MerchantPaymentHandleFailureResponse {
        code
        success
        message
      }
    }
  }
`;

/**
 * Query to get suggested payment handle options for the merchant
 * @param suggestionsCountInput - Number of suggestions to retrieve
 */
export const MERCHANT_PAYMENT_HANDLE_SUGGESTION_QUERY = gql`
  query merchantPaymentHandleSuggestions($suggestionsCountInput: PositiveInt) {
    merchantPaymentHandleSuggestions(suggestionsCountInput: $suggestionsCountInput) {
      __typename
      suggestions
    }
  }
`;

/**
 * Query to check if a specific payment handle slug is available for use
 * @param paymentHandleSlug - The payment handle slug to check availability for
 */
export const MERCHANT_PAYMENT_HANDLE_AVAILABILITY_QUERY = gql`
  query merchantPaymentHandleAvailability($paymentHandleSlug: String!) {
    merchantPaymentHandleAvailability(paymentHandleSlug: $paymentHandleSlug) {
      ... on MerchantPaymentHandleAvailabilitySuccessResponse {
        __typename
        code
        success
        message
        isPaymentHandleAvailable
      }
      ... on MerchantPaymentHandleAvailabilityFailureResponse {
        __typename
        code
        success
        message
      }
    }
  }
`;

/**
 * Mutation to update the merchant's payment handle
 * @param paymentHandleSlug - The new payment handle slug to set
 */
export const MERCHANT_PAYMENT_HANDLE_UPDATE_MUTATION = gql`
  mutation merchantPaymentHandleUpdate($paymentHandleSlug: String!) {
    merchantPaymentHandleUpdate(paymentHandleSlug: $paymentHandleSlug) {
      __typename
      ... on MerchantPaymentHandleUpdateSuccessResponse {
        paymentHandle {
          title
          paymentHandleSlug
          url
        }
        code
        success
        message
      }
      __typename
      ... on MerchantPaymentHandleUpdateFailureResponse {
        code
        success
        message
      }
    }
  }
`;

/**
 * Mutation to encrypt a payment amount for secure processing
 * @param amount - The payment amount to encrypt in MoneyInput format:
 *                 {
 *                   value: PositiveInt,
 *                   currency: { code: string } // e.g. { code: 'INR' }
 *                 }
 * @returns Object containing the encrypted amount string on success
 */
export const MERCHANT_PAYMENT_HANDLE_ENCRYPTED_AMOUNT_MUTATION = gql`
  mutation merchantPaymentHandleEncryptedAmount($amount: MoneyInput!) {
    merchantPaymentHandleEncryptedAmount(amount: $amount) {
      __typename
      ... on MerchantPaymentHandleEncryptedAmountSuccessResponse {
        code
        message
        success
        encryptedAmount
      }
      ... on MerchantPaymentHandleEncryptedAmountFailureResponse {
        code
        message
        success
      }
    }
  }
`;
