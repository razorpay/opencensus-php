import React from 'react';
import { AlertTriangleIcon, Text } from '@razorpay/blade/components';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { CenteredEmptyContainer } from 'merchant_common/views/Reports/components/styled';

export const InsightsErrorBoundary: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  return (
    <ErrorBoundary
      FallbackComponent={() => (
        <CenteredEmptyContainer>
          <AlertTriangleIcon color="feedback.icon.information.intense" size="2xlarge" />
          <Text variant="body" weight="regular" size="medium" color="surface.text.gray.muted">
            Something went wrong. Our team has been notified.
          </Text>
        </CenteredEmptyContainer>
      )}
      rank={Ranks.P0}
      team={Teams.DATAHUB}
      resetOnProps
    >
      {children}
    </ErrorBoundary>
  );
};
