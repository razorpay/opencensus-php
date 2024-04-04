import React, { useReducer } from 'react';
import { Box } from '@razorpay/blade/components';
import { useQuery, useQueryClient } from '@tanstack/react-query';

import BlockRule from 'merchant/views/RiskAndFraud/RiskAnalytics/BlockRule';
import ChartContainer from 'merchant/views/RiskAndFraud/RiskAnalytics/ChartContainer';
import DownloadReports from 'merchant/views/RiskAndFraud/RiskAnalytics/DownloadReports';
import StatsOverview from 'merchant/views/RiskAndFraud/RiskAnalytics/StatsOverview';
import { EntityFilters, EntityHeader } from 'merchant/views/RiskAndFraud/RiskAnalytics/components';
import {
  SET_DATE_RANGE,
  SET_METRIC,
  SET_CHART_OPTIONS,
  SET_INTERVAL,
  RISK_DECLINED,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';
import riskAnalyticsReducer from 'merchant/views/RiskAndFraud/RiskAnalytics/reducer';
import { fetchAnalytics } from 'merchant/views/RiskAndFraud/RiskAnalytics/services';

import { getBreakdownInterval, getInitialState } from './utils';
import { trackEvent } from '../../common/trackEvents';
import {
  Grid,
  GridItem,
  DownloadReportsContainer,
  BlocklistContainer,
} from '../../components/styled';
import { SelectedGraphOption } from '../ChartContainer/types';
import EntityAnalyticsTable from '../EntityAnalyticsTable';
import { calculateStats } from '../utils';

import type { EntityAnalyticsProps, AnalyticsReducer } from './types';
import type {
  DateRange,
  MetricOptions,
  IntervalValue,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/types';

const EntityAnalytics: React.FC<EntityAnalyticsProps> = ({ ratios, entity }) => {
  const queryClient = useQueryClient();
  const initialState = getInitialState(entity);
  const [state, dispatch] = useReducer<AnalyticsReducer>(riskAnalyticsReducer, initialState);

  const { dateRange, metric, graphOptions, interval } = state;
  const { startDate, endDate } = dateRange;

  const {
    isLoading,
    isError,
    data: queryData,
  } = useQuery({
    queryKey: [entity, { startDate, endDate, interval }],
    queryFn: () => fetchAnalytics({ entity, metric, dateRange, interval, graphOptions }),
    cacheTime: 15 * 60 * 1000, // Cache data for 15 minutes
    staleTime: 15 * 60 * 1000, // Data remains fresh for 15 minutes
    retry: false,
    refetchOnWindowFocus: false,
    enabled: startDate !== null && endDate !== null,
  });

  const handleDurationChange = (newValue: DateRange) => {
    const { startDate, endDate } = newValue;
    const newInterval = getBreakdownInterval(startDate as number, endDate as number);
    dispatch({ type: SET_DATE_RANGE, payload: newValue });
    dispatch({ type: SET_INTERVAL, payload: newInterval });
    trackEvent({
      objectName: 'Duration',
      properties: { dateRange: newValue, chartInterval: newInterval, section: entity },
    });
  };

  const handleMetricChange = (newValue: MetricOptions) => {
    dispatch({ type: SET_METRIC, payload: newValue });
    const newStats = calculateStats(queryData?.data ?? [], newValue);
    queryClient.setQueryData([entity, { startDate, endDate, interval }], (prevData) => ({
      ...(prevData as object),
      stats: newStats,
    }));
    trackEvent({
      objectName: 'Duration',
      actionName: 'Change',
      properties: { metric: newValue, section: entity },
    });
  };

  const handleGraphOptions = (newValue: SelectedGraphOption[]) => {
    dispatch({ type: SET_CHART_OPTIONS, payload: newValue });
    trackEvent({
      objectName: 'Graph Options',
      actionName: 'Change',
      properties: { chartOptions: newValue, section: entity },
    });
  };

  const handleInterval = (newValue: IntervalValue) => {
    dispatch({ type: SET_INTERVAL, payload: newValue });
    trackEvent({
      objectName: 'Chart Interval',
      actionName: 'Change',
      properties: { chartInterval: newValue, section: entity },
    });
  };

  const { data, stats, chartData } = queryData || {
    data: [],
    stats: {},
    chartData: { labels: [], datasets: [] },
  };

  return (
    <Box
      display="flex"
      flexDirection="column"
      marginTop="spacing.5"
      padding={['spacing.5', 'spacing.7', 'spacing.5', 'spacing.7']}
      backgroundColor="surface.background.gray.intense"
    >
      <EntityHeader entity={entity} />
      <EntityFilters
        entity={entity}
        dateRange={dateRange}
        metric={metric}
        graphOptions={graphOptions}
        handleDurationChange={handleDurationChange}
        handleMetricChange={handleMetricChange}
        handleGraphOptions={handleGraphOptions}
      />
      <StatsOverview
        isLoading={isLoading}
        entity={entity}
        metric={metric}
        ratios={ratios}
        stats={stats}
      />
      <ChartContainer
        isLoading={isLoading}
        isError={isError}
        entity={entity}
        dateRange={dateRange}
        selectedInterval={interval}
        metric={metric}
        chartData={chartData}
        queryData={data}
        graphOptions={graphOptions}
        handleInterval={handleInterval}
      />
      <Grid>
        {entity !== RISK_DECLINED && (
          <GridItem columns={5}>
            <EntityAnalyticsTable entity={entity} dateRange={dateRange} />
          </GridItem>
        )}
        <GridItem columns={entity !== RISK_DECLINED ? 3 : 8}>
          <DownloadReportsContainer>
            <DownloadReports entity={entity} />
          </DownloadReportsContainer>
          <BlocklistContainer>
            {entity !== RISK_DECLINED ? <BlockRule entity={entity} /> : null}
          </BlocklistContainer>
        </GridItem>
      </Grid>
    </Box>
  );
};

export default EntityAnalytics;
