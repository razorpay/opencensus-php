import React from 'react';
import { Box, Heading, Divider } from '@razorpay/blade/components';
import DesktopHeaderImg from 'apps/onboarding-experience/src/assets/WaysToAcceptPaymentDesktop.svg';
import MobileHeaderImg from 'apps/onboarding-experience/src/assets/WaysToAcceptPaymentMobile.svg';
import { isMobileDevice } from '@libs/shared-utils';

const WaysToAcceptPayment = () => {
  const isMobile = isMobileDevice();

  return (
    <Box display="flex" flexDirection="column" justifyContent="center" gap="spacing.4">
      <Box width="100%" display="flex" flexDirection="row" alignItems="center" marginY="spacing.4">
        <Divider />
        {isMobile ? (
          <Box marginX="spacing.2">
            <img src={MobileHeaderImg} alt="header-icon" width="85px" />
          </Box>
        ) : (
          <Box paddingX="spacing.5" display="flex" alignItems="center" gap="spacing.4">
            <img src={DesktopHeaderImg} alt="header-icon-left" width="85px" />
            <Heading
              color="surface.text.gray.normal"
              weight="semibold"
              size="large"
              textAlign="center"
            >
              More ways to accept payments
            </Heading>
            <Box transform="rotate(180deg)">
              <img src={DesktopHeaderImg} alt="header-icon-right" width="85px" />
            </Box>
          </Box>
        )}
        <Divider />
      </Box>
      {isMobile && (
        <Heading color="surface.text.gray.normal" weight="semibold" size="large" textAlign="center">
          More ways to accept payments
        </Heading>
      )}
    </Box>
  );
};

export default WaysToAcceptPayment;
