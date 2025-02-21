import React from 'react';
import { ArrowUpRightIcon, Box, Heading, Link, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose } from 'redux';

export type ConfigurationHeadingProps = {
  org: { business_name: string };
};

function ConfigurationHeading({ org }: ConfigurationHeadingProps) {
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
          href={`https://${org.business_name.toLowerCase()}.com/docs/payments/payment-gateway/web-integration/standard/configure-payment-methods`}
        >
          Setup Guide
        </Link>
      </Box>
    </Box>
  );
}

export default compose(connect((state) => ({ org: state.session.org })))(ConfigurationHeading);
