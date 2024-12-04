import React from 'react';
import { Card, CardBody } from '@razorpay/blade/components';

import ManageTeam from 'merchant/views/Account/ManageTeam';
const PartnerManageTeam = (): JSX.Element => {
  return (
    <Card padding="spacing.3" elevation="none">
      <CardBody>
        <main>
          <ManageTeam isRenderedFromPartnerRoute />
        </main>
      </CardBody>
    </Card>
  );
};
export default PartnerManageTeam;
