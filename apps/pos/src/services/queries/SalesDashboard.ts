import { gql } from 'graphql-tag';

export const SALES_ONBOARDED_MERCHANTS = gql`
  query SalesOnboardedMerchants(
    $limit: PositiveInt!
    $offset: NonNegativeInt!
    $startDate: PositiveInt!
    $endDate: PositiveInt!
    $status: String!
  ) {
    salesOnboardedMerchants(
      limit: $limit
      offset: $offset
      startDate: $startDate
      endDate: $endDate
      status: $status
    ) {
      ... on SalesOnboardedMerchants {
        __typename
        limit
        offset
        total
        hasMore
        merchants {
          createdAt
          merchantId
          merchantName
          merchantMobile
          progressCompletion
          status
        }
      }
      ... on SalesOnboardedMerchantsError {
        __typename
        code
        success
        message
      }
    }
  }
`;
