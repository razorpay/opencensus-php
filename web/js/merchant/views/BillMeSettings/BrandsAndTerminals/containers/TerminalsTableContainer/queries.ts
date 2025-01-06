import { gql } from 'graphql-tag';

export const TERMINALS_TABLE_DATA_QUERY = gql`
  query storeTerminals(
    $limit: PositiveInt!
    $offset: NonNegativeInt!
    $fromDate: DateTime
    $toDate: DateTime
    $isActive: Boolean
    $bit: StoreTerminalBitEnum
    $version: String
    $macAddress: String
    $searchTerm: String
    $type: StoreTerminalTypeEnum
    $searchColumn: StoreTerminalSearchColumnEnum
  ) {
    storeTerminals(
      limit: $limit
      offset: $offset
      fromDate: $fromDate
      toDate: $toDate
      isActive: $isActive
      bit: $bit
      version: $version
      macAddress: $macAddress
      searchTerm: $searchTerm
      type: $type
      searchColumn: $searchColumn
    ) {
      limit
      offset
      total
      storeTerminals {
        id
        name
        isActive
        terminalInfo {
          macAddress
          ipAddress
          version
          bit
          licenseKey
        }
        transactionDates {
          lastTransactionAt
        }
        dates {
          updatedAt
        }
        store {
          name
          storeInfo {
            storeCode
            storeType
            linkedProducts
          }
        }
      }
    }
  }
`;
