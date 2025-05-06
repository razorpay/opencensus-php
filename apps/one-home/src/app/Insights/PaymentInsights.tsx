import React, { useEffect, useState } from 'react';
import {
  ArrowUpRightIcon,
  Box,
  Button,
  CalendarIcon,
  ClockIcon,
  Skeleton,
} from '@razorpay/blade/components';
import { useBreakpoint, useTheme } from '@razorpay/blade/utils';
import { useNavigate } from 'react-router-dom';
import DateFilter from '../../components/DateFilter';
import SectionHeader from '../../components/SectionHeader/SectionHeader';
import useOneHomeAnalytics from '../../hooks/useOneHomeAnalytics';
import { dateFilterOptions, staticContent } from './constants';
import InsightCard from './InsightCard';
import {
  InsightResponse,
  PaymentInsightsWithErrorBoundaryProp,
  DateRangeOption,
  Component,
  InsightCardType,
} from './types';
import useInsights from './useInsights';
import { getDateRange, getTimeAgo, getComponentByAlias } from './utils';
import { ErrorBoundary } from '@libs/shared-ui';
import { DASHBOARD_PRIORITY_RANKS, DASHBOARD_TEAMS } from '@libs/shared-types';
import ErrorState from '../../components/ErrorBoundary/ErrorState';

const PaymentInsightsWithErrorBoundary = ({
  insightsData,
  selectedFilter,
  isError,
  isLoading,
  isMobile,
  paymentLocked,
  error,
}: PaymentInsightsWithErrorBoundaryProp) => {
  if (isError || insightsData?.error) {
    throw error;
  }

  const payments_collected_data = getComponentByAlias(
    (insightsData as InsightResponse)?.components,
    'home_payment_collected_insight',
  );

  const success_rate_data = getComponentByAlias(
    (insightsData as InsightResponse)?.components,
    'home_success_rate_insight',
  );

  const refunds_data = getComponentByAlias(
    (insightsData as InsightResponse)?.components,
    'home_refund_insight',
  );

  // const seasonal_data = getComponentByAlias(
  //   (insightsData as InsightResponse)?.components,
  //   'seasonal_data',
  // );

  const analyticsProps = {
    title: staticContent.paymentInsightsHeader,
    dateRange: selectedFilter,
    buttonName: staticContent.viewDetailsText,
    widgetId: staticContent.paymentInsightWidgetId,
  };

  /* If card data is not present, do not show the card.
  If isLoading is removed, the shimmer effect will be removed as well. */
  const renderInsightCard = (cardType: InsightCardType, componentData?: Component) =>
    (componentData || isLoading) && (
      <InsightCard
        cardType={cardType}
        isLoading={isLoading}
        isMobile={isMobile}
        componentData={componentData}
        source="payment_insights"
        paymentLocked={paymentLocked}
        analytics={{
          ...analyticsProps,
          subWidgetId: `one_home_${componentData?.alias}`,
          cardName: componentData?.title,
        }}
      />
    );

  return (
    <Box
      display="flex"
      gap="spacing.5"
      flexWrap={{ base: 'no-wrap', s: 'no-wrap', m: 'wrap', l: 'no-wrap' }}
      flexDirection={{ base: 'column', s: 'column', m: 'row', l: 'row' }}
      alignItems="stretch"
      borderRadius="large"
    >
      {renderInsightCard('payment', payments_collected_data)}
      {renderInsightCard('success_rate', success_rate_data)}
      {renderInsightCard('refund', refunds_data)}

      {/* Not for v1 */}
      {/* {(seasonal_data || isLoading) && (
          <SeasonalCard isLoading={isLoading} isMobile={isMobile} componentData={seasonal_data} />
        )} */}
    </Box>
  );
};

const renderSectionBadges = (
  isLoading: boolean,
  timeRange: string | null,
  lastUpdatedAt: string | null,
) => {
  if (isLoading) {
    return (
      <SectionHeader.Badges>
        <SectionHeader.Badge icon={CalendarIcon} text="••••" />
        <SectionHeader.Badge icon={ClockIcon} text="••••" />
      </SectionHeader.Badges>
    );
  }
  return (
    <SectionHeader.Badges>
      {timeRange && <SectionHeader.Badge icon={CalendarIcon} text={timeRange} />}
      {lastUpdatedAt && <SectionHeader.Badge icon={ClockIcon} text={lastUpdatedAt} />}
    </SectionHeader.Badges>
  );
};

