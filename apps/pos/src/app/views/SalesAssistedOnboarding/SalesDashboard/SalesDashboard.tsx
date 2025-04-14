import React, { lazy, Suspense, useEffect, useState, useCallback } from 'react';
import {
  Badge,
  Box,
  Button,
  DatePicker,
  Heading,
  Link,
  PlusIcon,
  Text,
  useToast,
  SearchIcon,
  SearchInput,
} from '@razorpay/blade/components';

import { useInfiniteQuery, useQueryClient } from '@tanstack/react-query';
import { graphqlRequest } from '@federated/apps/shell/graphql';
import moment from 'moment';
import { useNavigate } from 'react-router-dom';

import SalesTable from './SalesTable';
import { SALES_ONBOARDED_MERCHANTS } from 'apps/pos/src/services/queries/SalesDashboard';
import Search from './components/Search';
import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';
import {
  SalesOnboardedMerchants,
  TableFilter,
} from 'apps/pos/src/app/types/SalesAssistedOnboarding';
import { GraphQLErrorResponseType } from 'apps/pos/src/app/types/common';
import useOnboardingStore from 'apps/pos/src/bootstrap/Store/index';
import {
  ASSISTED_ONBOARDING,
  PARTNER_ASSISTED_ONBOARDING,
} from 'apps/pos/src/app/constants/SalesAssistedOnboarding';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';
import StatusFilter from 'apps/pos/src/app/views/SalesAssistedOnboarding/SalesDashboard/StatusFilter';

interface Range {
  startDate: number;
  endDate: number;
}
const DEFAULT_RANGE: Range = {
  startDate: moment().subtract(10, 'days').startOf('day').unix(),
  endDate: moment().endOf('day').unix(),
};
const DEFAULT_FILTERS: TableFilter = {
  activationStatus: 'all',
  dateRange: DEFAULT_RANGE,
};

const PAGE_SIZE = 10;
const QUERY_KEY = 'salesTable';
const DATE_PRESETS = [
  {
    label: 'Past 3 days',
    value: () => [
      moment().subtract(3, 'days').startOf('day').toDate(), // this includes current date also, so end result will include 4 days data
      moment().endOf('day').toDate(),
    ],
  },
  {
    label: 'Past 7 days',
    value: () => [
      moment().subtract(7, 'days').startOf('day').toDate(),
      moment().endOf('day').toDate(),
    ],
  },
  {
    label: 'Past 30 days',
    value: () => [
      moment().subtract(30, 'days').startOf('day').toDate(),
      moment().endOf('day').toDate(),
    ],
  },
  {
    label: 'Past 90 days',
    value: () => [
      moment().subtract(90, 'days').startOf('day').toDate(),
      moment().endOf('day').toDate(),
    ],
  },
];

