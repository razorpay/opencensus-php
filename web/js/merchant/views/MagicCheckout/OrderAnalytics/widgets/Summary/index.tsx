import React, { useMemo } from 'react';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';

import { useOrderAnalyticsContext } from 'merchant/views/MagicCheckout/OrderAnalytics/OrderAnalyticsContext';
import Shimmer from 'merchant/views/MagicCheckout/OrderAnalytics/widgets/Shimmer';
import { VerticalPartition } from 'merchant/views/MagicCheckout/RTOAnalytics/common/CumulativeOrders';
import { SUMMARY_WIDGETS } from './constants';
import {
  SummaryWidgetWrapper,
  SummaryWidget as StyledSummaryWidget,
  SummaryWidgetItem,
} from './styles';
import { buildSummaryWidgetData } from './utils';

const WidgetWithShimmer = ({ index, isLoading, data }) => (
  <SummaryWidgetItem>
    {isLoading ? (
      <Shimmer width="100%" height={57} />
    ) : (
      <ErrorBoundary team={Teams?.MAGIC_CHECKOUT} rank={Ranks.P0} resetOnProps>
        <StyledSummaryWidget color={data?.[index]?.color}>
          <span className="accent" />
          <span className="title">{data?.[index]?.title}</span>
          <span className="value">{data?.[index]?.value}</span>
        </StyledSummaryWidget>
        {index < 2 ? <VerticalPartition /> : null}
      </ErrorBoundary>
    )}
  </SummaryWidgetItem>
);

const SummaryWidget = (): React.ReactNode => {
  const { isFetching, analyticsData } = useOrderAnalyticsContext();
  const analyticsSummary = useMemo(() => {
    if (!isFetching && analyticsData?.metrics) {
      return buildSummaryWidgetData(analyticsData.metrics, SUMMARY_WIDGETS);
    }
    return [];
  }, [isFetching, analyticsData]);

  return (
    <SummaryWidgetWrapper>
      {Array.from(Array(3).keys()).map((idx) => (
        <WidgetWithShimmer
          data={analyticsSummary}
          isLoading={isFetching}
          index={idx}
          key={`analytics-${idx}`}
        />
      ))}
    </SummaryWidgetWrapper>
  );
};

export default SummaryWidget;
