import React from 'react';
import { Box, BoxProps, Divider, Heading, Spinner } from '@razorpay/blade/components';
import { formatNumber } from '@razorpay/i18nify-js/currency';

import RetryOnError from '@apps/digital-bills/src/common/components/RetryOnError';
import TransactionDoughnutChart from '@apps/digital-bills/src/views/OverviewDashboard/containers/OverviewDashboardContainer/components/TransactionDoughnutChart';

type TransactionData = {
  legend: string;
  value: number;
  colour: string;
};

type TransactionDetailsProp = {
  totalTransactions: number;
  data: TransactionData[];
  flex?: BoxProps['flex'];
  isLoading?: boolean;
  hasError?: boolean;
  retryFn?: () => void;
};

const TransactionDetails = (props: TransactionDetailsProp): React.ReactElement => {
  const {
    totalTransactions = 0,
    flex,
    data = [],
    isLoading = false,
    hasError = false,
    retryFn,
  } = props;

  const formatted = formatNumber(totalTransactions, {
    intlOptions: {
      notation: 'compact',
      maximumFractionDigits: 2,
      trailingZeroDisplay: 'stripIfInteger',
      currencyDisplay: undefined,
    },
  });

  const renderContent = () => {
    if (hasError) return <RetryOnError retryFn={retryFn} />;
    return isLoading ? (
      <Spinner
        alignSelf="flex-start"
        color="primary"
        label=""
        accessibilityLabel="Transaction Details spinner"
      />
    ) : (
      <Heading weight="semibold" size="large">
        {formatted}
      </Heading>
    );
  };

  return (
    <Box
      display="flex"
      backgroundColor="surface.background.gray.intense"
      borderRadius="small"
      padding="spacing.3"
      paddingX="spacing.6"
      gap="spacing.5"
      alignItems="stretch"
      flex={flex}
      flexWrap="wrap"
      width={{ base: '100%', l: 'auto' }}
    >
      <Box
        display="flex"
        flexDirection={{ base: 'row', l: 'column' }}
        justifyContent="space-between"
        width={{ base: '100%', l: 'auto' }}
      >
        <Heading size="small" weight="regular">
          Total Transactions
        </Heading>
        {renderContent()}
      </Box>
      <Divider orientation="vertical" display={{ base: 'none', l: 'block' }} variant="normal" />
      <Box flex="1" display="flex" width={{ base: '60%', s: '60%' }}>
        <TransactionDoughnutChart data={data} />
      </Box>
    </Box>
  );
};

export default TransactionDetails;
