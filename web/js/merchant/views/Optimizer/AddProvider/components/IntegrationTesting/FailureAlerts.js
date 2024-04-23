import React from 'react';
import { Alert, Text } from '@razorpay/blade/components';

export const PaymentWebhookFailureAlert = ({ gateway }) => {
  return (
    <Alert
      description={
        <Text size="small">
          Please ensure that your webhooks have been configured correctly on your {gateway}
          account. You may reach out to your {gateway} account manager or {gateway} support team for
          assistance.
        </Text>
      }
      isDismissible={false}
      color="negative"
      actions={{
        primary: {
          onClick: () => {
            window.open('https://razorpay.com/docs/webhooks/', '_blank');
          },
          text: 'Know more',
        },
      }}
      marginTop="spacing.4"
    />
  );
};

export const PaymentFailureAlert = ({ paymentError }) => {
  return (
    <Alert
      title={<Text weight="semibold">Payment failed</Text>}
      description={<Text size="small">{paymentError}</Text>}
      isDismissible={false}
      color="negative"
      marginTop="spacing.4"
    />
  );
};
