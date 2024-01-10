import React from 'react';
import { Box, Divider, Heading, Text, Title } from '@razorpay/blade/components';

import { TERMS_AND_CONDITIONS } from 'merchant/views/POS/constants';

const TermsAndConditions = (): JSX.Element => {
  return (
    <Box width="100%" maxWidth="1200px" marginBottom="spacing.8">
      <Box marginBottom="spacing.5">
        <Title size="medium" textAlign="center">
          Terms & Conditions
        </Title>
      </Box>
      <Box
        display={{ base: 'block', m: 'flex' }}
        backgroundColor="surface.background.level2.lowContrast"
        padding="spacing.5"
        borderRadius="medium"
      >
        {TERMS_AND_CONDITIONS.map(({ criteria, rows }, index) => (
          <Box key={criteria} width={{ base: '100%', m: '50%' }} marginBottom="spacing.5">
            <Heading marginBottom="spacing.4" marginLeft="spacing.8">
              {criteria}
            </Heading>
            <Divider marginBottom="spacing.4" />
            <Box
              borderRightWidth={{
                base: 'none',
                m: index !== TERMS_AND_CONDITIONS.length - 1 ? 'thick' : 'none',
              }}
              borderRightColor="brand.gray.400.lowContrast"
              paddingX={{ base: 'spacing.0', m: 'spacing.8' }}
            >
              {rows.map((row, rowNumber) => (
                <Box key={row} display="flex">
                  <Text marginRight="spacing.3"> {rowNumber + 1}.</Text>
                  <Text marginBottom="spacing.4">{row}</Text>
                </Box>
              ))}
            </Box>
          </Box>
        ))}
      </Box>
    </Box>
  );
};

export default TermsAndConditions;
