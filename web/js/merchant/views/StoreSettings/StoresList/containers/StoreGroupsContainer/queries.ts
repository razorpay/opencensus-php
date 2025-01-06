import { gql } from 'graphql-tag';

export const STORE_GROUPS_DATA_QUERY = gql`
  query StoreGroups($limit: PositiveInt!, $offset: NonNegativeInt!) {
    storeGroups(limit: $limit, offset: $offset) {
      limit
      offset
      total
      storeGroups {
        id
        name
        description
        isActive
        stores {
          id
          name
          storeInfo {
            storeCode
          }
        }
      }
    }
  }
`;

export const STORE_GROUP_DATA_QUERY = gql`
  query StoreGroupById($id: ID!) {
    storeGroupById(id: $id) {
      id
      name
      description
      isActive
      stores {
        id
        name
        storeInfo {
          storeCode
        }
      }
    }
  }
`;

export const STORES_LIST_DATA_QUERY = gql`
  query getStores(
    $limit: PositiveInt!
    $offset: NonNegativeInt!
    $searchTerm: String
    $states: [String!]
    $cities: [String!]
    $searchColumn: StoreSearchColumnEnum
    $isDeleted: Boolean
  ) {
    stores(
      limit: $limit
      offset: $offset
      searchTerm: $searchTerm
      states: $states
      cities: $cities
      searchColumn: $searchColumn
      isDeleted: $isDeleted
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
