import React from 'react';
import {
  Text,
  Card,
  CardBody,
  Button,
  BookIcon,
  MailIcon,
  Box,
  Heading,
} from '@razorpay/blade/components';
import FeedbackForm from 'merchant/views/MagicCheckout/MagicDashboard/SupportAndFeedback/components/FeedbackForm';
import { OnboardedMerchantTabHeader } from 'merchant/views/MagicCheckout/Settings/containers/MagicXControlCenter/styled';

const SupportAndFeedback = () => {
  return (
    <Box paddingBottom="spacing.2">
      <OnboardedMerchantTabHeader>
        <Box display="flex" flexDirection="column" gap="spacing.3">
          <Heading size="xlarge" weight="semibold">
            Support & Feedback
          </Heading>
          <Text weight="regular" color="surface.text.gray.subtle">
            Get help with your Razorpay account or share your feedback with us
          </Text>
        </Box>
      </OnboardedMerchantTabHeader>

      <Card margin="spacing.7">
        <CardBody>
          <Text weight="semibold">Have an issue?</Text>
          <Text marginTop="spacing.2" color="surface.text.gray.muted">
            Connect with our support team for quick resolution of any problems you're facing with
            your Razorpay account
          </Text>
          <Box
            display="flex"
            flexDirection="column"
            alignItems="flex-end"
            marginTop="spacing.6"
            marginLeft="auto"
            width="220px"
          >
            <Button
              variant="primary"
              icon={BookIcon}
              iconPosition="left"
              isFullWidth
              href="https://razorpay.com/docs/payments/cod-magic-checkout/"
            >
              See Documentation
            </Button>
            <Button
              variant="primary"
              icon={MailIcon}
              iconPosition="left"
              isFullWidth
              marginTop="spacing.3"
              onClick={() => (window.location.href = 'mailto:magic-checkout-support@razorpay.com')}
            >
              Contact Magic Support
            </Button>
          </Box>
        </CardBody>
      </Card>

      <FeedbackForm />
    </Box>
  );
};

export default SupportAndFeedback;
