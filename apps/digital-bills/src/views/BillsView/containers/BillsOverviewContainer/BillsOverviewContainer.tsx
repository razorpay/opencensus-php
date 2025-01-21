import React from 'react';
import {
  Card,
  CardBody,
  Box,
  Divider,
  ChevronDownIcon,
  ChevronUpIcon,
  Link,
} from '@razorpay/blade/components';

import { useQuery } from '@tanstack/react-query';
import { graphqlRequest } from '@apps/digital-bills/src/utils/graphql';
import SalesCard from '@apps/digital-bills/src/views/BillsView/containers/BillsOverviewContainer/components/SalesCard';
import TransactionsCard from '@apps/digital-bills/src/views/BillsView/containers/BillsOverviewContainer/components/TransactionsCard';
import Graph from '@apps/digital-bills/src/views/BillsView/containers/BillsOverviewContainer/components/Graph';
import OverviewHeader from '@apps/digital-bills/src/views/BillsView/containers/BillsOverviewContainer/components/OverviewHeader';
import { useBillsOverviewStore } from '@apps/digital-bills/src/views/BillsView/containers/BillsOverviewContainer/store';
import {
  SALES_OVERVIEW_QUERY,
  TRANSACTIONS_OVERVIEW_QUERY,
} from '@apps/digital-bills/src/views/BillsView/containers/BillsOverviewContainer/queries';
import Breadcrumbs from '@apps/digital-bills/src/common/components/Breadcrumbs';
import {
  TOTAL_SALES,
  AVERAGE_SALES,
  TOTAL_TRANSACTIONS,
  DurationRange,
} from '@apps/digital-bills/src/utils/constants';
import { calculatePercentage } from '@apps/digital-bills/src/utils/helpers/calculatePercentage';
import { currDate, pastDate } from '@apps/digital-bills/src/utils/helpers/getDateRangeFromInterval';

import type { BreadCrumbType } from '@apps/digital-bills/src/common/components/Breadcrumbs/types';
import {
  SalesResponse,
  TransactionsResponse,
} from '@apps/digital-bills/src/views/BillsView/containers/BillsOverviewContainer/types';

const pageBreadCrumbs: BreadCrumbType[] = [{ label: 'BillMe' }, { label: 'Bills View' }];

