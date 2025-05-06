import React from 'react';
import { RazorpayIcon } from '@razorpay/blade/components';
import useHorizontalScroll from '@apps/one-home/src/hooks/useHorizontalScroll';
import { useNavigate } from 'react-router-dom';
import ScrollableContainer from './components/ScrollableContainer';
import { QuickActionItem, QuickAction, QuickActionsCategory } from './types';
import { messages } from './constants';
import useOneHomeAnalytics from '../../hooks/useOneHomeAnalytics';
import config from './config';
import QuickActionCard from './components/QuickActionCard';

const QuickActionsList = ({
  isMobile,
  data,
  error,
  type,
}: {
  isMobile: boolean;
  data?: QuickActionItem;
  error: unknown;
  type: QuickActionsCategory;
}) => {
  const navigate = useNavigate();
  const { trackOneHomeAnalytics } = useOneHomeAnalytics();
  const { scrollContainerRef, isScrolledLeft, isScrolledRight } = useHorizontalScroll();
  if (error || !data) {
    throw new Error(messages.quickActionsSection.errorMessage);
  }

  const handleCardClick = ({
    itemName,
    itemRank,
    action,
  }: {
    itemName: string;
    itemRank: number;
    action: QuickAction;
  }) => {
    if (action.action === 'navigate') {
      trackOneHomeAnalytics({
        objectName: 'Ucs Link',
        actionName: 'Clicked',
        properties: {
          title: messages.quickActionsSection.analytics.title,
          itemName,
          itemRank,
          widgetId: messages[type].analytics.widgetId,
          subWidgetId: messages[type].analytics.subWidgetId,
        },
      });
      if ('path' in action.action_params) {
        navigate(action.action_params.path);
      } else if ('url' in action.action_params) {
        window.open(action.action_params.url, '_blank');
      }
    }
  };

  return (
    <ScrollableContainer
      ref={scrollContainerRef}
      isScrolledLeft={isScrolledLeft}
      isScrolledRight={isScrolledRight}
      isMobile={isMobile}
    >
      {data.components.map((item, index) => (
        <QuickActionCard
          key={item.id}
          icon={config[item.alias] || RazorpayIcon}
          title={item.title}
          isMobile={isMobile}
          onClick={() => {
            handleCardClick({ itemName: item.title, itemRank: index + 1, action: item.actions[0] });
          }}
        />
      ))}
    </ScrollableContainer>
  );
};

export default QuickActionsList;
