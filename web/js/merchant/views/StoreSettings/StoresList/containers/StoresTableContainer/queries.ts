import { gql } from 'graphql-tag';

export const STORES_DATA_QUERY = gql`
  query getStores(
    $limit: PositiveInt!
    $offset: NonNegativeInt!
    $searchTerm: String
    $linkedProducts: [LinkedProductsEnum!]
    $storeType: StoreTypeEnum
    $storeGroupId: ID
    $searchColumn: StoreSearchColumnEnum
    $isDeleted: Boolean
  ) {
    stores(
      limit: $limit
      offset: $offset
      searchTerm: $searchTerm
      linkedProducts: $linkedProducts
      storeType: $storeType
      storeGroupId: $storeGroupId
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
          linkedProducts
          storeCode
          storeType
        }
        dates {
          deletedAt
        }
      }
    }
  }
`;
