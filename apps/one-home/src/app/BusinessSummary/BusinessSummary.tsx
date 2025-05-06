import React, { useEffect, useRef, useState } from 'react';
import {
  Box,
  useTheme,
  Skeleton,
  BoxRefType,
  CalendarIcon,
  ClockIcon,
} from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import DateFilter from '../../components/DateFilter';
import EarningsCard from './EarningsCard/EarningsCard';
import BalanceCard from './BalanceCard/BalanceCard';
import SpendsCard from './SpendsCard/SpendsCard';
import Connector from './Connector';
import useBusinessSummary from './useBusinessSummary';
import SectionHeader from '../../components/SectionHeader/SectionHeader';
import { getTimeAgo, getDateRange, DateRangeOption } from './utils';
import {
  BusinessSummaryContentProps,
  PaymentsComponent,
  BalanceComponent,
  SpendsComponent,
} from './types';
import { ErrorBoundary } from '@libs/shared-ui';
import { DASHBOARD_PRIORITY_RANKS, DASHBOARD_TEAMS } from '@libs/shared-types';
import ErrorState from '../../components/ErrorBoundary/ErrorState';
import { messages } from './constants';
import { dateFilterOptions, dateFilterOptionsMap } from './config';
import useOneHomeAnalytics from '../../hooks/useOneHomeAnalytics';

const extractComponent = <T extends PaymentsComponent | BalanceComponent | SpendsComponent>(
  components: (PaymentsComponent | BalanceComponent | SpendsComponent)[],
  alias: string,
): T | undefined => {
  return components.find((component) => component.alias === alias) as T | undefined;
};

const BusinessSummaryContent: React.FC<BusinessSummaryContentProps> = ({
  isLoading,
  data,
  error,
  dateFilter,
}) => {
  const { theme } = useTheme();
  const containerRef = useRef<BoxRefType>(null);

  if (error) {
    throw error;
  }

  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedDeviceType === 'mobile';

  const earningsCardRef = useRef<BoxRefType>(null);
  const balanceCardRef = useRef<BoxRefType>(null);
  const spendsCardRef = useRef<BoxRefType>(null);

  return (
    <Box
      display={'flex'}
      flexDirection={{ base: 'column', m: 'row', l: 'row', xl: 'row' }}
      justifyContent={'space-between'}
      alignItems={'center'}
      position={'relative'}
      width="100%"
      gap={{ base: '72px', m: 'spacing.8', l: 'auto', xl: 'auto' }}
      ref={containerRef}
    >
      <ErrorBoundary
        rank={DASHBOARD_PRIORITY_RANKS.P0}
        team={DASHBOARD_TEAMS.R1_CONNECTED_EXPERIENCE}
        tags={{ module: 'One_Home_Business_Summary_Earnings' }}
        FallbackComponent={() => (
          <ErrorState
            title={messages.earningsCard.errorMessage}
            withBorder={true}
            borderRadius="large"
          />
        )}
      >
        <EarningsCard
          ref={earningsCardRef}
          isLoading={isLoading}
          data={data.earningsData}
          dateFilter={dateFilter}
        />
      </ErrorBoundary>

      <ErrorBoundary
        rank={DASHBOARD_PRIORITY_RANKS.P0}
        team={DASHBOARD_TEAMS.R1_CONNECTED_EXPERIENCE}
        tags={{ module: 'One_Home_Business_Summary_Balance' }}
        FallbackComponent={() => (
          <ErrorState
            title={messages.balanceCard.errorMessage}
            withBorder={true}
            borderRadius="large"
          />
        )}
      >
        <BalanceCard
          ref={balanceCardRef}
          isLoading={isLoading}
          isMobile={isMobile}
          data={data.balanceData}
          dateFilter={dateFilter}
        />
      </ErrorBoundary>

      <ErrorBoundary
        rank={DASHBOARD_PRIORITY_RANKS.P0}
        team={DASHBOARD_TEAMS.R1_CONNECTED_EXPERIENCE}
        tags={{ module: 'One_Home_Business_Summary_Spends' }}
        FallbackComponent={() => (
          <ErrorState
            title={messages.spendsCard.errorMessage}
            withBorder={true}
            borderRadius="large"
          />
        )}
      >
        <SpendsCard
          ref={spendsCardRef}
          isLoading={isLoading}
          data={data.spendsData}
          dateFilter={dateFilter}
        />
      </ErrorBoundary>

      {isLoading ? null : (
        <>
          <Connector
            key={`${isMobile}-${
              earningsCardRef?.current && balanceCardRef.current
                ? `earnings_to_balance_ready`
                : '`earnings_to_balance_not_ready`'
            }`}
            parentRef={containerRef}
            startRef={earningsCardRef}
            endRef={balanceCardRef}
            direction={isMobile ? 'vertical' : 'horizontal'}
            type="highToLow"
          />
          <Connector
            key={`${isMobile}-${
              balanceCardRef?.current && spendsCardRef.current
                ? `balance_to_spends_ready`
                : 'balance_to_spends_not_ready'
            }`}
            parentRef={containerRef}
            startRef={balanceCardRef}
            endRef={spendsCardRef}
            direction={isMobile ? 'vertical' : 'horizontal'}
            type="lowToHigh"
          />
        </>
      )}
    </Box>
  );
};

