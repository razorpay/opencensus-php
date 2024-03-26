import React, { useState, useEffect, useMemo } from 'react';
import { Box } from '@razorpay/blade/components';
import { Bar, Line } from 'react-chartjs-2';

import { ChartInterval } from 'merchant/views/RiskAndFraud/RiskAnalytics/components';
import { RISK_DECLINED } from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';
import { generateChartData } from 'merchant/views/RiskAndFraud/RiskAnalytics/utils';
import { StyledChartLoader, StyledChartError } from 'merchant/views/RiskAndFraud/components/styled';

import ChartLegend from './ChartLegend';
import { getChartAreaConfig } from './chartConfig';
import { ChartComponentType, ChartContainerProps, ChartData } from './types';

const ChartContainer: React.FC<ChartContainerProps> = (props) => {
  const {
    isLoading,
    isError,
    entity,
    dateRange,
    selectedInterval,
    metric,
    graphOptions,
    queryData,
    handleInterval,
  } = props;
  const ChartComponent: ChartComponentType = entity !== RISK_DECLINED ? Bar : Line;
  const [chartData, setChartData] = useState<ChartData>({ labels: [], datasets: [] });

  const chartOptions = useMemo(() => {
    return getChartAreaConfig({ entity, metric, selectedInterval, chartData });
  }, [entity, metric, selectedInterval, chartData]);

  const chart = useMemo(() => {
    return generateChartData({ entity, metric, graphOptions, queryData });
  }, [entity, metric, graphOptions, queryData]);

  useEffect(() => {
    setChartData(chart);
  }, [chart]);

  return (
    <Box
      testID="chart-container"
      display="flex"
      flexDirection="column"
      paddingTop="spacing.5"
      marginBottom="spacing.9"
    >
      <ChartInterval
        isLoading={isLoading}
        dateRange={dateRange}
        selectedInterval={selectedInterval}
        handleInterval={handleInterval}
      />
      <Box marginBottom="spacing.6" position="relative">
        <ChartComponent height={240} data={chartData} options={chartOptions} />
        {isLoading && <StyledChartLoader data-testid="chart-loader" />}
        {isError && <StyledChartError data-testid="chart-error" />}
      </Box>
      <ChartLegend entity={entity} metric={metric} />
    </Box>
  );
};

export default ChartContainer;
