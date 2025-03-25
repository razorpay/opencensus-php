import React from 'react';
import { Box, Heading, Text, Button, Alert, CheckIcon, InfoIcon } from '@razorpay/blade/components';
import {
  BUYER_PROTECT_BLOG,
  CUSTOMER_TRUST_BANNER,
  CUSTOMER_TRUST_INFO_LIST,
  MONEY_BACK_BADGE,
} from 'merchant/views/CustomerTrust/constants';
import { handleInterestedClick } from 'merchant/views/CustomerTrust/utils/helpers';
import {
  OnboardingStatus,
  SetOnboardingStatus,
} from 'merchant/views/CustomerTrust/types';

const LandingPage = ({
  onboardingStatus,
  setOnboardingStatus,
  show,
}: {
  onboardingStatus: OnboardingStatus;
  setOnboardingStatus: SetOnboardingStatus;
  show: any
}) => {

  return (
    <Box
      display="flex"
      elevation="lowRaised"
      marginRight="spacing.7"
      marginTop="spacing.7"
      borderRadius="large"
    >
      <img
        src={CUSTOMER_TRUST_BANNER}
        alt="Customer Trust Banner"
        width="662px"
        height="565px"
        style={{ borderTopLeftRadius: '8px', borderBottomLeftRadius: '8px' }}
      />
      <Box
        padding="spacing.10"
        backgroundColor="surface.background.gray.moderate"
        borderTopRightRadius="large"
        borderBottomRightRadius="large"
      >
        <img src={MONEY_BACK_BADGE} alt="Buyer Protect Badge" width="32px" height="32px" />
        <Text size="small" color="surface.text.gray.muted" marginTop="spacing.7">
          Razorpay Buyer Protection
        </Text>
        <Heading size="large" marginTop="spacing.3">
          Build trust instantly with the Money-Back Promise widget on your product page.
        </Heading>
        <Box marginTop="spacing.7">
          {CUSTOMER_TRUST_INFO_LIST.map((info) => (
            <Box key={info.title} display="flex" gap="spacing.2" marginBottom="spacing.5">
              <CheckIcon
                size="medium"
                color="feedback.icon.positive.intense"
                marginTop="spacing.1"
              />
              <Box>
                <Text size="medium" weight="semibold" color="surface.text.gray.subtle">
                  {info.title}
                </Text>
                <Text size="small" color="surface.text.gray.muted" marginTop="spacing.1">
                  {info.description}
                </Text>
              </Box>
            </Box>
          ))}
        </Box>
        <Box height="96px" paddingTop="spacing.3">
          {onboardingStatus === 'completed' && (
            <Alert
              description="A member of our Sales team will reach out soon to provide more details and answer any questions you may have."
              color="positive"
              icon={InfoIcon}
              isFullWidth
            />
          )}
        </Box>
        <Box display="flex" gap="spacing.3">
          <Box flex="1">
            <Button
              variant="secondary"
              size="medium"
              isFullWidth
              onClick={() => window.open(BUYER_PROTECT_BLOG, '_blank', 'noopener noreferrer')}
            >
              Learn more
            </Button>
          </Box>
          <Box flex="1">
            {onboardingStatus === 'completed' ? (
              <Button
                variant="primary"
                isFullWidth
                size="medium"
                icon={CheckIcon}
                iconPosition="right"
                isDisabled
              >
                Interest Confirmed
              </Button>
            ) : (
              <Button
                variant="primary"
                size="medium"
                isFullWidth
                onClick={() =>
                  handleInterestedClick(onboardingStatus, setOnboardingStatus, show)
                }
              >
                I am Interested
              </Button>
            )}
          </Box>
        </Box>
      </Box>
    </Box>
  );
};

export default LandingPage;
