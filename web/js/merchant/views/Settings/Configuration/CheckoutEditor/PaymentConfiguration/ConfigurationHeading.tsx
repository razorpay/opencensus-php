import React from 'react';
import { ArrowUpRightIcon, Box, Heading, Link, Text } from '@razorpay/blade/components';

export function ConfigurationHeading() {
  return (
    <Box display="flex" flexDirection="column" gap="spacing.1" marginBottom="spacing.8">
      <Heading weight="semibold" size="xlarge">
        Payment Configuration
      </Heading>
      <Box display="flex" flexGrow="1">
        <Box flexGrow="1">
          <Text size="small" color="surface.text.gray.muted">
            Control how your customers pay on your checkout
          </Text>
        </Box>
        <Link
          variant="anchor"
          size="small"
          color="primary"
          iconPosition="right"
          icon={ArrowUpRightIcon}
          target="_blank"
          href="https://razorpay.com/docs/payments/dashboard/account-settings/payment-methods"
        >
          Setup Guide
        </Link>
      </Box>
    </Box>
  );
}