const PaymentInsights = () => {
  const [selectedFilter, setSelectedFilter] = useState<DateRangeOption>(dateFilterOptions[1].key);
  const [isInitialLoading, setIsInitialLoading] = useState(true);
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const { trackOneHomeAnalytics } = useOneHomeAnalytics();
  const isMobile = matchedDeviceType === 'mobile';

  const { insightsData, isLoading, isError, error } = useInsights({
    key: 'one_home_payment_insights',
    aliasKey: 'home_payment_insight',
    input_data: selectedFilter,
  });

  useEffect(() => {
    if (!isLoading) {
      setIsInitialLoading(false);
      if (selectedFilter || isError) trackOnLoad();
    }
  }, [isLoading]);

  const trackOnLoad = () => {
    let analyticsProps: { [key: string]: unknown } = { title: staticContent.paymentInsightsHeader };
    if (isError) {
      analyticsProps = {
        ...analyticsProps,
        itemName: 'Oops! An error occured.',
      };
    } else {
      analyticsProps = {
        ...analyticsProps,
        dateRange: selectedFilter,
        widgetId: staticContent.paymentInsightWidgetId,
        nonInsightsCardPresent: 'No',
      };
    }
    trackOneHomeAnalytics({
      objectName: 'Ucs Widget',
      actionName: 'Loaded',
      properties: { ...analyticsProps },
    });
  };

  const navigate = useNavigate();

  const handleChangeFilter = (changedFilter: string) => {
    if (changedFilter === selectedFilter) return;
    setSelectedFilter(changedFilter as DateRangeOption);
  };

  const trackDetailedInsightsClick = () => {
    const analyticsProps = {
      title: staticContent.paymentInsightsHeader,
      dateRange: selectedFilter,
      widgetId: staticContent.paymentInsightWidgetId,
      buttonName: 'See Detailed Insights',
      cardName: 'Insights',
    };
    trackOneHomeAnalytics({
      objectName: 'Ucs Link',
      actionName: 'Clicked',
      properties: { ...analyticsProps },
    });
  };

  const insightSummaryData = insightsData?.data?.one_home_data?.insight?.insight_summary;
  const lastUpdatedAt = insightSummaryData
    ? getTimeAgo(Number(insightSummaryData?.data_summary?.last_updated))
    : '';
  const timeRange = getDateRange(selectedFilter);

  const payment_enabled = insightSummaryData?.payment_enabled;
  const payment_locked = insightSummaryData?.payment_locked;

  // Do not display section if: payment_enabled: false regardless of payment_locked: false/true
  if (payment_enabled === false) {
    return null;
  }

  return (
    <Box>
      {isInitialLoading ? (
        <Skeleton
          width="240px"
          height="32px"
          borderRadius="large"
          marginBottom="spacing.5"
          testID="onehome-payment-insight-skeleton"
        />
      ) : (
        <SectionHeader>
          <SectionHeader.Title>{staticContent.paymentInsightsHeader}</SectionHeader.Title>
          {!isError && !(insightsData as InsightResponse)?.error && (
            <SectionHeader.Content>
              {renderSectionBadges(isLoading, timeRange, lastUpdatedAt)}
              <SectionHeader.Actions>
                <DateFilter
                  options={dateFilterOptions}
                  selected={selectedFilter}
                  onChange={(dateKey) => handleChangeFilter(dateKey)}
                  isDisabled={false}
                />
                <Button
                  variant="secondary"
                  color="primary"
                  size="medium"
                  icon={ArrowUpRightIcon}
                  iconPosition="right"
                  display={{ base: 'none', s: 'none', m: 'block', l: 'block' }}
                  onClick={() => {
                    navigate(staticContent.dashboardUrl);
                    trackDetailedInsightsClick();
                  }} // TODO: via splitz change
                  accessibilityLabel={staticContent.insightsBtnLabel}
                >
                  {staticContent?.seeDetailedInsightsText}
                </Button>
              </SectionHeader.Actions>
            </SectionHeader.Content>
          )}
        </SectionHeader>
      )}
      <ErrorBoundary
        key={selectedFilter}
        rank={DASHBOARD_PRIORITY_RANKS.P0}
        team={DASHBOARD_TEAMS.R1_CONNECTED_EXPERIENCE}
        tags={{ module: 'One_Home_Payment_Insight' }}
        FallbackComponent={() => (
          <ErrorState title={staticContent.errorText} withBorder={true} borderRadius="large" />
        )}
      >
        <PaymentInsightsWithErrorBoundary
          insightsData={insightsData}
          isError={isError}
          isLoading={isLoading}
          isMobile={isMobile}
          selectedFilter={selectedFilter}
          paymentLocked={payment_locked}
          error={error}
        />
      </ErrorBoundary>
    </Box>
  );
};

export default PaymentInsights;
