import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

import { EscrowDetailsContainer, TitleContainer } from './styles';

const BankDetails = (): JSX.Element => {
  return (
    <Box padding="spacing.7">
      <EscrowDetailsContainer>
        <TitleContainer>
          <Text size="medium" variant="body" weight="semibold">
            Escrow Account
          </Text>
        </TitleContainer>
        <Box
          padding="spacing.6"
          display="grid"
          backgroundColor="surface.background.gray.intense"
          gap="spacing.6"
          columnGap="80px"
        >
          <Box gridRow="1" gridColumn="1">
            <Box display="inline-flex" flexDirection="column" gap="spacing.2">
              <Text size="small" color="surface.text.gray.muted">
                Beneficiary Name
              </Text>
              <Text>RAZORPAY TECHNOLOGIES PRIVATE LIMITED</Text>
            </Box>
          </Box>

          <Box gridRow="2" gridColumn="1">
            <Box display="inline-flex" flexDirection="column" gap="spacing.2">
              <Text size="small" color="surface.text.gray.muted">
                Account Number
              </Text>
              <Text>921020056336542</Text>
            </Box>
          </Box>

          <Box gridRow="2" gridColumn="2">
            <Box display="inline-flex" flexDirection="column" gap="spacing.2">
              <Text size="small" color="surface.text.gray.muted">
                Bank Name
              </Text>
              <Text>Axis Bank</Text>
            </Box>
          </Box>

          <Box gridRow="3" gridColumn="1">
            <Box display="inline-flex" flexDirection="column" gap="spacing.2">
              <Text size="small" color="surface.text.gray.muted">
                Branch
              </Text>
              <Text>Kormangala</Text>
            </Box>
          </Box>

          <Box gridRow="3" gridColumn="2">
            <Box display="inline-flex" flexDirection="column" gap="spacing.2">
              <Text size="small" color="surface.text.gray.muted">
                IFSC Code
              </Text>
              <Text>UTIB0001506</Text>
            </Box>
          </Box>

          <Box gridRow="4" gridColumn="1/4">
            <Box display="inline-flex" flexDirection="column" gap="spacing.2">
              <Text size="small" color="surface.text.gray.muted">
                Address
              </Text>
              <Text>
                No-21, 80 Feet Road, 4 Th Block, Koramangala, Bangalore, Karnataka, Pin 560 095
              </Text>
            </Box>
          </Box>
        </Box>
      </EscrowDetailsContainer>
    </Box>
  );
};

export default BankDetails;
