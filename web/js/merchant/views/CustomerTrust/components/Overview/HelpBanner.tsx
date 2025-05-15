import React from 'react';
import { Alert, InfoIcon } from '@razorpay/blade/components';

export const HelpBanner = () => {
  return (
    <Alert
      isFullWidth={true}
      color="information"
      icon={InfoIcon}
      description="Need assistance with onboarding? Reach out to us on this Email ID: magicsales@razorpay.com"
      isDismissible={false}
      actions={{
        primary: {
          text: 'Write an email',
          onClick: () => {
            window.open('mailto:magicsales@razorpay.com', '_blank');
          },
        },
      }}
    />
  );
};
