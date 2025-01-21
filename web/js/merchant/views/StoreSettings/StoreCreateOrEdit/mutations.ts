import { gql } from 'graphql-tag';
const STORE_CREATE = gql`
  mutation createStore(
    $brandId: ID
    $primaryContact: PhoneInput
    $storeCode: String!
    $name: String!
    $business: StoreBusinessInput
    $address: StoreAddressInput
    $isActive: Boolean
    $platform: StorePlatformEnum
    $registeredFrom: RegisteredFromEnum
    $secondaryContact: PhoneInput
    $storeType: StoreTypeEnum
    $customFields: [CustomFieldInput!]
    $websiteUrl: String
    $linkedProducts: [LinkedProductsEnum!]
    $storeEmail: EmailAddress
    $storeInCharge: String
  ) {
    storeCreate(
      brandId: $brandId
      primaryContact: $primaryContact
      storeCode: $storeCode
      name: $name
      business: $business
      address: $address
      isActive: $isActive
      platform: $platform
      registeredFrom: $registeredFrom
      secondaryContact: $secondaryContact
      storeType: $storeType
      customFields: $customFields
      websiteUrl: $websiteUrl
      linkedProducts: $linkedProducts
      storeEmail: $storeEmail
      storeInCharge: $storeInCharge
    ) {
      code
      success
      message
      store {
        id
        name
        address {
          city
          country
          zipcode
          line1
          state
        }
        brand {
          id
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
  }
`;

const STORE_UPDATE = gql`
  mutation updateStore(
    $id: ID!
    $brandId: ID
    $primaryContact: PhoneInput
    $storeCode: String
    $name: String
    $business: StoreBusinessInput
    $address: StoreAddressInput
    $isActive: Boolean
    $platform: StorePlatformEnum
    $registeredFrom: RegisteredFromEnum
    $secondaryContact: PhoneInput
    $storeType: StoreTypeEnum
    $customFields: [CustomFieldInput!]
    $websiteUrl: String
    $linkedProducts: [LinkedProductsEnum!]
    $storeEmail: EmailAddress
    $storeInCharge: String
  ) {
    storeUpdate(
      id: $id
      brandId: $brandId
      primaryContact: $primaryContact
      storeCode: $storeCode
      name: $name
      business: $business
      address: $address
      isActive: $isActive
      platform: $platform
      registeredFrom: $registeredFrom
      secondaryContact: $secondaryContact
      storeType: $storeType
      customFields: $customFields
      websiteUrl: $websiteUrl
      linkedProducts: $linkedProducts
      storeEmail: $storeEmail
      storeInCharge: $storeInCharge
    ) {
      code
      success
      message
      store {
        id
        name
        address {
          city
          country
          zipcode
          line1
          state
        }
        brand {
          id
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
  }
`;

const STORE_TERMINALS_STATUS_BULK_UPDATE = gql`
  mutation storeTerminalsStatusBulkUpdate($storeId: ID!, $isActive: Boolean) {
    storeTerminalsStatusBulkUpdate(storeId: $storeId, isActive: $isActive) {
      code
      success
      message
    }
  }
`;

const STORE_TERMINAL_CREATE = gql`
  mutation StoreTerminalCreate(
    $name: String!
    $storeId: String!
    $macAddress: String
    $ipAddress: String
    $type: StoreTerminalTypeEnum
  ) {
    storeTerminalCreate(
      name: $name
      storeId: $storeId
      macAddress: $macAddress
      ipAddress: $ipAddress
      type: $type
    ) {
      code
      success
      message
      storeTerminal {
        id
        name
        isActive
        terminalInfo {
          ipAddress
          macAddress
          licenseKey
        }
      }
    }
  }
`;

const STORE_TERMINAL_UPDATE = gql`
  mutation StoreTerminalUpdate(
    $id: ID!
    $name: String
    $storeId: String!
    $macAddress: String
    $ipAddress: String
    $isActive: Boolean
  ) {
    storeTerminalUpdate(
      id: $id
      name: $name
      storeId: $storeId
      macAddress: $macAddress
      ipAddress: $ipAddress
      isActive: $isActive
    ) {
      code
      success
      message
      storeTerminal {
        id
        name
        isActive
        terminalInfo {
          ipAddress
          macAddress
          licenseKey
        }
      }
    }
  }
`;

const STORE_TERMINAL_DELETE = gql`
  mutation StoreTerminalDelete($id: ID!) {
    storeTerminalDelete(id: $id) {
      message
      code
      success
    }
  }
`;
export {
  STORE_CREATE,
  STORE_UPDATE,
  STORE_TERMINAL_CREATE,
  STORE_TERMINAL_UPDATE,
  STORE_TERMINAL_DELETE,
  STORE_TERMINALS_STATUS_BULK_UPDATE,
};
