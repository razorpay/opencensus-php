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

export { SMS_NOTIFICATION_STATUS_QUERY, SMS_NOTIFICATION_TOGGLE_MUTATION };
