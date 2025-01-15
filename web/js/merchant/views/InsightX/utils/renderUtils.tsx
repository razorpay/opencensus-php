import React from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import { EmptyState } from '../EmptyState';

export const renderLoadingOrErrorState = (
  isLoading: boolean,
  isError: boolean,
  guestToken: string | null,
  error: unknown,
  showEmptyState: boolean,
  refetch: () => void,
  retryHandler: () => void,
) => {
  if (showEmptyState) {
    return (
      <EmptyState
        text="InsightX"
        retryHandler={retryHandler}
        analyticsProperties={{
          objectName: 'InsightX',
          actionName: 'error',
          screen: 'insightx/root',
          ...(error ? { error: `Error while fetching guest token : ${error}` } : {}),
        }}
      />
    );
  }
  if (isError) {
    // this throw is handled by the InsightXErrorBoundary
    throw new Error(`Error Occured: ${error}`);
  }

  if (isLoading || !guestToken) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" height="300px">
        <Spinner size="xlarge" accessibilityLabel="fetching token" />
      </Box>
    );
  }

  return null;
};
