import React from 'react';
import { Card, CardBody, Box } from '@razorpay/blade/components';

import ManageTeam from 'merchant/views/Account/ManageTeam';
const PartnerManageTeam = (): JSX.Element => {
  return (
    <Card padding="spacing.3" elevation="none">
      <CardBody>
        <Box>
          <ManageTeam isRenderedFromPartnerRoute />
        </Box>
      </CardBody>
    </Card>
  );
};
export default PartnerManageTeam;
