import { gql } from 'graphql-tag';

export const BILL_BULK_DELETE = gql`
  mutation BillBulkDelete($ids: [ID!]!) {
    billBulkDelete(ids: $ids) {
      code
      message
      success
    }
  }
`;

export const BILL_RESEND = gql`
  mutation BillResend($id: ID!, $email: EmailAddress, $phone: PhoneInput) {
    billResend(id: $id, email: $email, phone: $phone) {
      success
      code
      message
    }
  }
`;

export const BILL_DELETE_BY_ID = gql`
  mutation BillDeleteById($id: ID!) {
    billDeleteById(id: $id) {
      code
      message
      success
    }
  }
`;
