import React from 'react';
import { Box, Title, Text, Divider } from '@razorpay/blade/components';

import { DETAILED_PRICING } from 'merchant/views/POS/constants';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';

const DetailedPricing = (): JSX.Element => {
  const { isMobile } = useBladeBreakpoints();
  return (
    <Box
      width="100%"
      maxWidth="1200px"
      marginBottom="spacing.8"
      marginX={{ base: 'spacing.5', l: 'spacing.3' }}
    >
      <Box marginBottom="spacing.5">
        <Title size="medium" textAlign="center">
          Detailed Pricing
        </Title>
      </Box>
      {DETAILED_PRICING.map(({ title, rows }) => (
        <Box key={title ?? 'heading'}>
          {title ? (
            <Text weight="bold" size="large" marginBottom="spacing.5">
              {title}
            </Text>
          ) : null}
          <Box
            backgroundColor="surface.background.level2.lowContrast"
            marginBottom="spacing.5"
            borderRadius="medium"
          >
            {rows.map(({ name, value }, index) => (
              <React.Fragment key={name}>
                <Box display="flex" width="100%" padding="spacing.5">
                  <Box width="70%">
                    <Text weight={title ? 'regular' : 'bold'}>{name}</Text>
                  </Box>
                  <Box>
                    <Text weight={title ? 'regular' : 'bold'}>{value}</Text>
                  </Box>
                </Box>
                {index !== rows.length - 1 && rows.length > 1 ? (
                  <Divider marginX="spacing.5" />
                ) : null}
              </React.Fragment>
            ))}
          </Box>
        </Box>
      ))}
      <Box testID="detailed-pricing-footer-text" marginX={isMobile ? 'spacing.5' : 'spacing.0'}>
        <Text>*Transaction Value</Text>
        <Text>
          #Note: Any value added services (Ex- EMI) required by the Merchant shall be charged
          separately, as per the agreed terms. Also, devices once ordered cannot be canceled.
        </Text>
      </Box>
    </Box>
  );
};

export default DetailedPricing;
