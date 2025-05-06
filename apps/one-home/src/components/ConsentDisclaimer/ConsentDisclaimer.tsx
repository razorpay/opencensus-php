import { Box, Card, Text } from '@razorpay/blade/components';
import React from 'react';

const ConsentDisclaimer: React.FC = () => {
  return (
    <Box
      borderTopStyle="solid"
      borderTopWidth="thin"
      borderTopColor="surface.border.gray.normal"
      borderRadius="none"
    >
      <Card backgroundColor="surface.background.gray.subtle">
        <Box>
          <Text
            variant="body"
            size="medium"
            weight="regular"
            color="surface.text.gray.normal"
            textAlign="center"
          >
            The unified dashboard is designed solely for visual representation, providing a
            consolidated view of the services you use. Please note that your data for each specific
            service remains securely stored with the respective service provider entities within the
            Razorpay Group and is not centralized on the dashboard.
          </Text>
        </Box>
      </Card>
    </Box>
  );
};

export default ConsentDisclaimer;
