import React from 'react';
import { Box, Spinner, LayoutIcon } from '@razorpay/blade/components';
import { EmptyState } from '../EmptyState';
import { TAB_ICONS, DOCUMENTATION_ROUTES } from '../constants';

export const renderDashboardIcon = (activeTab: string) => {
  const IconComponent = TAB_ICONS[activeTab] || LayoutIcon;
  return <IconComponent marginRight="spacing.3" size="xlarge" />;
};

export const getDisplayName = (tab: string): string => {
  return tab === 'MagicX' ? 'Magic' : tab;
};

export const getDocumentationUrl = (activeTab: string): string => {
  return DOCUMENTATION_ROUTES[activeTab] || DOCUMENTATION_ROUTES['Overview'];
};

export const trackDocumentationClick = (
  activeTab: string,
  trackAnalytics: (event: string, data: any) => void,
): string => {
  const documentationUrl = getDocumentationUrl(activeTab);
  trackAnalytics(`${activeTab} Documentation Clicked`, {
    documentation_link: documentationUrl,
  });
  return documentationUrl;
};

export const renderLoadingOrErrorState = (
  isLoading: boolean,
  isError: boolean,
  guestToken: string | undefined,
  error: unknown,
  activeTab: string,
  showEmptyState: boolean,
  retryHandler: () => void,
  insightsDashboard: string,
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
            text={`${insightsDashboard} - ${activeTab}`}
            retryHandler={retryHandler}
            analyticsProperties={{
              objectName: `Insights - ${insightsDashboard}`,
              actionName: 'error',
              screen: `${insightsDashboard} - ${activeTab}`,
              ...(error ? { error: `Error while fetching guest token : ${error}` } : {}),
              event_name: `insights.${insightsDashboard}.${activeTab}.error`,
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
