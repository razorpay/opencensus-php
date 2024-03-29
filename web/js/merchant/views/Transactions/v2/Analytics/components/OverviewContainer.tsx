import React from 'react';
import { Card, CardBody } from '@razorpay/blade/components';

import { OverviewWrapper } from 'merchant/views/Transactions/v2/Analytics/styled';

const OverviewContainer = ({ children }: JSX.ElementChildrenAttribute): JSX.Element => {
  return (
    <OverviewWrapper>
      <Card
        elevation="lowRaised"
        padding="spacing.5"
        backgroundColor="surface.background.gray.moderate"
      >
        <CardBody>{children}</CardBody>
      </Card>
    </OverviewWrapper>
  );
};

export default OverviewContainer;
