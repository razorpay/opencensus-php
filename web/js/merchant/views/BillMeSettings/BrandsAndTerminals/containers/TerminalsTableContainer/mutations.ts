import { gql } from 'graphql-tag';

const UPDATE_TERMINAL_MUTATION = gql`
  mutation StoreTerminalUpdate($id: ID!, $isActive: Boolean) {
    storeTerminalUpdate(id: $id, isActive: $isActive) {
      code
      success
      message
      storeTerminal {
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
          }
        }
      }
    }
  }
`;

export { UPDATE_TERMINAL_MUTATION };
