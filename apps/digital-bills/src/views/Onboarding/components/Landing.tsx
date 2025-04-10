import React from 'react';
import {
  Box,
  Text,
  Card,
  Button,
  CardBody,
  Display,
  ArrowRightIcon,
  Heading,
} from '@razorpay/blade/components';

import LandingPageBg from '@apps/digital-bills/src/assets/landing-page.svg';

type LandingProps = {
  updateActiveScreen: () => void;
};

const Landing = ({ updateActiveScreen }: LandingProps) => {
  return (
    <Card elevation="lowRaised" padding="spacing.0" accessibilityLabel="Digital Billing Onboarding" data-analytics-name="digital-bills-landing-section">
      <CardBody>
        <Box
          display="flex"
          height={{ base: '444px', l: '610px' }}
          backgroundColor="surface.background.gray.moderate"
        >
          <Box width="50%" display={{ base: 'none', l: 'block' }}>
            <img src={LandingPageBg} alt="Digital Billing" />
          </Box>
          <Box
            width={{ base: '100%', l: '50%' }}
            display="flex"
            justifyContent="center"
            alignItems="center"
          >
            <Box marginX="46px">
              <Display
                size="medium"
                weight="semibold"
                marginBottom={{ base: 'spacing.7', l: 'spacing.10' }}
              >
                BillMe
              </Display>
              <Heading size="large" weight="semibold" marginBottom="spacing.5">
                Say goodbye to paper bills and unlock business growth.
              </Heading>
              <Text size="large" weight="medium" marginBottom="spacing.11">
                Integration with any POS and billing system takes less than 10 mins. Start sending
                personlized digital bills almost instantly to engage customers and drive repeat
                purchases.
              </Text>
              <Box textAlign="right">
                <Button
                  size="large"
                  icon={ArrowRightIcon}
                  iconPosition="right"
                  onClick={updateActiveScreen}
                  data-analytics-name="read-more"
                >
                  Read More
                </Button>
              </Box>
            </Box>
          </Box>
        </Box>
      </CardBody>
    </Card>
  );
};

export default Landing;
