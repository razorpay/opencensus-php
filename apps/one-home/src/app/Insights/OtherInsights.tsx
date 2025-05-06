import React, { useEffect, useState } from 'react';
import { Box, CalendarIcon, ClockIcon, Skeleton } from '@razorpay/blade/components';
import { useBreakpoint, useTheme } from '@razorpay/blade/utils';
import DateFilter from '../../components/DateFilter';
import withErrorBoundary from '../../components/ErrorBoundary/withErrorBoundary';
import SectionHeader from '../../components/SectionHeader/SectionHeader';
import useOneHomeAnalytics from '../../hooks/useOneHomeAnalytics';
import { dateFilterOptions, staticContent, nonInsightCardsStaticData } from './constants';
import InsightCard from './InsightCard';
import NonInsightCard from './NonInsightCard';
import { InsightResponse, OtherInsightsWithErrorBoundaryProp, DateRangeOption } from './types';
import useInsights from './useInsights';
import {
  getDateRange,
  getTimeAgo,
  generateNonInsightComponentData,
  getComponentByAlias,
} from './utils';
import { ErrorBoundary } from '@libs/shared-ui';
import { DASHBOARD_PRIORITY_RANKS, DASHBOARD_TEAMS } from '@libs/shared-types';
import ErrorState from '../../components/ErrorBoundary/ErrorState';

const OtherInsightWithErrorBoundary = ({
  insightsData,
  isError,
  isLoading,
  isMobile,
  selectedFilter,
  error,
}: OtherInsightsWithErrorBoundaryProp) => {
  if (error || insightsData?.error) {
    throw error;
  }

  const payout_insights = getComponentByAlias(
    (insightsData as InsightResponse)?.components,
    'home_payout_other_insight',
  );
  const payroll_insights = getComponentByAlias(
    (insightsData as InsightResponse)?.components,
    'home_payroll_other_insight',
  );
  const customers_insights = getComponentByAlias(
    (insightsData as InsightResponse)?.components,
    'home_customer_other_insight',
  );

  const analyticsProps = {
    title: staticContent.otherInsightsHeader,
    dateRange: selectedFilter,
    widgetId: staticContent.otherInsightWidgetId,
  };

  const payout_insights_enabled =
    payout_insights?.data?.one_home_data?.other_insight?.payout?.business_banking_enabled;
  const payout_insights_locked =
    payout_insights?.data?.one_home_data?.other_insight?.payout?.locked;

  return (
    <Box
      display="flex"
      gap="spacing.5"
      flexWrap={{ base: 'no-wrap', s: 'no-wrap', m: 'wrap', l: 'no-wrap' }}
      flexDirection={{ base: 'column', s: 'column', m: 'row', l: 'row' }}
      alignItems="stretch"
      borderRadius="large"
    >
      {(payout_insights || isLoading) && (
        <>
          {payout_insights_enabled ? (
            <InsightCard
              cardType="payout"
              isLoading={isLoading}
              isMobile={isMobile}
              componentData={payout_insights}
              source="other_insights"
              paymentLocked={payout_insights_locked}
              analytics={{
                ...analyticsProps,
                subWidgetId: `one_home_${payout_insights?.alias}`,
                buttonName: staticContent.viewDetailsText,
                cardName: payout_insights?.title,
              }}
            />
          ) : (
            <NonInsightCard
              cardType="payout"
              isLoading={isLoading}
              isMobile={isMobile}
              componentData={generateNonInsightComponentData(
                nonInsightCardsStaticData.payout,
                'payout_other_insight',
              )}
              analytics={{
                ...analyticsProps,
                subWidgetId: `one_home_${customers_insights?.alias}`,
                cardName: customers_insights?.title,
              }}
            />
          )}
        </>
      )}

      {(payroll_insights || isLoading) && (
        <NonInsightCard
          cardType="payroll"
          isLoading={isLoading}
          isMobile={isMobile}
          componentData={payroll_insights}
          analytics={{
            ...analyticsProps,
            subWidgetId: `one_home_${payroll_insights?.alias}`,
            cardName: payroll_insights?.title,
          }}
        />
      )}

      {(customers_insights || isLoading) && (
        <NonInsightCard
          cardType="customers"
          isLoading={isLoading}
          isMobile={isMobile}
          componentData={customers_insights}
          analytics={{
            ...analyticsProps,
            subWidgetId: `one_home_${customers_insights?.alias}`,
            cardName: customers_insights?.title,
          }}
        />
      )}
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

const OtherInsights = () => {
  const [selectedFilter, setSelectedFilter] = useState<DateRangeOption>(dateFilterOptions[1].key);
  const [isInitialLoading, setIsInitialLoading] = useState(true);
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const { trackOneHomeAnalytics } = useOneHomeAnalytics();
  const isMobile = matchedDeviceType === 'mobile';

  const { insightsData, isLoading, isError, error } = useInsights({
    key: 'one_home_other_insights',
    aliasKey: 'home_other_insight',
    input_data: selectedFilter,
  });

  useEffect(() => {
    if (!isLoading) {
      setIsInitialLoading(false);
      if (selectedFilter || isError) trackOnLoad();
    }
  }, [isLoading]);

  const trackOnLoad = () => {
    let analyticsProps: { [key: string]: unknown } = { title: staticContent.otherInsightsHeader };
    if (isError) {
      analyticsProps = {
        ...analyticsProps,
        itemName: 'Oops! An error occured.',
      };
    } else {
      analyticsProps = {
        ...analyticsProps,
        dateRange: selectedFilter,
        widgetId: staticContent.otherInsightWidgetId,
        growthCardPresent: 'Yes',
      };
    }
    trackOneHomeAnalytics({
      objectName: 'Ucs Widget',
      actionName: 'Loaded',
      properties: { ...analyticsProps },
    });
  };

  const handleChangeFilter = (changedFilter: string) => {
    if (changedFilter === selectedFilter) return;
    setSelectedFilter(changedFilter as DateRangeOption);
  };

  const otherInsightSummaryData = insightsData?.data?.one_home_data?.other_insight?.insight_summary;
  const lastUpdatedAt = otherInsightSummaryData
    ? getTimeAgo(Number(otherInsightSummaryData?.data_summary?.last_updated))
    : '';
  const timeRange = getDateRange(selectedFilter);

  return (
    <Box>
      {isInitialLoading ? (
        <Skeleton
          width="240px"
          height="32px"
          borderRadius="large"
          marginBottom="spacing.5"
          testID="onehome-other-insight-skeleton"
        />
      ) : (
        <SectionHeader>
          <SectionHeader.Title>{staticContent.otherInsightsHeader}</SectionHeader.Title>
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
              </SectionHeader.Actions>
            </SectionHeader.Content>
          )}
        </SectionHeader>
      )}
      <ErrorBoundary
        key={selectedFilter}
        rank={DASHBOARD_PRIORITY_RANKS.P0}
        team={DASHBOARD_TEAMS.R1_CONNECTED_EXPERIENCE}
        tags={{ module: 'One_Home_Other_Insight' }}
        FallbackComponent={() => (
          <ErrorState title={staticContent.errorText} withBorder={true} borderRadius="large" />
        )}
      >
        <OtherInsightWithErrorBoundary
          insightsData={insightsData}
          isError={isError}
          isLoading={isLoading}
          isMobile={isMobile}
          selectedFilter={selectedFilter}
          error={error}
        />
      </ErrorBoundary>
    </Box>
  );
};

export default OtherInsights;
