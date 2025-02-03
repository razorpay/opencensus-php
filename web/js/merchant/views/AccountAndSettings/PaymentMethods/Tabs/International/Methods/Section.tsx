import React, { useEffect } from 'react';
import { Card, CardBody, Box, Heading, Text } from '@razorpay/blade/components';

import ErrorBoundary, { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import IntoViewUsingQueryParams from 'common/ui/IntoViewUsingQueryParams';
import { track } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/analytics/track';

import { MethodSectionProps } from './types';

const MethodSection = ({ header, description, id, children, trailing }: MethodSectionProps) => {
  useEffect(() => {
    if (header) {
      track('render', { objectName: header });
    }
  }, [header]);

  return (
    <ErrorBoundary rank={Ranks.P1} team={Teams.CROSS_BORDER} resetOnProps>
      <Box id={id} testID="leaf-list-item">
        <Card elevation="none" testID={id}>
          <CardBody>
            {header || description ? (
              <Box display="flex" marginBottom="spacing.6">
                <Box>
                  {header && <Heading size="small">{header}</Heading>}
                  {description && <Text size="small">{description}</Text>}
                </Box>
                {trailing && <Box marginLeft="auto">{trailing}</Box>}
              </Box>
            ) : null}
            <IntoViewUsingQueryParams queryKey="instrument" queryValue={id} key={id}>
              <Box>{children}</Box>
            </IntoViewUsingQueryParams>
          </CardBody>
        </Card>
      </Box>
    </ErrorBoundary>
  );
};

export default MethodSection;
