import React from 'react';
import { Card, CardBody } from '@razorpay/blade/components';

import { OverviewWrapper } from 'apps/self-serve/src/App/Transactions/v2/Analytics/styled';

const OverviewContainer = ({ children }: JSX.ElementChildrenAttribute): JSX.Element => {
  return (
    <OverviewWrapper>
      <Card elevation="lowRaised" padding="spacing.5" surfaceLevel={2}>
        <CardBody>{children}</CardBody>
      </Card>
    </OverviewWrapper>
  );
};

export default OverviewContainer;
