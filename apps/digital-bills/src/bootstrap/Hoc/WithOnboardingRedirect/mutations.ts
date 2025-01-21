import { gql } from 'graphql-tag';

export const JOIN_WAITLIST = gql`
  mutation merchantJoinWaitlist(
    $minStoreCount: Int!
    $maxStoreCount: Int
    $productType: OnboardingProductTypeEnum!
  ) {
    merchantJoinWaitlist(
      minStoreCount: $minStoreCount
      maxStoreCount: $maxStoreCount
      productType: $productType
    ) {
      code
      success
      message
    }
  }
`;
