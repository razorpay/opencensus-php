import { gql } from 'graphql-tag';
export const BILLS_TABLE_DATA_QUERY = gql`
  query bills(
    $limit: PositiveInt!
    $offset: NonNegativeInt!
    $storeIds: [String]
    $storeCode: String
    $invoiceNumber: String
    $user: BillUserInput
    $transactionType: [BillTransactionTypeEnum!]!
    $minAmount: Float
    $maxAmount: Float
    $status: [BillStatusEnum!]!
    $fromDate: DateTime
    $toDate: DateTime
  ) {
    bills(
      limit: $limit
      offset: $offset
      storeIds: $storeIds
      storeCode: $storeCode
      invoiceNumber: $invoiceNumber
      user: $user
      transactionType: $transactionType
      minAmount: $minAmount
      maxAmount: $maxAmount
      status: $status
      fromDate: $fromDate
      toDate: $toDate
    ) {
      bills {
        user {
          email
          phone {
            countryCode
            number
          }
        }
        legacyEntityId
        store {
          name
          storeInfo {
            storeCode
          }
          address {
            displayAddress
          }
          id
          platform
        }
        brand {
          name
          logo
          id
        }
        category {
          id
        }
        dates {
          createdAt
        }
        deliveryReport {
          sms {
            channel
            receiver
            createdAt
          }
          whatsapp {
            channel
            receiver
            createdAt
          }
          email {
            channel
            receiver
            createdAt
          }
        }
        id
        invoice {
          number
          amount {
            currency {
              code
              name
            }
            value
          }
        }
        transactionType
        platform
        deliveryStatus {
          email
          sms
          whatsapp
        }
      }
      limit
      offset
      total
    }
  }
`;

export const STORES_AGGREGATION_DATA_QUERY = gql`
  query BillStoreAggregation(
    $limit: PositiveInt!
    $offset: NonNegativeInt!
    $storeIds: [String]
    $storeCode: String
    $storeName: String
    $minAmount: Float
    $maxAmount: Float
    $status: StoreStatusEnum
    $fromDate: DateTime
    $toDate: DateTime
  ) {
    billStoresAggregation(
      limit: $limit
      offset: $offset
      storeIds: $storeIds
      storeCode: $storeCode
      storeName: $storeName
      minAmount: $minAmount
      maxAmount: $maxAmount
      status: $status
      fromDate: $fromDate
      toDate: $toDate
    ) {
      stores {
        store {
          id
          name
          address {
            displayAddress
          }
          brand {
            logo
          }
          storeInfo {
            storeCode
          }
          isActive
        }
        salesInfo {
          totalSales
          averageSales
        }
        totalTransactions
        transactionInfo {
          DIGITAL
          PRINT
          DIGITAL_PRINT
        }
      }
      limit
      offset
      total
    }
  }
`;

export const STORE_GROUP_DATA_QUERY = gql`
  query StoreGroups($limit: PositiveInt!, $offset: NonNegativeInt!) {
    storeGroups(limit: $limit, offset: $offset) {
      storeGroups {
        id
        name
        stores {
          id
        }
      }
    }
  }
`;

export const STORES_DATA_QUERY = gql`
  query getStores($limit: PositiveInt!, $offset: NonNegativeInt!, $isDeleted: Boolean) {
    stores(limit: $limit, offset: $offset, isDeleted: $isDeleted) {
      limit
      offset
      total
    }
  }
`;
