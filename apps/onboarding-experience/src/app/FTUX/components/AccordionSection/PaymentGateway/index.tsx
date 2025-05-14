import React from 'react';
import { Box, Divider } from '@razorpay/blade/components';
import InviteTeamMember from './InviteTeamMember';
import IntegrationGuide from './IntegrationGuide';
import GenerateAPIKeys from './GenerateAPIKeys';

const PaymentGateway = () => {
  return (
    <Box display="flex" flexDirection="column" gap="spacing.6">
      <InviteTeamMember />
      <IntegrationGuide />
      <Divider />
      <GenerateAPIKeys />
    </Box>
  );
};

export default PaymentGateway;
