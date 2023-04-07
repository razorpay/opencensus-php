import { Text, Box } from '@razorpay/blade/components';
import Shimmer from 'common/components/Shimmer';
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
      backgroundColor="surface.background.level2.lowContrast"
      flexDirection={{ base: 'column', m: 'row' }}
      padding={{ base: 'spacing.7', m: ['spacing.8', 'spacing.0'] }}
      rowGap={{ base: 'spacing.7', m: 'spacing.6' }}
    >
      {placeHolderData.map((each, index) => (
        <InfoItem key={index} isBorder={index < placeHolderData.length - 1}>
          <Text size="medium" type="subtle">
            {each.name}
          </Text>
          <Shimmer height="24px" width="140px" variant="rounded" borderRadius="12px" />
        </InfoItem>
      ))}
    </Box>
  );
};

export default SettlementInfoShimmer;
