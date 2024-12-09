import React, { lazy, Suspense, useEffect, useState } from 'react';
import {
  Badge,
  Box,
  Button,
  CloseIcon,
  Heading,
  PlusIcon,
  Text,
  useToast,
} from '@razorpay/blade/components';

import { useInfiniteQuery, useQueryClient } from '@tanstack/react-query';
import { graphqlRequest } from '@dashboard/shared-utils/graphql/graphql';
import moment from 'moment';
import { useNavigate } from 'react-router-dom';

import SalesTable from './SalesTable';
import StatusFilter from './StatusFilter';
import SalesDashboardStatusCounts from './SalesDashboardStatusCounts';
import { SALES_ONBOARDED_MERCHANTS } from 'apps/pos/src/services/queries/SalesDashboard';
import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';
import {
  SalesOnboardedMerchants,
  STATUS_FILTERS,
  StatusCounts,
} from 'apps/pos/src/app/types/SalesAssistedOnboarding';
import { GraphQLErrorResponseType } from 'apps/pos/src/app/types/common';
import useOnboardingStore from 'apps/pos/src/bootstrap/Store/index';
import {
  ASSISTED_ONBOARDING,
  PARTNER_ASSISTED_ONBOARDING,
} from 'apps/pos/src/app/constants/SalesAssistedOnboarding';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

interface Filters {
  status: STATUS_FILTERS;
}

interface Range {
  startDate: number;
  endDate: number;
}

interface DatePickerRange {
  from: number;
  to: number;
}

const DEFAULT_RANGE: Range = {
  startDate: moment().subtract(10, 'days').startOf('day').unix(),
  endDate: moment().endOf('day').unix(),
};

const DEFAULT_FILTERS: Filters = {
  status: 'all',
};

const PAGE_SIZE = 10;
const QUERY_KEY = 'salesTable';

const DateRangePicker = lazy(
  () =>
    import(
      /* webpackChunkName: 'DateRangePicker' */ '@dashboard/shared-ui/components/Forms/DateRangePickerField'
    ),
);