const renderSectionBadges = (
  isFetching: boolean,
  timeRange: string | null,
  lastUpdatedAt: string | null,
) => {
  if (isFetching) {
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

const BussinessInsights = () => {
  const [selectedfilter, setSelectedFilter] = useState<DateRangeOption>(dateFilterOptions[1].key);
  const [isInitialLoading, setIsInitialLoading] = useState(true);
  const { trackOneHomeAnalytics } = useOneHomeAnalytics();

  const { data, isError, error, isLoading, isFetching } = useBusinessSummary(selectedfilter);

  useEffect(() => {
    if (!isLoading) {
      setIsInitialLoading(false);
      if (error) {
        trackOneHomeAnalytics({
          objectName: 'Ucs widget',
          actionName: 'Loaded',
          properties: {
            title: messages.businessSummarySection.analytics.title,
            widgetId: messages.businessSummarySection.analytics.widgetId,
            itemName: messages.businessSummarySection.errorMessage,
          },
        });
      }
    }
  }, [isLoading]);

  const handleChangeFilter = (changedFilter: string) => {
    if (changedFilter === selectedfilter) return;
    setSelectedFilter(changedFilter as DateRangeOption);
  };

  const earningsData = extractComponent<PaymentsComponent>(
    data?.components || [],
    'home_summary_earnings_item',
  );
  const balanceData = extractComponent<BalanceComponent>(
    data?.components || [],
    'home_summary_balance_item',
  );
  const spendsData = extractComponent<SpendsComponent>(
    data?.components || [],
    'home_summary_spends_item',
  );

  const lastUpdatedAt =
    data && 'data' in data
      ? getTimeAgo(Number(data.data.one_home_data.business_summary.data_summary.last_updated))
      : '';

  const timeRange = getDateRange(selectedfilter);

  return (
    <Box display="flex" flexDirection="column">
      {isInitialLoading ? (
        <Box marginBottom="spacing.5">
          <Skeleton width={{ base: '240px' }} height="32px" borderRadius="large" />
        </Box>
      ) : (
        <SectionHeader>
          <SectionHeader.Title>{messages.businessSummarySection.title}</SectionHeader.Title>
          <SectionHeader.Content>
            {renderSectionBadges(isFetching, timeRange, lastUpdatedAt)}
            {isInitialLoading ? null : (
              <SectionHeader.Actions>
                <DateFilter
                  options={dateFilterOptions}
                  selected={selectedfilter}
                  onChange={handleChangeFilter}
                  isDisabled={isFetching}
                />
              </SectionHeader.Actions>
            )}
          </SectionHeader.Content>
        </SectionHeader>
      )}

      <ErrorBoundary
        key={selectedfilter}
        rank={DASHBOARD_PRIORITY_RANKS.P0}
        team={DASHBOARD_TEAMS.R1_CONNECTED_EXPERIENCE}
        tags={{ module: 'One_Home_Business_Summary' }}
        FallbackComponent={() => (
          <ErrorState
            title={messages.businessSummarySection.errorMessage}
            withBorder={true}
            borderRadius="large"
          />
        )}
      >
        <BusinessSummaryContent
          isLoading={isLoading || isInitialLoading}
          data={{ earningsData, balanceData, spendsData }}
          error={error}
          dateFilter={selectedfilter}
        />
      </ErrorBoundary>
    </Box>
  );
};

export default BussinessInsights;
