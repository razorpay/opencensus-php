import React from 'react';
import { ArrowRightIcon, Link, Box, Heading, useTheme, Divider } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';

import { graphqlRequest } from '@apps/digital-bills/src/utils/graphql';
import WalletBalanceIcon from '@apps/digital-bills/src/assets/icons/wallet.svg';
import TreesSavedIcon from '@apps/digital-bills/src/assets/icons/trees-saved.svg';
import ActiveStoresIcon from '@apps/digital-bills/src/assets/icons/active-stores.svg';
import {
  DIGITAL,
  DIGITAL_PRINT,
  DurationRange,
  PRINT,
} from '@apps/digital-bills/src/utils/constants';
import { currDate, pastDate } from '@apps/digital-bills/src/utils/helpers/getDateRangeFromInterval';
import {
  BILL_SALES_STATS_QUERY,
  BILL_TRANSACTION_STATS_QUERY,
  BILL_WALLET_BALANCE_QUERY,
} from '@apps/digital-bills/src/views/OverviewDashboard/queries';
import {
  BillSalesStatsResponse,
  BillTransactionStatsResponse,
  BillWalletBalanceResponse,
} from '@apps/digital-bills/src/views/OverviewDashboard/types';
import { IFRAME_LABELS_AND_LINKS } from '@apps/digital-bills/src/views/OverviewDashboard/constants';
import { STORES_DATA_QUERY } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/queries';
import InfoContainer from '@apps/digital-bills/src/views/OverviewDashboard/containers/OverviewDashboardContainer/components/InfoContainer';
import SummaryCard from '@apps/digital-bills/src/views/OverviewDashboard/containers/OverviewDashboardContainer/components/SummaryCard';
import LinkCard from '@apps/digital-bills/src/views/OverviewDashboard/containers/OverviewDashboardContainer/components/LinkCard';
import TransactionCard from '@apps/digital-bills/src/views/OverviewDashboard/containers/OverviewDashboardContainer/components/TransactionCard';
import TransactionDetails from '@apps/digital-bills/src/views/OverviewDashboard/containers/OverviewDashboardContainer/components/TransactionDetails';

import type { StoresDataResponse } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

