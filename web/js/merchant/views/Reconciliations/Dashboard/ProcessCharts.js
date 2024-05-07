import React, { useState, useEffect } from 'react';
import { Box, Heading, Text, Card, CardBody } from '@razorpay/blade/components';
import moment from 'moment';
import { Doughnut, Bar } from 'react-chartjs-2';
import { connect } from 'react-redux';
import { compose } from 'redux';
import styled from 'styled-components';

import { formatAmount } from 'common/utils/rzp-utils';
import { Loader } from 'merchant/views/Reconciliations/commonComponents';

const bgColor = ['#7DA2FF', '#305EFF', '#B2E949', '#909090'];

const LegendBox = styled.div`
  width: 16px;
  height: 16px;
  border-radius: 2px;
  background-color: ${(props) => props.color};
`;

const ProcessCharts = ({ stats, currency, error }) => {
  const trendsChartOptions = {
    responsive: true,
    scales: {
      xAxes: [
        {
          display: false,
          gridLines: {
            offsetGridLines: true,
          },
        },
      ],
      yAxes: [
        {
          display: false,
          gridLines: {
            offsetGridLines: true,
          },
        },
      ],
    },
    tooltips: {
      enabled: true,
      displayColors: false,
      mode: 'point',
      callbacks: {
        label: (tooltipItem) => {
          const { datasetIndex, value } = tooltipItem;
          const type = datasetIndex === 0 ? 'Reconciled' : 'Unreconciled';
          const val = Math.abs(Number(value));
          return `${type} ${val}`;
        },
      },
    },
  };
  const [chartData, setChartData] = useState({});
  const [trendsData, setTrendsData] = useState({});
  const [statsData, setStatsData] = useState({
    unreconciled_split: [],
    recon_trend: [],
  });

  const getChartFormatData = (stats) => {
    const data = stats?.unreconciled_split;
    if (Array.isArray(data)) {
      const labels = data.map((item) => item.reason);
      return {
        labels,
        datasets: [
          {
            label: 'Unreconciled Reasons Split',
            backgroundColor: bgColor,
            data: data.map((item) => item.count),
            borderWidth: 0,
          },
        ],
      };
    } else {
      return { labels: [], datasets: [] };
    }
  };

  const getTrendsChartData = (stats) => {
    const d = stats?.recon_trend;
    let reconciled = [];
    let unreconciled = [];
    let labels = [];
    if (Array.isArray(d)) {
      reconciled = d.map((trend) => trend?.Stats?.Reconciled?.count);
      unreconciled = d.map((trend) => trend?.Stats?.Unreconciled?.count * -1);
      labels = d.map((trend) => moment(trend?.time * 1000).format('lll'));
    }
    const data = {
      labels,
      datasets: [
        {
          label: 'Reconciled',
          data: reconciled,
          backgroundColor: '#B2E949',
          stack: 'Stack 0',
          barThickness: 18,
          borderRadius: 6,
        },
        {
          label: 'Unreconciled',
          data: unreconciled,
          backgroundColor: '#F13737',
          barThickness: 18,
          borderRadius: 6,
          stack: 'Stack 0',
        },
      ],
    };
    return data;
  };

  const chartOptions = {
    cutoutPercentage: 80,
    tooltips: {
      enabled: true,
      displayColors: false,
      mode: 'point',
      callbacks: {
        title: (tooltipItem) => {
          const index = tooltipItem[0].index;
          const item = statsData?.unreconciled_split[index];
          return item?.reason;
        },
        label: (tooltipItem) => {
          const index = tooltipItem.index;
          const item = statsData?.unreconciled_split[index];
          return `Records Count: ${item?.count}, Sum: ${formatAmount(item?.sum, true, currency)}`;
        },
      },
    },
  };

  useEffect(() => {
    const chartData = getChartFormatData(stats);
    const trendsData = getTrendsChartData(stats);
    setChartData(chartData);
    setStatsData(stats);
    setTrendsData(trendsData);
  }, [stats]);

  if (error) {
    return null;
  } else if (chartData?.labels?.length > 0) {
    return (
      <Box
        display="flex"
        justifyContent="space-between"
        testID="recon-overview-charts"
        marginTop="spacing.6"
      >
        <Card width="49%" minHeight="100%">
          <CardBody>
            <Box>
              <Heading weight="semibold" size="small" color="surface.text.gray.subtle">
                Reconciliation trend
              </Heading>
              <Box>
                <Box marginTop="spacing.6" width="100%" height="100px">
                  <Bar data={trendsData} options={trendsChartOptions} />
                </Box>
                {Array.isArray(statsData.recon_trend) && statsData.recon_trend.length > 0 ? (
                  <Box
                    display="flex"
                    alignItems="flex-end"
                    flexDirection="column"
                    marginTop="spacing.2"
                  >
                    <Box textAlign="center">
                      <Text size="small" marginLeft="spacing.2" color="surface.text.gray.muted">
                        Last run at
                      </Text>
                      <Text
                        size="medium"
                        weight="semibold"
                        marginLeft="spacing.2"
                        color="surface.text.gray.muted"
                      >
                        {moment(
                          statsData?.recon_trend[statsData?.recon_trend.length - 1]?.time * 1000,
                        ).format('lll')}
                      </Text>
                    </Box>
                  </Box>
                ) : null}
                <Box display="flex" marginTop="spacing.10">
                  <Box display="flex" alignItems="center">
                    <LegendBox color="#B2E949" />
                    <Text size="medium" marginLeft="spacing.2" color="surface.text.gray.subtle">
                      Reconciled
                    </Text>
                  </Box>
                  <Box display="flex" alignItems="center" marginLeft="spacing.4">
                    <LegendBox color="#F13737" />
                    <Text size="medium" marginLeft="spacing.2" color="surface.text.gray.subtle">
                      Unreconciled
                    </Text>
                  </Box>
                </Box>
              </Box>
            </Box>
          </CardBody>
        </Card>
        <Card minHeight="100%" width="49%">
          <CardBody>
            <Box>
              <Heading weight="semibold" size="small" color="surface.text.gray.subtle">
                Unreconciled Reasons Split
              </Heading>
              <Box>
                <Box display="flex">
                  <Box marginTop="spacing.8" width="50%">
                    <Doughnut data={chartData} options={chartOptions} />
                  </Box>
                  <Box display="grid" gridTemplateColumns="1fr 1fr" gap="40px">
                    {chartData?.labels.map((label, index) => {
                      const item = statsData?.unreconciled_split[index];
                      return (
                        <Box key={item?.reason} display="flex" marginTop="spacing.4">
                          <LegendBox color={bgColor[index]} />
                          <Box marginLeft="spacing.4">
                            <Text size="medium" color="surface.text.gray.subtle">
                              {item?.reason}
                            </Text>
                            <Text size="medium" weight="semibold" color="surface.text.gray.normal">
                              {item?.count} records
                            </Text>
                            <Text size="medium" color="surface.text.gray.subtle">
                              {formatAmount(item?.sum, true, currency)}
                            </Text>
                          </Box>
                        </Box>
                      );
                    })}
                  </Box>
                </Box>
              </Box>
            </Box>
          </CardBody>
        </Card>
      </Box>
    );
  } else {
    return (
      <Card marginTop="spacing.6">
        <CardBody>
          <Loader />
        </CardBody>
      </Card>
    );
  }
};

export default compose(
  connect((state) => ({
    currency: state.session.user?.merchant?.currency,
  })),
)(ProcessCharts);
