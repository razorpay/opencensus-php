// TODO: to delete this file and consume from Store Create page after the respective PR is merged

import { gql } from 'graphql-tag';

export const STORE_BY_ID = gql`
  query storeById($id: ID!) {
    storeById(id: $id) {
      id
      name
      address {
        displayAddress
        line1
        city
        country
        zipcode
        state
      }
      brand {
        id
        name
      }
      storeInfo {
        storeCode
        storeInCharge
        storeType
        linkedProducts
        email
        websiteUrl
      }
      business {
        fssaiLicNumber
        gstNumber
        cinNumber
      }
      customFields {
        title
        value
      }
      isActive
      registeredFrom
      dates {
        createdAt
        deletedAt
        updatedAt
      }
      platform
      contact {
        primary {
          countryCode
          number
        }
        secondary {
          number
          countryCode
        }
      }
    }
  }
`;

export const TERMINAL_BY_STORE_ID = gql`
  query terminalsByStoreId($storeIds: [ID!], $limit: PositiveInt!, $offset: NonNegativeInt!) {
    storeTerminals(storeIds: $storeIds, limit: $limit, offset: $offset) {
      storeTerminals {
        id
        name
        isActive
        terminalInfo {
          macAddress
          ipAddress
          licenseKey
        }
      }
    }
  }
`;
