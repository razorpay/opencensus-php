import { gql } from 'graphql-tag';

export const CREATE_BRAND_MUTATION = gql`
  mutation StoreBrandCreate($logo: String, $name: String!, $description: String) {
    storeBrandCreate(logo: $logo, name: $name, description: $description) {
      code
      success
      message
      storeBrand {
        id
        name
        logo
        description
      }
    }
  }
`;

export const UPDATE_BRAND_MUTATION = gql`
  mutation StoreBrandUpdate($id: ID!, $logo: String, $name: String, $description: String) {
    storeBrandUpdate(id: $id, logo: $logo, name: $name, description: $description) {
      code
      success
      message
      storeBrand {
        id
        name
        logo
        description
      }
    }
  }
`;

export const GET_PRE_SIGNED_URL_MUTATION = gql`
  mutation StoreBrandLogoPreSignedUrl($filename: String!) {
    storeBrandLogoPreSignedUrl(filename: $filename) {
      code
      success
      message
      preSignedUrlInfo {
        documentId
        presignedUrl
      }
    }
  }
`;
