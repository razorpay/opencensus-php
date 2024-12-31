import React from 'react';
import { Box, Link, ArrowUpRightIcon, Text } from '@razorpay/blade/components';

type integrationReferenceT = {
  link: string;
  title: string;
  subtitle: string;
};

const referenceLinks: integrationReferenceT[] = [
  {
    link: 'https://razorpay.com/docs/payments/payment-gateway/#types-of-checkout',
    title: 'Types of Checkout',
    subtitle: 'View different types of payment gateways.',
  },
  {
    link: 'https://razorpay.com/docs/payments/payment-gateway/how-it-works/',
    title: 'How to integrate Payment Gateway',
    subtitle: 'View integration flow, try out checkout demo or watch demo video.',
  },
  {
    link: 'https://razorpay.com/docs/payments/payment-gateway/web-integration/standard/integration-steps/',
    title: 'Build Integration',
    subtitle: 'Follow step by step for guide and build checkout.',
  },
];

const IntegrationLinks = () => {
  return (
    <Box>
      <Box>Follow these links to build payment gateway or share with your developer</Box>
      <Box display="flex" flexDirection="column" gap="spacing.4" marginTop="spacing.4">
        {referenceLinks.map((reference, index) => (
          <Box key={reference.title}>
            <Box display="flex" flexDirection="row" alignItems="center" gap="spacing.2">
              <Text weight="semibold" size="medium" color="surface.text.staticBlack.muted">
                {index + 1}.
              </Text>
              <Text size="large">
                <Link
                  href={reference.link}
                  target="_blank"
                  iconPosition="right"
                  icon={ArrowUpRightIcon}
                >
                  {reference.title}
                </Link>
              </Text>
            </Box>
            <Text
              weight="regular"
              size="small"
              color="surface.text.gray.muted"
              marginTop="spacing.1"
            >
              {reference.subtitle}
            </Text>
          </Box>
        ))}
      </Box>
    </Box>
  );
};

export default IntegrationLinks;