const BillsOverviewContainer = (): React.ReactElement => {
  const [overviewTimeRange, setOverviewTimeRange] = React.useState({
    fromDate: pastDate(DurationRange.Last30Days),
    toDate: currDate(),
  });
  const {
    selectedOverviewCategory,
    isGraphExpanded,
    setSelectedOverviewCategory,
    expandGraph,
    collapseGraph,
  } = useBillsOverviewStore();

  const graphTogglehandler = (isExpanded: boolean) => {
    if (!isExpanded) {
      setSelectedOverviewCategory(TOTAL_SALES);
      expandGraph();
    } else {
      setSelectedOverviewCategory(null);
      collapseGraph();
    }
  };

  const {
    data: salesData,
    isFetching: isSalesDataFetching,
    isError: isErrorInSalesInfo,
    refetch: refetchSalesInfo,
  } = useQuery<SalesResponse>({
    queryKey: ['sales_overview_data', overviewTimeRange],
    refetchOnWindowFocus: false,
    queryFn: () =>
      graphqlRequest({
        document: SALES_OVERVIEW_QUERY,
        variables: overviewTimeRange,
      }),
  });

  const {
    data: transactionsData,
    isFetching: isTransactionDataFetching,
    isError: isErrorInTransactionsInfo,
    refetch: refetchTransactionsInfo,
  } = useQuery<TransactionsResponse>({
    refetchOnWindowFocus: false,
    queryKey: ['transactions_overview_data', overviewTimeRange],
    queryFn: () =>
      graphqlRequest({
        document: TRANSACTIONS_OVERVIEW_QUERY,
        variables: overviewTimeRange,
      }),
  });

  const salesLabels = salesData?.billSalesStats?.overall?.map((item) => item?.date) || [];
  const totalSalesData = salesData?.billSalesStats?.overall?.map((item) => item?.totalSales) || [];
  const averageSalesData = salesData?.billSalesStats?.overall?.map((item) => item?.avgSales) || [];
  const transactionsLabels =
    transactionsData?.billTransactionStats?.transactionOverview?.map((item) => item?.date) || [];
  const totalTransactionsData =
    transactionsData?.billTransactionStats?.transactionOverview?.map(
      (item) => item?.totalTransactions,
    ) || [];

  const getGraphData = (category: string | null) => {
    switch (category) {
      case TOTAL_SALES:
        return { labels: salesLabels, data: totalSalesData };
      case AVERAGE_SALES:
        return { labels: salesLabels, data: averageSalesData };
      case TOTAL_TRANSACTIONS:
        return { labels: transactionsLabels, data: totalTransactionsData };
      default:
        return { labels: salesLabels, data: totalSalesData };
    }
  };

  const getGraphLabel = (category: string | null) => {
    switch (category) {
      case TOTAL_SALES:
        return 'Total Sales';
      case AVERAGE_SALES:
        return 'Average Sales';
      case TOTAL_TRANSACTIONS:
        return 'Total Transactions';
      default:
        return 'Total Sales';
    }
  };

  const totalTransactions = transactionsData?.billTransactionStats?.totalTransactions ?? 0;
  const digitalTransactions =
    transactionsData?.billTransactionStats?.transactionSummary?.DIGITAL ?? 0;
  const printedTransactions =
    transactionsData?.billTransactionStats?.transactionSummary?.PRINT ?? 0;
  const digitalPrintedTransactions =
    transactionsData?.billTransactionStats?.transactionSummary?.DIGITAL_PRINT ?? 0;

  return (
    <Card backgroundColor="surface.background.gray.moderate">
      <CardBody>
        <Breadcrumbs items={pageBreadCrumbs} />
        <Divider variant="normal" thickness="thinner" />
        <Box paddingTop="spacing.4">
          <OverviewHeader
            overviewTimeRange={overviewTimeRange}
            setOverviewTimeRange={(fromDate, toDate) => setOverviewTimeRange({ fromDate, toDate })}
          />
          <Box
            display="flex"
            flexDirection={{ base: 'column', l: 'row' }}
            gap="spacing.5"
            marginBottom={isGraphExpanded ? 'spacing.11' : 'spacing.8'}
          >
            <Box display="flex" flex={2} gap="spacing.5">
              <Box flex={1}>
                <SalesCard
                  isLoading={isSalesDataFetching}
                  heading={TOTAL_SALES}
                  value={salesData?.billSalesStats?.totalSales}
                  isSelected={selectedOverviewCategory === TOTAL_SALES}
                  setSelectedOverviewCategory={() => setSelectedOverviewCategory(TOTAL_SALES)}
                  expandGraph={expandGraph}
                  hasError={isErrorInSalesInfo}
                  retryFn={refetchSalesInfo}
                />
              </Box>
              <Box flex={1}>
                <SalesCard
                  isLoading={isSalesDataFetching}
                  heading={AVERAGE_SALES}
                  value={salesData?.billSalesStats?.avgSales}
                  isSelected={selectedOverviewCategory === AVERAGE_SALES}
                  setSelectedOverviewCategory={() => setSelectedOverviewCategory(AVERAGE_SALES)}
                  expandGraph={expandGraph}
                  hasError={isErrorInSalesInfo}
                  retryFn={refetchSalesInfo}
                />
              </Box>
            </Box>
            <Box flex={4}>
              <TransactionsCard
                isLoading={isTransactionDataFetching}
                isSelected={selectedOverviewCategory === TOTAL_TRANSACTIONS}
                totalTransactions={totalTransactions}
                digitalTransactions={digitalTransactions}
                digitalTransPercent={calculatePercentage(digitalTransactions, totalTransactions)}
                printedTransactions={printedTransactions}
                printedTransPercent={calculatePercentage(printedTransactions, totalTransactions)}
                digitalPrintedTransactions={digitalPrintedTransactions}
                digitalPrintedTransPercent={calculatePercentage(
                  digitalPrintedTransactions,
                  totalTransactions,
                )}
                setSelectedOverviewCategory={setSelectedOverviewCategory}
                expandGraph={expandGraph}
                hasError={isErrorInTransactionsInfo}
                retryFn={refetchTransactionsInfo}
              />
            </Box>
          </Box>
          {isGraphExpanded ? (
            <Box position="relative" height="220px" marginBottom="spacing.5">
              <Graph
                graphData={getGraphData(selectedOverviewCategory)}
                legendLabel={getGraphLabel(selectedOverviewCategory)}
              />
            </Box>
          ) : null}

          <Divider variant="normal" thickness="thinner" marginBottom="spacing.6" />

          <Box display="flex" justifyContent="center">
            <Link
              size="medium"
              icon={isGraphExpanded ? ChevronUpIcon : ChevronDownIcon}
              iconPosition="right"
              onClick={() => graphTogglehandler(isGraphExpanded)}
            >
              View Graphical Data
            </Link>
          </Box>
        </Box>
      </CardBody>
    </Card>
  );
};

export default BillsOverviewContainer;
