import { gql } from 'graphql-tag';

export const CREATE_BRAND_MUTATION = gql`
  mutation BrandCreate($logo: String, $name: String!, $description: String) {
    brandCreate(logo: $logo, name: $name, description: $description) {
      code
      success
      message
      brand {
        id
        name
        logo
        description
      }
    }
  }
`;

export const UPDATE_BRAND_MUTATION = gql`
  mutation BrandUpdate($id: ID!, $logo: String, $name: String, $description: String) {
    brandUpdate(id: $id, logo: $logo, name: $name, description: $description) {
      code
      success
      message
      brand {
        id
        name
        logo
        description
      }
    }
  }
`;

export const GET_PRE_SIGNED_URL_MUTATION = gql`
  mutation BrandLogoPreSignedUrl($filename: String!) {
    brandLogoPreSignedUrl(filename: $filename) {
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
