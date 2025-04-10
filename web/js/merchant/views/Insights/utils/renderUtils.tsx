import React from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import { EmptyState } from '../EmptyState';

export const renderLoadingOrErrorState = (
  isLoading: boolean,
  isError: boolean,
  guestToken: string | undefined,
  error: unknown,
  activeTab: string,
  showEmptyState: boolean,
  retryHandler: () => void,
  Insights_Dashboard: string,
  baseAnalyticsProps: Record<string, unknown>,
) => {
  if (isLoading || !guestToken) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" height="300px">
        <Spinner size="xlarge" accessibilityLabel="fetching token" />
      </Box>
    );
  }
  if (isError) {
    throw new Error(`Error Occured: ${error}`);
  }
  if (showEmptyState) {
    return (
      <>
        <Box display="flex" alignContent="center" justifyContent="center">
          <EmptyState
            text={`${Insights_Dashboard} - ${activeTab}`}
            retryHandler={retryHandler}
            Insights_Dashboard={Insights_Dashboard}
            analyticsProperties={{
              objectName: `Insights - ${Insights_Dashboard}`,
              actionName: 'error',
              screen: `${Insights_Dashboard} - ${activeTab}`,
              ...(error ? { error: `Error while fetching guest token : ${error}` } : {}),
              event_name: `insights.${Insights_Dashboard}.${activeTab}.error`,
              ...baseAnalyticsProps,
              button_text: 'Try Again',
            }}
          />
        </Box>
      </>
    );
  }
  return null;
};