const OverviewDashboardContainer = (): React.ReactElement => {
  const { theme } = useTheme();
  const navigate = useNavigate();
  const {
    data: billSales,
    isFetching: isSalesInfoLoading,
    isError: isErrorInSalesInfo,
    refetch: refetchSalesInfo,
  } = useQuery<BillSalesStatsResponse>({
    queryKey: ['bill_sales_stats'],
    refetchOnWindowFocus: false,
    queryFn: () =>
      graphqlRequest({
        document: BILL_SALES_STATS_QUERY,
        variables: {
          fromDate: pastDate(DurationRange.Last90Days),
          toDate: currDate(),
        },
      }),
  });

  const {
    data: billTransactions,
    isFetching: isTransactionsInfoLoading,
    isError: isErrorInTransactionsInfo,
    refetch: refetchTransactionsInfo,
  } = useQuery<BillTransactionStatsResponse>({
    queryKey: ['bill_transaction_stats'],
    refetchOnWindowFocus: false,
    queryFn: () =>
      graphqlRequest({
        document: BILL_TRANSACTION_STATS_QUERY,
        variables: {
          fromDate: pastDate(DurationRange.Last90Days),
          toDate: currDate(),
        },
      }),
  });

  const {
    data: billWallet,
    isFetching: isWalletBalanceLoading,
    isError: isErrorInWalletBalance,
    refetch: refetchWalletBalance,
  } = useQuery<BillWalletBalanceResponse>({
    queryKey: ['bill_wallet_balance'],
    refetchOnWindowFocus: false,
    queryFn: () =>
      graphqlRequest({
        document: BILL_WALLET_BALANCE_QUERY,
      }),
  });

  const {
    data: storesData,
    isFetching: isStoresInfoLoading,
    isError: isErrorInStoresInfo,
    refetch: refetchStoresInfo,
  } = useQuery<StoresDataResponse>({
    queryKey: ['stores_data'],
    refetchOnWindowFocus: false,
    queryFn: () =>
      graphqlRequest({
        document: STORES_DATA_QUERY,
        variables: {
          limit: 1,
          offset: 0,
          isDeleted: false,
        },
      }),
  });

  const billSalesInfo = billSales?.billSalesStats ?? {
    avgSales: 0,
    totalSales: 0,
  };
  const billTransactionsInfo = billTransactions?.billTransactionStats ?? {
    totalTransactions: 0,
    treesSaved: 0,
    transactionSummary: {},
  };
  const billWalletBalanceInfo = billWallet?.billWalletBalance ?? {
    balance: 0,
  };
  const storesInfo = storesData?.stores ?? {
    total: 0,
  };

  const LEGEND_KEYS = [
    { key: DIGITAL, label: 'Digital', color: theme.colors.interactive.icon.primary.subtle },
    { key: PRINT, label: 'Print', color: theme.colors.interactive.icon.information.muted },
    {
      key: DIGITAL_PRINT,
      label: 'Digital + Print',
      color: theme.colors.interactive.icon.neutral.muted,
    },
  ] as const;

  return (
    <Box alignItems="stretch" display="flex" flexWrap="wrap" gap="spacing.5" flexDirection="column">
      <Heading size="2xlarge" marginBottom="spacing.4">
        Dashboard
      </Heading>
      {/* Summary Section */}
      <Box width="100%">
        <InfoContainer title={<Heading size="medium">Summary</Heading>}>
          <Box
            flexWrap="wrap"
            display="flex"
            justifyContent="space-between"
            gap="spacing.5"
            paddingX="spacing.7"
            paddingY="spacing.7"
          >
            <SummaryCard
              title="Wallet Balance"
              info="This is the sum available for spending on SMS, Email, WhatsApp, and Digital Bills."
              amount={billWalletBalanceInfo?.balance}
              heroImg={WalletBalanceIcon}
              showCurrency
              isLoading={isWalletBalanceLoading}
              hasError={isErrorInWalletBalance}
              retryFn={refetchWalletBalance}
            />
            <SummaryCard
              heroImg={TreesSavedIcon}
              title="Trees Saved"
              info="This figure is for the past 3 months"
              amount={billTransactionsInfo?.treesSaved}
              isLoading={isTransactionsInfoLoading}
              hasError={isErrorInTransactionsInfo}
              retryFn={refetchTransactionsInfo}
            />
            <SummaryCard
              heroImg={ActiveStoresIcon}
              title="Active Stores"
              amount={storesInfo?.total}
              isLoading={isStoresInfoLoading}
              hasError={isErrorInStoresInfo}
              retryFn={refetchStoresInfo}
            />
          </Box>
        </InfoContainer>
      </Box>

      <Box display="flex" gap="spacing.4" flexWrap="wrap" width="100%">
        {/* Bills section */}
        <Box flex="3.5">
          <InfoContainer
            title={<Heading size="medium">Bills View</Heading>}
            info="The following figures are for the past 3 months"
          >
            <Box padding="spacing.7" paddingBottom="spacing.5">
              <Box flexWrap="wrap" display="flex" justifyContent="space-between" gap="spacing.4">
                <TransactionCard
                  title="Total Sales"
                  amount={billSalesInfo?.totalSales || 0}
                  isLoading={isSalesInfoLoading}
                  hasError={isErrorInSalesInfo}
                  retryFn={refetchSalesInfo}
                />
                <TransactionCard
                  title="Average Sales"
                  amount={billSalesInfo?.avgSales || 0}
                  isLoading={isSalesInfoLoading}
                  hasError={isErrorInSalesInfo}
                  retryFn={refetchSalesInfo}
                />
                <TransactionDetails
                  flex="3"
                  totalTransactions={billTransactionsInfo?.totalTransactions || 0}
                  data={LEGEND_KEYS.map((legend) => ({
                    key: legend.key,
                    legend: legend.label,
                    value: billTransactionsInfo?.transactionSummary?.[legend.key] || 0,
                    colour: legend.color,
                  }))}
                  isLoading={isTransactionsInfoLoading}
                  hasError={isErrorInTransactionsInfo}
                  retryFn={refetchTransactionsInfo}
                />
              </Box>
              <Box paddingTop="spacing.5" textAlign="right">
                <Link
                  variant="button"
                  size="large"
                  icon={ArrowRightIcon}
                  iconPosition="right"
                  onClick={() => navigate('bills/')}
                >
                  Go to Bills View
                </Link>
              </Box>
            </Box>
          </InfoContainer>
        </Box>

        {/* Your Customers section */}
        <Box flex={1}>
          <InfoContainer title={<Heading size="medium">Your Customers</Heading>}>
            {IFRAME_LABELS_AND_LINKS.YOUR_CUSTOMERS.map((linkInfo, index) => {
              const { label, href } = linkInfo;
              return (
                <Box key={href}>
                  <LinkCard label={label} href={href} />
                  {index < IFRAME_LABELS_AND_LINKS.YOUR_CUSTOMERS.length - 1 ? (
                    <Divider dividerStyle="dashed" variant="normal" thickness="thinner" />
                  ) : null}
                </Box>
              );
            })}
          </InfoContainer>
        </Box>
      </Box>

      <Box display="flex" gap="spacing.5" flexWrap="wrap" width="100%">
        {/* Campaigns section */}
        <Box flex="1">
          <InfoContainer title={<Heading size="medium">Campaigns</Heading>}>
            {IFRAME_LABELS_AND_LINKS.CAMPAIGNS.map((linkInfo, index) => {
              const { label, href } = linkInfo;
              return (
                <Box key={href}>
                  <LinkCard label={label} href={href} />
                  {index < IFRAME_LABELS_AND_LINKS.CAMPAIGNS.length - 1 ? (
                    <Divider dividerStyle="dashed" variant="normal" thickness="thinner" />
                  ) : null}
                </Box>
              );
            })}
          </InfoContainer>
        </Box>

        {/* Miscellaneous section */}
        <Box flex="1">
          <InfoContainer title={<Heading size="medium">Miscellaneous</Heading>}>
            {IFRAME_LABELS_AND_LINKS.MISCELLANEOUS.map((linkInfo, index) => {
              const { label, href } = linkInfo;
              return (
                <Box key={href}>
                  <LinkCard label={label} href={href} />
                  {index < IFRAME_LABELS_AND_LINKS.MISCELLANEOUS.length - 1 ? (
                    <Divider dividerStyle="dashed" variant="normal" thickness="thinner" />
                  ) : null}
                </Box>
              );
            })}
          </InfoContainer>
        </Box>
      </Box>
    </Box>
  );
};

export default OverviewDashboardContainer;
