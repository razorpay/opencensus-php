import { gql } from 'graphql-tag';

export const STORES_LIST_DATA_QUERY = gql`
  query getStores(
    $limit: PositiveInt!
    $offset: NonNegativeInt!
    $searchTerm: String
    $states: [String!]
    $cities: [String!]
    $searchColumn: StoreSearchColumnEnum
    $isDeleted: Boolean
    $brandIds: [ID!]
    $isActive: Boolean
  ) {
    stores(
      limit: $limit
      offset: $offset
      searchTerm: $searchTerm
      states: $states
      cities: $cities
      searchColumn: $searchColumn
      isDeleted: $isDeleted
      brandIds: $brandIds
      isActive: $isActive
    ) {
      limit
      offset
      total
      stores {
        id
        name
        storeInfo {
          storeCode
        }
        dates {
          deletedAt
        }
      }
    }
  }
`;

export const STORES_STATES_AND_CITIES_QUERY = gql`
  query StoresStatesAndCitiesByMerchantId {
    storesStatesAndCitiesByMerchantId {
      states
      cities
    }
  }
`;

export const BRANDS_LIST_DATA_QUERY = gql`
  query StoreBrands($limit: PositiveInt!, $offset: NonNegativeInt!) {
    storeBrands(limit: $limit, offset: $offset) {
      limit
      offset
      total
      storeBrands {
        id
        name
      }
    }
  }
`;
