import { gql } from 'graphql-tag';

export const BRANDS_TABLE_DATA_QUERY = gql`
  query StoreBrands(
    $limit: PositiveInt!
    $offset: NonNegativeInt!
    $searchTerm: String
    $searchColumn: StoreBrandSearchColumnEnum
  ) {
    storeBrands(
      limit: $limit
      offset: $offset
      searchTerm: $searchTerm
      searchColumn: $searchColumn
    ) {
      limit
      offset
      total
      storeBrands {
        id
        name
        logo
        description
      }
    }
  }
`;

export const BRAND_BY_ID_DATA_QUERY = gql`
  query StoreBrandById($id: ID!) {
    storeBrandById(id: $id) {
      id
      name
      logo
      description
    }
  }
`;
