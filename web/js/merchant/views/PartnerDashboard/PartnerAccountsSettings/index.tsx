import React from 'react';
import { Box, Card, CardBody } from '@razorpay/blade/components';

import SupportTickets from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/SupportTickets';

const PartnerAccountsSettings = (): JSX.Element => {
  return (
    <Card padding="spacing.3" elevation="none">
      <CardBody>
        <Box>
          <SupportTickets />
        </Box>
      </CardBody>
    </Card>
  );
};

export default PartnerAccountsSettings;