const SalesDashboard = (): JSX.Element => {
  const { isMobile } = useScreen();
  const [range, setRange] = useState<Range>(DEFAULT_RANGE);
  const [filters, setFilter] = useState<Filters>({ ...DEFAULT_FILTERS });
  const [page, setPage] = useState(0);
  // eslint-disable-next-line @typescript-eslint/unbound-method
  const queryCache = useQueryClient();
  const toast = useToast();
  const navigate = useNavigate();
  const { isPosEkycAgent } = useOnboardingStore();

  const handleError = (response: GraphQLErrorResponseType): void => {
    toast.show({
      color: 'negative',
      content: (
        <Box display="grid">
          <Heading color="surface.text.staticWhite.normal">Failed to fetch merchants</Heading>
          <Text truncateAfterLines={2} color="surface.text.staticWhite.normal">
            {response?.message || 'Something went wrong. Please try again.'}
          </Text>
        </Box>
      ),
      autoDismiss: false,
    });
  };

  const { data, fetchNextPage, isLoading, isFetching } = useInfiniteQuery<
    SalesOnboardedMerchants | null,
    GraphQLErrorResponseType
  >({
    queryKey: [QUERY_KEY, isPosEkycAgent],
    queryFn: async ({ pageParam = 0 }) => {
      const { salesOnboardedMerchants: response } = await graphqlRequest<
        'salesOnboardedMerchants',
        SalesOnboardedMerchants,
        GraphQLErrorResponseType
      >({
        document: SALES_ONBOARDED_MERCHANTS,
        variables: {
          limit: PAGE_SIZE,
          offset: pageParam * PAGE_SIZE,
          startDate: range?.startDate,
          endDate: range?.endDate,
          status: filters.status,
          signupCampaign: isPosEkycAgent ? PARTNER_ASSISTED_ONBOARDING : ASSISTED_ONBOARDING,
        },
      });

      if (response?.__typename === 'SalesOnboardedMerchants')
        return response as SalesOnboardedMerchants;
      else if (response?.__typename === 'SalesOnboardedMerchantsError') {
        handleError(response as GraphQLErrorResponseType);
      }

      return null;
    },
    getNextPageParam: (lastPage) => {
      if (!lastPage?.hasMore) return undefined;
      return page + 1;
    },
    staleTime: 60000 * 1,
    retry: false,
    networkMode: 'always',
    refetchOnWindowFocus: false,
    refetchOnMount: false,
    onError: (error) => {
      handleError(error);
    },
  });

  const pages = (data?.pages ?? []).map((item) => item).filter((item) => item !== null);
  const { totalMerchantsOnboarded, statusCounts } = pages[pages.length - 1] ?? {};

  const handleOnApplyFilter = () => {
    void queryCache.removeQueries({ queryKey: [QUERY_KEY, isPosEkycAgent] });
    setPage(0);
    void fetchNextPage({ pageParam: 0 });
  };

  const resetAllFilters = (): void => {
    setFilter({ ...DEFAULT_FILTERS });
    handleOnApplyFilter();
  };

  const handleOnDateChange = (dates: DatePickerRange): void => {
    const { from, to } = dates;
    setRange({
      startDate: moment.unix(from).startOf('day').unix(),
      endDate: moment.unix(to).endOf('day').unix(),
    });
  };

  const handleStatusFilterChange = (status: STATUS_FILTERS): void => {
    setFilter({ ...filters, status });
  };

  const handlePageChange = async ({ page: nextPage }) => {
    if (page !== nextPage) {
      setPage(nextPage as number);
      if (!pages?.[nextPage]) void fetchNextPage({ pageParam: nextPage }); //would fetch the already fetched from cache
    }
  };

  const handleOnAddMerchantClick = (): void => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Add New Merchant',
        section: 'Merchant Details',
        subSection: 'Agent Dashboard Homescreen',
        pageType: analyticsTypes.PAGE_TYPES.AGENT_DASHBOARD,
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.MERCHANT_DETAILS,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.AGENT_DASHBOARD_HOMESCREEN,
      },
    });

    navigate('onboarding/new');
  };

  useEffect(() => {
    handleOnApplyFilter();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [filters]);

  useEffect(() => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.PAGE,
      action: analyticsTypes.ANALYTICS_ACTIONS.VIEWED,
      properties: {
        pageType: analyticsTypes.PAGE_TYPES.AGENT_DASHBOARD,
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.MERCHANT_DETAILS,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.PAGE_VIEW,
      },
    });
  });

  return (
    <Box display="flex" flexDirection="column" width="100%">
      <Box margin="spacing.5">
        <Box
          display="flex"
          flexDirection="row"
          justifyContent="space-between"
          width="100%"
          marginBottom="spacing.5"
          alignItems="center"
        >
          <Box display="flex" alignItems="center">
            <Heading color="surface.text.gray.normal" size="xlarge" marginRight="spacing.3">
              Merchant Details
            </Heading>
            {totalMerchantsOnboarded ? (
              <Badge size="medium" color="neutral">
                {String(totalMerchantsOnboarded)}
              </Badge>
            ) : null}
          </Box>
          <Box
            display="flex"
            justifyContent="center"
            position={{ base: 'fixed', l: 'relative' }}
            bottom="0px"
            padding="spacing.4"
            backgroundColor={{
              base: 'surface.background.gray.intense',
              l: 'transparent',
            }}
            left="0px"
            right="0px"
            zIndex="1"
          >
            <Button
              icon={PlusIcon}
              onClick={handleOnAddMerchantClick}
              size={isMobile ? 'medium' : 'large'}
              isFullWidth
            >
              Add Merchant
            </Button>
          </Box>
        </Box>
        <Suspense fallback={null}>
          <Box
            display="flex"
            alignItems="flex-start"
            flexWrap="wrap"
            marginBottom={{ base: 'spacing.4', l: 'spacing.0' }}
          >
            <Box
              display="flex"
              alignItems="center"
              marginRight="spacing.3"
              marginBottom="spacing.4"
            >
              <DateRangePicker
                onDatesChange={(dates: DatePickerRange) => handleOnDateChange(dates)}
                startDate={moment.unix(range.startDate)}
                endDate={moment.unix(range.endDate)}
                numberOfMonths={isMobile ? 1 : 2}
              />
            </Box>
            <Box display="flex">
              <Button marginRight="spacing.4" isLoading={isLoading} onClick={handleOnApplyFilter}>
                Apply
              </Button>
              <Button
                variant="tertiary"
                icon={CloseIcon}
                onClick={resetAllFilters}
                display={{ base: 'none', l: 'block' }}
              >
                Clear all filters
              </Button>
            </Box>
          </Box>
        </Suspense>
        {filters.status === 'all' ? (
          <SalesDashboardStatusCounts statusCounts={statusCounts as StatusCounts} />
        ) : null}
      </Box>
      <Box
        padding="spacing.5"
        backgroundColor="surface.background.gray.intense"
        minHeight="500px"
        marginX={{ base: 'spacing.0', l: 'spacing.5' }}
        marginBottom={{ base: 'spacing.0', l: 'spacing.5' }}
      >
        <Box display={{ base: 'block', l: 'flex' }} marginBottom="spacing.5" alignItems="flex-end">
          <Box
            width={{ base: '100%', l: '200px' }}
            marginBottom={{ base: 'spacing.5', l: 'spacing.0' }}
            marginRight={{ base: 'spacing.0', l: 'spacing.5' }}
          >
            <StatusFilter
              defaultValue={DEFAULT_FILTERS.status}
              value={filters.status}
              onChange={handleStatusFilterChange}
            />
          </Box>
        </Box>
        <Box testID="sales-table">
          <SalesTable
            pages={pages as SalesOnboardedMerchants[]}
            page={page}
            size={PAGE_SIZE}
            handlePageChange={handlePageChange}
            isLoading={isLoading}
            isFetching={isFetching}
          />
        </Box>
      </Box>
    </Box>
  );
};

export default SalesDashboard;
