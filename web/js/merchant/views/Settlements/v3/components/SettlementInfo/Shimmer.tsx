import { Box, Card, CardBody, Text } from '@razorpay/blade/components';
import Shimmer from 'common/components/Shimmer';
import { SectionHeader } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';
import React from 'react';
import { InfoItem } from './styled';

const placeHolderData = [
  { name: 'Net settlement' },
  { name: 'Settlement ID' },
  { name: 'UTR number' },
  { name: 'Created on' },
  { name: 'Status' },
];

const SettlementInfoShimmer = (): JSX.Element => {
  return (
    <Box
      display="flex"
      flexWrap="wrap"
      backgroundColor="surface.background.gray.intense"
      flexDirection={{ base: 'column', m: 'row' }}
      padding={{ base: 'spacing.7', m: ['spacing.8', 'spacing.0'] }}
      rowGap={{ base: 'spacing.7', m: 'spacing.6' }}
    >
      {placeHolderData.map((each, index) => (
        <InfoItem key={index} isBorder={index < placeHolderData.length - 1}>
          <Text size="medium" color="surface.text.gray.subtle">
            {each.name}
          </Text>
          <Shimmer height="24px" width="140px" variant="rounded" borderRadius="12px" />
        </InfoItem>
      ))}
    </Box>
  );
};

export const SettlementInfoRevampShimmer = (): JSX.Element => {
  return (
    <Box testID="settlement-info-details-section-loading">
      <SectionHeader enableBorderBottomRadius={false}>
        <Text weight="semibold" size="large" color="surface.text.gray.normal">
          Details
        </Text>
      </SectionHeader>
      <Card padding="spacing.5" elevation="none">
        <CardBody>
          <Box display="flex" flexWrap="wrap" gap="spacing.3" padding="spacing.3">
            <Shimmer height="50px" width="100%" />
          </Box>
        </CardBody>
      </Card>
    </Box>
  );
};

export default SettlementInfoShimmer;
