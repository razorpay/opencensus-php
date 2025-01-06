import { gql } from 'graphql-tag';

export const BRANDS_TABLE_DATA_QUERY = gql`
  query Brands(
    $limit: PositiveInt!
    $offset: NonNegativeInt!
    $searchTerm: String
    $searchColumn: BrandSearchColumnEnum
  ) {
    brands(limit: $limit, offset: $offset, searchTerm: $searchTerm, searchColumn: $searchColumn) {
      limit
      offset
      total
      brands {
        id
        name
        logo
        description
      }
    }
  }
`;

export const BRAND_BY_ID_DATA_QUERY = gql`
  query BrandById($id: ID!) {
    brandById(id: $id) {
      id
      name
      logo
      description
    }
  }
`;
