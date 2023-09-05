import React from 'react';
import { Box, Card, CardBody, Text } from '@razorpay/blade/components';
import LoadFailedIcon from 'assets/transactions/load-failed.svg';
import { LoadFailedProps } from 'merchant/views/Transactions/v2/Analytics/types';

const LoadFailed = ({ title, subtitle, height }: LoadFailedProps): JSX.Element => {
  return (
    <Card padding="spacing.3" marginY="spacing.5" surfaceLevel={2} elevation="none" display="flex">
      <CardBody>
        <Box
          display="flex"
          padding="spacing.5"
          flexDirection="column"
          minWidth="280px"
          minHeight={`${height}px` as any}
          justifyContent="space-evenly"
          alignItems="center"
        >
          <img src={LoadFailedIcon} alt="data load failed" />
          <Box display="flex" justifyContent="center" flexDirection="column" alignItems="center">
            <Text textAlign="center">{title}</Text>
            <Text>{subtitle}</Text>
          </Box>
        </Box>
      </CardBody>
    </Card>
  );
};

export default LoadFailed;
