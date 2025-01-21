import { gql } from 'graphql-tag';

export const GET_ONBOARDING_STATUS_QUERY = gql`
  query getMerchantOnboardingStatus($productType: OnboardingProductTypeEnum!) {
    merchantOnboardingStatus(productType: $productType)
  }
`;
