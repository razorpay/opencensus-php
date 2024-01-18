import { gql } from 'graphql-tag';

const SMS_NOTIFICATION_STATUS_QUERY = gql`
  query smsNotificationStatus {
    smsNotificationStatus {
      code
      success
      message
      isEnabled
    }
  }
`;

const SMS_NOTIFICATION_TOGGLE_MUTATION = gql`
  mutation smsNotificationToggle($toggleValue: Boolean!) {
    smsNotificationToggle(toggleValue: $toggleValue) {
      code
      success
      message
    }
  }
`;

const NOTIFICATION_EMAIL_UPDATE_MUTATION = gql`
  mutation notificationEmailUpdate($transactionReportEmail: [EmailAddress!]!) {
    notificationEmailUpdate(transactionReportEmail: $transactionReportEmail) {
      ... on NotificationEmailUpdateSuccessResponse {
        __typename
        code
        success
        message
        transactionReportEmail
      }
      ... on NotificationEmailUpdateFailureResponse {
        __typename
        code
        success
        message
      }
    }
  }
`;

export {
  SMS_NOTIFICATION_STATUS_QUERY,
  SMS_NOTIFICATION_TOGGLE_MUTATION,
  NOTIFICATION_EMAIL_UPDATE_MUTATION,
};
