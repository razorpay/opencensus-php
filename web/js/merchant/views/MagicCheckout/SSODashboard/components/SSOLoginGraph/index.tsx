import React, { useEffect, useMemo, useState } from 'react';
import moment from 'moment';

import { Box, Card, CardBody, Heading, Spinner } from '@razorpay/blade/components';
import { Line } from 'react-chartjs-2';

import {
  getChartDatasets,
  getChartOptions,
} from 'merchant/views/MagicCheckout/OrderAnalytics/utils';
import type { SSOGraphData } from 'merchant/views/MagicCheckout/SSODashboard/types';
import { CHART_OPTIONS } from './options';
import { CHART_LABEL_MAPPING } from 'merchant/views/MagicCheckout/OrderAnalytics/constants';

interface SSOLoginGraphProps {
  data: SSOGraphData | null;
  aggregate: 'daily' | 'hourly';
  isFetching: boolean;
}

const SSOLoginGraph = ({ isFetching, aggregate, data }: SSOLoginGraphProps) => {
  const [chartOptions, setChartOptions] = useState(null);

  const chartData = useMemo(() => {
    const dataValues = [...(data?.values || [])];
    const datasets = getChartDatasets(dataValues, CHART_LABEL_MAPPING.LOGIN_TREND, true, 'number');
    const labels = data?.timestamps?.map((t) => moment.unix(t).local()) || [];
    return {
      datasets,
      labels,
    };
  }, [data]);

  useEffect(() => {
    setChartOptions(getChartOptions(CHART_OPTIONS, aggregate, true));
  }, [aggregate]);

  return (
    <Box display="flex" marginTop="spacing.8" marginBottom="spacing.8">
      <Card
        backgroundColor="surface.background.gray.intense"
        borderRadius="medium"
        elevation="none"
        padding="spacing.5"
        width="100%"
      >
        <CardBody>
          {isFetching ? (
            <Box minHeight="200px" display="flex" alignItems="center" justifyContent="center">
              <Spinner size="xlarge" accessibilityLabel="spinner" />
            </Box>
          ) : (
            <div className="chart-item">
              <Box display="flex" flexDirection="column" gap="spacing.7">
                <Heading size="medium">Login Trends - Total Login vs New Users</Heading>
                <Box display="flex">
                  <Line options={chartOptions ?? {}} data={chartData ?? {}} />
                </Box>
              </Box>
            </div>
          )}
        </CardBody>
      </Card>
    </Box>
  );
};

export default SSOLoginGraph;
