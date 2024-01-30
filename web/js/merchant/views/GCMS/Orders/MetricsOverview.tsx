import React from 'react';
import { Amount, Box, Text } from '@razorpay/blade/components';
import { Doughnut } from 'react-chartjs-2';

interface Props {
  title: string;
  b2bValue: number;
  b2cValue: number;
}

const MetricsOverview = ({ title, b2bValue, b2cValue }: Props) => {
  const totalValue = b2bValue + b2cValue;
  // const b2bPercentage = (b2bValue / totalValue) * 100;
  // const b2cPercentage = (b2cValue / totalValue) * 100;
  return (
    <Box
      display={'flex'}
      flex={1}
      flexDirection={'column'}
      maxWidth={{
        l: '33%', //TODO: how to provide a width of 33%?
        m: '100%',
        s: '100%',
      }}
      backgroundColor={'surface.background.level1.lowContrast'}
      marginX={'spacing.2'}
      padding={'spacing.2'}
    >
      <Box display={'flex'} justifyContent={'space-between'}>
        <Text>{title}</Text>
        <Box display={'flex'}>
          <Text>Total: </Text>
          <Amount value={totalValue} />
        </Box>
      </Box>
      <Box marginTop={'spacing.7'}>
        <Doughnut
          data={{
            // labels: [`${b2bPercentage}%`, `${b2cPercentage}%`],
            labels: ['B2B', 'B2C'],
            datasets: [
              {
                data: [b2bValue, b2cValue],
                backgroundColor: ['#30C5D8', '#324664'],
              },
            ],
          }}
        />
      </Box>
    </Box>
  );
};

export default MetricsOverview;
