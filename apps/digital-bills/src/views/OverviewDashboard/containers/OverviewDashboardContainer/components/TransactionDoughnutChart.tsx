import React, { ReactElement } from 'react';
import { Box, Text } from '@razorpay/blade/components';
import { formatNumber } from '@razorpay/i18nify-js/currency';
import { Doughnut, ChartComponentProps } from 'react-chartjs-2';

type TransactionDoughnutChartData = {
  colour: string;
  legend: string;
  value: number;
};

type TransactionDoughnutChartProps = {
  data: TransactionDoughnutChartData[];
};

const options: ChartComponentProps['options'] = {
  responsive: true,
  maintainAspectRatio: true,
  legend: {
    display: false,
  },
  cutoutPercentage: 80,
};

type TransactionDoughnutChartLegendProps = {
  legends: TransactionDoughnutChartData[];
};

const TransactionDoughnutChartLengends = (
  props: TransactionDoughnutChartLegendProps,
): ReactElement => {
  const { legends = [] } = props;

  return (
    <Box display="flex" flexDirection="column" height="100%" justifyContent="space-between">
      {legends.map((legend) => {
        const formatted = formatNumber(legend.value || 0, {
          intlOptions: {
            notation: 'compact',
            maximumFractionDigits: 2,
            trailingZeroDisplay: 'stripIfInteger',
            currencyDisplay: undefined,
          },
        });

        return (
          <Box
            key={legend.legend}
            flex="1"
            display="flex"
            alignItems={{ base: 'center', m: 'flex-start' }}
            justifyContent="space-between"
          >
            <Box gap="spacing.3" display="flex" alignItems="center">
              <div
                style={{
                  backgroundColor: legend.colour,
                  border: `1px solid ${legend.colour}`,
                  width: '14px',
                  height: '14px',
                }}
              />
              <Text variant="body" size="medium" weight="regular">
                {legend.legend}
              </Text>
            </Box>
            <Text variant="body" size="medium" weight="semibold">
              {formatted}
            </Text>
          </Box>
        );
      })}
    </Box>
  );
};

const TransactionDoughnutChart = (props: TransactionDoughnutChartProps): React.ReactElement => {
  const { data } = props;
  const chartData = {
    labels: data.map((transactionData) => transactionData.legend),
    datasets: [
      {
        data: data.map((transactionData) => transactionData.value),
        backgroundColor: data.map((transactionData) => transactionData.colour),
        borderColor: data.map((transactionData) => transactionData.colour),
        border: 1,
      },
    ],
  };
  return (
    <Box
      maxWidth="100%"
      maxHeight="100%"
      display={'flex'}
      flexDirection={{ base: 'column', m: 'row' }}
      gap="spacing.4"
      width="100%"
    >
      <Box maxWidth="100%" maxHeight="100%">
        <Doughnut width={100} data={chartData} options={options} />
      </Box>
      <Box flex="1">
        <TransactionDoughnutChartLengends legends={data} />
      </Box>
    </Box>
  );
};

export default TransactionDoughnutChart;
