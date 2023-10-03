import React from 'react';
import { Box, Text, Link } from '@razorpay/blade/components';

const ApplicationDetails = ({
  name,
  id,
  handleChange,
}: {
  name: string;
  id: string;
  handleChange: () => void;
}) => (
  <Box
    display="flex"
    gap="spacing.3"
    alignItems="center"
    padding={['spacing.3', 'spacing.6']}
    borderColor="surface.border.normal.lowContrast"
    marginTop="spacing.6"
  >
    <Box display="flex" gap="spacing.4" alignItems="center" flex="1">
      <Box>
        <Text color="surface.text.subdued.lowContrast" type="subdued">
          {name}
        </Text>
        <Text type="subdued">App Id : {id}</Text>
      </Box>
    </Box>
    <Link size="large" onClick={handleChange}>
      Change
    </Link>
  </Box>
);

export default ApplicationDetails;
