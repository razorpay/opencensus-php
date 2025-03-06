import { gql } from 'graphql-tag';

export const BILL_BY_ID_DATA_QUERY = gql`
  query BillById($id: ID!) {
    billById(id: $id) {
      category {
        id
      }
      deliveryReport {
        sms {
          channel
          receiver
          createdAt
          deliveredAt
          status
        }
        whatsapp {
          channel
          receiver
          createdAt
          deliveredAt
          status
        }
        email {
          channel
          receiver
          createdAt
          deliveredAt
          status
        }
      }
      dates {
        createdAt
      }
      feedback {
        id
      }
      id
      invoice {
        amount {
          value
          currency {
            code
            name
          }
        }
        number
      }
      platform
      store {
        address {
          displayAddress
        }
        id
      }
      brand {
        name
        logo
        id
      }
      visits {
        userAgent
        ip
        visitedAt
        source
      }
      user {
        id
        name
        email
        phone {
          countryCode
          number
        }
        address {
          billing {
            id
            isPrimary
            line1
            line2
            city
            state
            country
            zipcode
          }
          shipping {
            id
            isPrimary
            line1
            line2
            city
            state
            country
            zipcode
          }
        }
      }
      transactionType
      deliveryStatus {
        sms
        whatsapp
        email
      }
      signedToken
    }
  }
`;
