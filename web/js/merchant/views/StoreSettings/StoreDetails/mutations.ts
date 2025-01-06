import { gql } from 'graphql-tag';

export const DELETE_STORE_BY_ID = gql`
  mutation StoreDeleteById($id: ID!) {
    storeDelete(id: $id) {
      code
      success
      message
    }
  }
`;
