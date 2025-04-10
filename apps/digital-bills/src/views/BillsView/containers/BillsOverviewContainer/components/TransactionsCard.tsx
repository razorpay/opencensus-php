import React from 'react';
import {
  Card,
  CardBody,
  Box,
  Divider,
  Heading,
  Spinner,
  useTheme,
} from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';

import TransactionCategory from '@apps/digital-bills/src/views/BillsView/containers/BillsOverviewContainer/components/TransactionCategory';
import { getCompactNumber } from '@apps/digital-bills/src/utils/helpers/numberFormatting';
import { TOTAL_TRANSACTIONS } from '@apps/digital-bills/src/utils/constants';
import RetryOnError from '@apps/digital-bills/src/common/components/RetryOnError';

import type { SelectedOverviewCategory } from '@apps/digital-bills/src/views/BillsView/containers/BillsOverviewContainer/types';

type TransactionsCardProps = {
  isLoading: boolean;
  isSelected?: boolean;
  totalTransactions: number;
  digitalTransactions: number;
  digitalTransPercent: number;
  printedTransactions: number;
  printedTransPercent: number;
  digitalPrintedTransactions: number;
  digitalPrintedTransPercent: number;
  setSelectedOverviewCategory: (type: SelectedOverviewCategory) => void;
  expandGraph: () => void;
  hasError?: boolean;
  retryFn?: () => void;
};

const TransactionsCard = ({
  isLoading,
  isSelected = false,
  totalTransactions,
  digitalTransactions,
  digitalTransPercent,
  printedTransactions,
  printedTransPercent,
  digitalPrintedTransactions,
  digitalPrintedTransPercent,
  setSelectedOverviewCategory,
  expandGraph,
  hasError = false,
  retryFn,
}: TransactionsCardProps): React.ReactElement => {
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedDeviceType === 'mobile';

  return (
    <Card
      isSelected={isSelected}
      height={{ base: 'auto', m: '125px' }}
      elevation="none"
      padding="spacing.5"
      accessibilityLabel="Total Transactions Card"
      onClick={() => {
        setSelectedOverviewCategory(TOTAL_TRANSACTIONS);
        expandGraph();
      }}
      data-analytics-name="transactions-info-card"
    >
      <CardBody height="100%">
        <Box display="flex" justifyContent="center" height="100%">
          {hasError ? (
            <Box display="flex" alignItems="center">
              <RetryOnError retryFn={retryFn} />
            </Box>
          ) : isLoading ? (
            <Spinner
              alignSelf="center"
              color="primary"
              label=""
              size="large"
              accessibilityLabel="Total Transactions Spinner"
            />
          ) : (
            <Box
              display="flex"
              flexDirection={{ base: 'column', m: 'row' }}
              height="100%"
              width="90%"
            >
              <Box display="flex" height="100%" justifyContent="flex-end">
                <Box
                  display="flex"
                  flexDirection="column"
                  justifyContent="space-between"
                  alignItems="flex-start"
                  height="100%"
                  flex={1}
                >
                  <Heading size="small" weight="regular">
                    {TOTAL_TRANSACTIONS}
                  </Heading>
                  <Heading size="large" weight="semibold">
                    {getCompactNumber(totalTransactions)}
                  </Heading>
                </Box>
              </Box>
              <Divider
                orientation={isMobile ? 'horizontal' : 'vertical'}
                variant="normal"
                thickness="thinner"
                marginX={{ base: 'spacing.0', m: '4vw' }}
                marginY={{ base: '4vw', m: 'spacing.0' }}
              />
              <Box display="flex" justifyContent="space-between" flex={3}>
                <TransactionCategory
                  heading="Digital"
                  transactions={digitalTransactions}
                  transactionsPercent={digitalTransPercent}
                />
                <TransactionCategory
                  heading="Print"
                  transactions={printedTransactions}
                  transactionsPercent={printedTransPercent}
                />
                <TransactionCategory
                  heading="Digital + Print"
                  transactions={digitalPrintedTransactions}
                  transactionsPercent={digitalPrintedTransPercent}
                />
              </Box>
            </Box>
          )}
        </Box>
      </CardBody>
    </Card>
  );
};

export default TransactionsCard;
