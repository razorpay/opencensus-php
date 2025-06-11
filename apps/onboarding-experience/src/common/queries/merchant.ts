import { gql } from 'graphql-tag';

/**
 * GraphQL fragment containing essential merchant data
 * This fragment contains merchant identifying information, activation status,
 * API keys, and business channels data for reuse across queries
 */
const MERCHANT_FRAGMENT = gql`
  fragment MerchantFragment on Merchant {
    id
    name {
      billing
      registered
      display
    }
    contactPerson {
      name {
        value
      }
    }
    createdAt
    activation {
      bddVerificationStatus
      status
      isActivated
      isTransacted
    }
    hasApiKeyAccess
    apiKeys {
      id
      createdAt
      updatedAt
      expiredAt
    }
    business {
      paymentAcceptanceChannels {
        websites {
          urls {
            value
          }
          accept
        }
        ios {
          urls {
            value
          }
          accept
        }
        android {
          urls {
            value
          }
          accept
        }
        offlineStore {
          accept
        }
        socialMedia {
          accept
          socialMediaUrls {
            url
            platform
          }
        }
        whatsappSmsEmail {
          accept
        }
        others {
          accept
          value
        }
      }
    }
  }
`;

/**
 * GraphQL query to fetch merchant activation data
 */
export const MERCHANT_DETAILS_QUERY = gql`
  query merchantById($id: ID!) {
    merchantById(id: $id) {
      ...MerchantFragment
    }
  }
  ${MERCHANT_FRAGMENT}
`;
