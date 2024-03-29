import React from 'react';
import { Box, Card, CardBody } from '@razorpay/blade/components';
import { useMobile } from 'common/hooks/useMobile';
import Shimmer from 'common/components/Shimmer';

const InitiateSetup = (): JSX.Element => {
  const isMobile = useMobile();

  return (
    <Card
      padding={isMobile ? 'spacing.5' : 'spacing.7'}
      backgroundColor={
        isMobile ? 'surface.background.gray.intense' : 'surface.background.gray.moderate'
      }
      elevation="none"
      testID="loading-shimmer"
    >
      <CardBody>
        <Box display="flex" flexDirection="column" gap={{ base: 'spacing.6', m: 'spacing.5' }}>
          <Box
            display="flex"
            justifyContent="space-between"
            flexDirection={{ base: 'column', m: 'row' }}
            gap={{ base: '10px', m: 'spacing.0' }}
          >
            <Box display="flex" flexDirection="column" gap={{ base: '10px', m: 'spacing.3' }}>
              <Shimmer height="18px" width="240px" variant="rounded" borderRadius="12px" />
              <Shimmer height="14px" width="140px" variant="rounded" borderRadius="12px" />
            </Box>
            {isMobile ? (
              <Shimmer height="32px" variant="rounded" borderRadius="4px" />
            ) : (
              <Shimmer height="36px" width="95px" variant="rounded" borderRadius="4px" />
            )}
          </Box>
        </Box>
      </CardBody>
    </Card>
  );
};

export default InitiateSetup;