const SalesDashboard = (): JSX.Element => {
  const { isMobile } = useScreen();
  const [page, setPage] = useState(0);
  const [isSearchOpen, setIsSearchOpen] = useState(false);
  // eslint-disable-next-line @typescript-eslint/unbound-method
  const queryCache = useQueryClient();
  const toast = useToast();
  const navigate = useNavigate();
  const { isPosEkycAgent, filters, setFilters } = useOnboardingStore();
  const [dateRangeFilter, setDateRangeFilter] = useState<{
    startDate: number | null;
    endDate: number | null;
  }>(filters.dateRange);
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
      autoDismiss: true,
    });
  };

  const { data, fetchNextPage, isLoading, isFetching } = useInfiniteQuery<
    SalesOnboardedMerchants | null,
    GraphQLErrorResponseType
  >({
    queryKey: [
      QUERY_KEY,
      isPosEkycAgent,
      filters.dateRange.startDate,
      filters.dateRange.endDate,
      filters.activationStatus,
    ],
    queryFn: async ({ pageParam = 0, queryKey }) => {
      const { salesOnboardedMerchants: response } = await graphqlRequest<
        'salesOnboardedMerchants',
        SalesOnboardedMerchants,
        GraphQLErrorResponseType
      >({
        document: SALES_ONBOARDED_MERCHANTS,
        variables: {
          limit: PAGE_SIZE,
          offset: pageParam * PAGE_SIZE,
          startDate: queryKey[2],
          endDate: queryKey[3],
          status: queryKey[4],
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
  const { totalMerchantsOnboarded, statusCounts, total } = pages[pages.length - 1] ?? {};

  const handleOnApplyFilter = (filters?: TableFilter) => {
    if (!filters) setFilters(DEFAULT_FILTERS);
    else
      setFilters({
        ...filters,
        dateRange: {
          startDate: filters.dateRange.startDate,
          endDate: filters.dateRange.endDate,
        },
      });
    void queryCache.removeQueries({ queryKey: [QUERY_KEY, isPosEkycAgent] });
    setPage(0);
    void fetchNextPage({
      pageParam: 0,
    });
  };

  const resetAllFilters = (): void => {
    setFilters({ ...filters, dateRange: DEFAULT_RANGE, activationStatus: 'all' });
    setDateRangeFilter(DEFAULT_RANGE);
    handleOnApplyFilter();
  };

  const handlePageChange = async ({ page: nextPage }: { page: number }) => {
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

  const onDateApplyHandler = (date) => {
    const startDate = moment(date[0]).unix();
    const endDate = moment(date[1]).unix();
    if (startDate && endDate) {
      handleOnApplyFilter({
        dateRange: {
          startDate: startDate,
          endDate: endDate,
        },
        activationStatus: filters.activationStatus,
      });
      setDateRangeFilter({
        startDate: startDate,
        endDate: endDate,
      });
    } else {
      setDateRangeFilter(DEFAULT_RANGE);
      handleOnApplyFilter({
        dateRange: DEFAULT_RANGE,
        activationStatus: filters.activationStatus,
      });
    }
  };

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

  useEffect(() => {
    const user = window.rzp_user?.user;
    if (isPosEkycAgent && user && !user.name) {
      navigate('/pos-sales/basic-info');
    }
  }, [isPosEkycAgent]);

  const handleSearchButtonClick = useCallback(() => {
    setIsSearchOpen(true);
  }, []);

  useEffect(() => {
    window.addEventListener('toggle-search', handleSearchButtonClick);
    return () => window.removeEventListener('toggle-search', handleSearchButtonClick);
  }, [handleSearchButtonClick]);

  return (
    <Box display="flex" flexDirection="column" width="100%">
      <Box margin="spacing.5" marginTop={'spacing.2'}>
        <Box
          display="flex"
          flexDirection="row"
          justifyContent="space-between"
          width="100%"
          marginBottom="spacing.5"
          alignItems="center"
        >
          {!isMobile && (
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
          )}
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
          <Box>
            <Box display="flex" alignItems="center">
              <Box maxWidth={{ m: '500px' }} marginBottom="spacing.7">
                <DatePicker
                  // @ts-ignore
                  label={{
                    end: 'End Date',
                    start: 'Start Date',
                  }}
                  allowSingleDateInRange
                  value={[
                    dateRangeFilter.startDate
                      ? new Date(
                          moment(dateRangeFilter.startDate * 1000).format(
                            'ddd MMM DD YYYY HH:mm:ss [GMT]ZZ (z)',
                          ),
                        )
                      : null,
                    dateRangeFilter.endDate
                      ? new Date(
                          moment(dateRangeFilter.endDate * 1000).format(
                            'ddd MMM DD YYYY HH:mm:ss [GMT]ZZ (z)',
                          ),
                        )
                      : null,
                  ]}
                  onChange={(date: Array<Date | null>) => {
                    setDateRangeFilter({
                      startDate: date[0] ? moment(date[0]).unix() : null,
                      endDate: date[1] ? moment(date[1]).endOf('day').unix() : null,
                    });
                  }}
                  defaultValue={[
                    moment(filters.dateRange.startDate * 1000).format(
                      'ddd MMM DD YYYY HH:mm:ss [GMT]ZZ (z)',
                    ),
                    moment(filters.dateRange.endDate * 1000).format(
                      'ddd MMM DD YYYY HH:mm:ss [GMT]ZZ (z)',
                    ),
                  ]}
                  onApply={(date) => onDateApplyHandler(date)}
                  // @ts-ignore
                  selectionType="range"
                  presets={DATE_PRESETS}
                />
              </Box>

              {!isMobile ? (
                <SearchInput
                  placeholder="Search in payments"
                  name="search"
                  size="medium"
                  label=""
                  onClick={handleSearchButtonClick}
                  marginLeft={'spacing.4'}
                />
              ) : null}
            </Box>
            <StatusFilter
              defaultValue={filters.activationStatus}
              value={filters.activationStatus}
              statusCounts={statusCounts}
            />
            <Box>
              <Link onClick={resetAllFilters} variant="button">
                Clear Filters
              </Link>
            </Box>
          </Box>
        </Suspense>
      </Box>
      <Box
        padding="spacing.5"
        backgroundColor="surface.background.gray.intense"
        minHeight="500px"
        marginX={{ base: 'spacing.0', l: 'spacing.5' }}
        marginBottom={{ base: 'spacing.0', l: 'spacing.5' }}
      >
        <Box testID="sales-table">
          <SalesTable
            totalMerchants={total}
            pages={pages as SalesOnboardedMerchants[]}
            page={page}
            size={PAGE_SIZE}
            handlePageChange={handlePageChange}
            isLoading={isLoading}
            isFetching={isFetching}
          />
        </Box>
      </Box>
      <Search
        isSearchOpen={isSearchOpen}
        setIsSearchOpen={setIsSearchOpen}
        dateRange={filters.dateRange}
      />
    </Box>
  );
};

export default SalesDashboard;
