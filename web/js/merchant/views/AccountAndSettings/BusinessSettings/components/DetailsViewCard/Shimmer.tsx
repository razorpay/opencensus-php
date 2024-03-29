import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

import Shimmer from 'common/components/Shimmer';

import { StyledDetailListing, StyledDivider } from './styled';

const DetailsViewShimmer = ({ title }: { title: string }): JSX.Element => {
  return (
    <Box
      display="flex"
      flexDirection="column"
      backgroundColor="surface.background.gray.moderate"
      padding={{
        base: ['spacing.4', 'spacing.5', 'spacing.6'],
        m: ['spacing.6', 'spacing.7', 'spacing.7'],
      }}
      gap="spacing.5"
    >
      <Text size="large">{title}</Text>
      <StyledDivider />
      <Box display="flex" flexDirection="column" gap="spacing.7">
        {[
          { name: '75px', value: '120px' },
          { name: '60px', value: '200px' },
          { name: '90px', value: '180px' },
        ].map((item, index) => (
          <Box key={index} display="flex" flexDirection="column" gap="spacing.3">
            <Shimmer height="20px" width={item.name} variant="rounded" borderRadius="4px" />
            <StyledDetailListing>
              <Box
                display="flex"
                gap={{ base: 'spacing.3', m: 'spacing.2' }}
                justifyContent={{ base: 'space-between', m: 'initial' }}
                alignItems="center"
              >
                <Shimmer height="20px" width={item.value} variant="rounded" borderRadius="4px" />
              </Box>
            </StyledDetailListing>
          </Box>
        ))}
      </Box>
    </Box>
  );
};

export default DetailsViewShimmer;
