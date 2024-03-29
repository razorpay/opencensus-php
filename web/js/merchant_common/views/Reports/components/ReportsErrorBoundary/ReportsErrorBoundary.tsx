import React from 'react';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { AlertTriangleIcon, Text } from 'merchant_common/views/Reports/components';
import { CenteredEmptyContainer } from 'merchant_common/views/Reports/components/styled';

export const ReportsErrorBoundary = ({ children }) => {
  return (
    <ErrorBoundary
      FallbackComponent={() => (
        <CenteredEmptyContainer>
          <AlertTriangleIcon color="feedback.icon.information.intense" size="2xlarge" />

          <Text variant="body" weight="regular" size="medium" color="surface.text.gray.muted">
            Something went wrong, our team has been notified.
          </Text>
        </CenteredEmptyContainer>
      )}
      rank={Ranks.P0}
      team={Teams.MERCHANT_REPORTING}
      resetOnProps
    >
      {children}
    </ErrorBoundary>
  );
};
