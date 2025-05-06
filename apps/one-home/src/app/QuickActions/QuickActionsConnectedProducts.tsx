import React, { useEffect } from 'react';
import { RazorpayIcon, Box } from '@razorpay/blade/components';
import useHorizontalScroll from '@apps/one-home/src/hooks/useHorizontalScroll';
import { useNavigate } from 'react-router-dom';
import ScrollableContainer from './components/ScrollableContainer';
import { messages } from './constants';
import useOneHomeAnalytics from '../../hooks/useOneHomeAnalytics';
import config from './config';
import QuickActionCard from './components/QuickActionCard';
import SectionHeader from '../../components/SectionHeader/SectionHeader';
import { ErrorBoundary } from '@libs/shared-ui';
import { DASHBOARD_PRIORITY_RANKS, DASHBOARD_TEAMS } from '@libs/shared-types';
import ErrorState from '../../components/ErrorBoundary/ErrorState';
import { SelectAction, QuickActionsConnectedProductsProps } from './types';

const QuickActionsConnectedProducts = ({
  title,
  data,
  error,
  isMobile,
}: QuickActionsConnectedProductsProps) => {
  const navigate = useNavigate();
  const { scrollContainerRef, isScrolledLeft, isScrolledRight } = useHorizontalScroll();
  const { trackOneHomeAnalytics } = useOneHomeAnalytics();

  useEffect(() => {
    const commonProperties = {
      title: messages.quickActionsSection.analytics.title,
      widgetId: messages.quickActionsSection.analytics.widgetId,
      subWidgetId: messages.quickActionsConnectedProducts.analytics.subWidgetId,
    };

    if (error) {
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

  const handleCardClick = ({
    itemName,
    itemRank,
    action,
  }: {
    itemName: string;
    itemRank: number;
    action: SelectAction;
  }) => {
    trackOneHomeAnalytics({
      objectName: 'Ucs Link',
      actionName: 'Clicked',
      properties: {
        title: messages.quickActionsSection.analytics.title,
        itemName,
        itemRank,
        widgetId: messages.quickActionsConnectedProducts.analytics.widgetId,
        subWidgetId: messages.quickActionsConnectedProducts.analytics.subWidgetId,
      },
    });
    switch (action.actionType) {
      case 'navigate':
      case 'growth_page':
      case 'access_denied_page': {
        if (action.value && typeof action.value === 'object' && 'navigateTo' in action.value) {
          const { navigateTo, type } = action.value;
          if (type === 'external_navigation') {
            window.open(navigateTo, '_blank');
          } else {
            navigate(navigateTo);
          }
        }
        break;
      }
      case 'modal': {
        if (action.value === 'partners_onboarding_modal') {
          navigate({
            pathname: '/dashboard',
            search: '?openModal=partners_onboarding_modal',
          });
        }

        break;
      }

      default: {
        console.warn('Unknown action type:', action.actionType);
      }
    }
  };

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
        <ScrollableContainer
          ref={scrollContainerRef}
          isScrolledLeft={isScrolledLeft}
          isScrolledRight={isScrolledRight}
          isMobile={isMobile}
        >
          {data.map((item, index) => {
            return (
              <QuickActionCard
                key={item.id}
                icon={config[item.alias] || RazorpayIcon}
                title={item.title}
                isMobile={isMobile}
                onClick={() => {
                  handleCardClick({
                    itemName: item.title,
                    itemRank: index + 1,
                    action: item.selectAction,
                  });
                }}
              />
            );
          })}
        </ScrollableContainer>
      </ErrorBoundary>
    </Box>
  );
};

export default QuickActionsConnectedProducts;
