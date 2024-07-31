import React from 'react';
import { Box, Heading, Text } from '@razorpay/blade/components';
import { StatusCounts } from 'apps/pos/src/app/types/SalesAssistedOnboarding';
import { StatusTiles } from 'apps/pos/src/app/constants/SalesAssistedOnboarding';

interface StatusCountProps {
  statusCounts: StatusCounts;
}

const SalesDashboardStatusCounts = ({ statusCounts }: StatusCountProps): JSX.Element | null => {
  return (
    <Box
      display={{ base: 'grid' }}
      gridTemplateColumns={{
        base: '1fr 1fr',
        m: '1fr 1fr 1fr',
        xl: `repeat(${StatusTiles.length}, minmax(0, 1fr));`,
      }}
      gridAutoColumns="auto"
      gap="spacing.4"
      testID="sales-dashboard-status-counts"
    >
      {StatusTiles.map(({ name, key }) => (
        <Box
          key={key}
          backgroundColor="surface.background.gray.intense"
          paddingY="spacing.5"
          paddingX="spacing.6"
          testID={`${key}-sales-count-field`}
        >
          <Heading
            size="large"
            weight="semibold"
            marginBottom="spacing.2"
            color="surface.text.gray.subtle"
            textAlign="center"
          >
            {statusCounts?.[key] ?? 0}
          </Heading>
          <Text size="small" color="surface.text.gray.muted" textAlign="center">
            {name}
          </Text>
        </Box>
      ))}
    </Box>
  );
};

export default SalesDashboardStatusCounts;
