import { gql } from 'graphql-tag';

export const CREATE_STORE_GROUP_MUTATION = gql`
  mutation StoreGroupCreate($name: String!, $description: String, $stores: [ID!]!) {
    storeGroupCreate(name: $name, description: $description, stores: $stores) {
      code
      success
      message
      storeGroup {
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

export const UPDATE_STORE_GROUP_MUTATION = gql`
  mutation StoreGroupUpdate($id: ID!, $name: String, $description: String, $stores: [ID!]) {
    storeGroupUpdate(id: $id, name: $name, description: $description, stores: $stores) {
      code
      success
      message
      storeGroup {
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

export const DELETE_STORE_GROUP_MUTATION = gql`
  mutation StoreGroupDelete($id: ID!) {
    storeGroupDelete(id: $id) {
      code
      success
      message
    }
  }
`;
