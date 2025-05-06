import React, { useEffect } from 'react';
import { Box, useTheme, Skeleton } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { DASHBOARD_PRIORITY_RANKS, DASHBOARD_TEAMS } from '@libs/shared-types';
import { ErrorBoundary } from '@libs/shared-ui';
import SectionHeader from '../../components/SectionHeader/SectionHeader';
import useQuickActions from './useQuickActions';
import { QuickActionConnectedProductItem, QuickActionItem, QuickActionsCategory } from './types';
import ErrorState from '../../components/ErrorBoundary/ErrorState';
import { messages } from './constants';
import QuickActionsList from './QuickActionsList';
import useOneHomeAnalytics from '../../hooks/useOneHomeAnalytics';
import { useTopNavigationData } from '@federated/apps/shell/connected-navigation/hooks';

import QuickActionsConnectedProducts from './QuickActionsConnectedProducts';

const QuickActionsComponent: React.FC<{
  title: string;
  data?: QuickActionItem;
  isMobile: boolean;
  error: unknown;
  type: QuickActionsCategory;
}> = ({ title, data, isMobile, error, type }) => {
  const { trackOneHomeAnalytics } = useOneHomeAnalytics();

  useEffect(() => {
    const commonProperties = {
      title: messages.quickActionsSection.analytics.title,
      widgetId: messages.quickActionsSection.analytics.widgetId,
      subWidgetId: messages[type].analytics.subWidgetId,
    };

    if (error || data?.error) {
      trackOneHomeAnalytics({
        objectName: 'Ucs widget',
        actionName: 'Loaded',
        properties: {
          ...commonProperties,
          status: 'error',
          itemName: messages.quickActionsSection.analytics.title,
        },
      });
    } else if (data) {
      trackOneHomeAnalytics({
        objectName: 'Ucs widget',
        actionName: 'Loaded',
        properties: {
          ...commonProperties,
          status: 'success',
        },
      });
    }
  }, []);

  return (
    <Box display="flex" flexDirection="column">
      <SectionHeader>
        <SectionHeader.Title>{title}</SectionHeader.Title>
      </SectionHeader>
      <ErrorBoundary
        rank={DASHBOARD_PRIORITY_RANKS.P0}
        team={DASHBOARD_TEAMS.R1_CONNECTED_EXPERIENCE}
        FallbackComponent={() => (
          <ErrorState
            title={messages.quickActionsSection.errorMessage}
            borderRadius="large"
            withBorder={true}
          />
        )}
      >
        <QuickActionsList
          error={error || data?.error}
          data={data}
          isMobile={isMobile}
          type={type}
        />
      </ErrorBoundary>
    </Box>
  );
};

const QuickActions = () => {
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });

  const isMobile = matchedDeviceType === 'mobile';

  const {
    products,
    isLoading: isConnectedProductsLoading,
    error: topNavigationLoadError,
  } = useTopNavigationData();

  const connectedProductsPayload: QuickActionConnectedProductItem[] = products
    ? products.filter(
        (product: QuickActionConnectedProductItem) => product.alias != 'home_top_navigation_item',
      )
    : undefined;

  const {
    isFetching: isPaymentsQuickActionsFetching,
    data: paymentsQuickActionsData,
    error: paymentsQuickActionsError,
  } = useQuickActions('payments_quick_action_item');

  const {
    isFetching: isBankingQuickActionsFetching,
    data: bankingQuickActionsData,
    error: bankingQuickActionsError,
  } = useQuickActions('banking_quick_action_item');

  return (
    <Box
      display="flex"
      flexDirection={{ base: 'column', m: 'column', l: 'row', xl: 'row' }}
      gap={{ base: '16px', m: '48px', l: '48px', xl: '48px' }}
      flexWrap={'wrap'}
      width={'100%'}
      overflowX={'hidden'}
    >
      <Box flex={1} width={'100%'}>
        {isPaymentsQuickActionsFetching ? (
          <Skeleton width={'100%'} height={'132px'} borderRadius="large" />
        ) : (
          <QuickActionsComponent
            title={messages.quickActionsPayments.title}
            data={paymentsQuickActionsData}
            isMobile={isMobile}
            error={paymentsQuickActionsError}
            type="quickActionsPayments"
          />
        )}
      </Box>
      <Box flex={1} width={'100%'}>
        {isBankingQuickActionsFetching ? (
          <Skeleton width={'100%'} height={'132px'} borderRadius="large" />
        ) : (
          <QuickActionsComponent
            title={messages.quickActionsBanking.title}
            data={bankingQuickActionsData}
            isMobile={isMobile}
            error={bankingQuickActionsError}
            type="quickActionsBanking"
          />
        )}
      </Box>

      {isMobile ? (
        <Box flex={1} width={'100%'}>
          {isConnectedProductsLoading ? (
            <Skeleton width={'100%'} height={'132px'} borderRadius="large" />
          ) : (
            <QuickActionsConnectedProducts
              title={messages.quickActionsConnectedProducts.title}
              data={connectedProductsPayload}
              error={topNavigationLoadError as Error}
              isMobile={isMobile}
            />
          )}
        </Box>
      ) : null}
    </Box>
  );
};

export default QuickActions;
