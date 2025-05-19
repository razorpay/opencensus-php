import React, { useEffect, useState } from 'react';

import { showNotification } from 'merchant_common/reducers/notifications';
import {
  SSO_CUSTOMER_FILTER,
  type SSOCustomer,
} from 'merchant/views/MagicCheckout/SSODashboard/types';

import { fetchCustomerLoginList } from 'merchant/views/MagicCheckout/SSODashboard/api';

import { Box, Heading, Text } from '@razorpay/blade/components';
import ExportWidget from 'merchant/views/MagicCheckout/SSODashboard/components/ExportWidget';
import CustomerFilters from 'merchant/views/MagicCheckout/SSODashboard/components/CustomerData/Filters';
import CustomerTable from 'merchant/views/MagicCheckout/SSODashboard/components/CustomerData/CustomerTable';

interface CustomerDataProps {
  timeRange: {
    start: moment.Moment;
    end: moment.Moment;
  };
}

const CustomerData = ({ timeRange }: CustomerDataProps) => {
  const [selectedFilter, setSelectedFilter] = useState(SSO_CUSTOMER_FILTER.ALL);
  const [customerData, setCustomerData] = useState<SSOCustomer[]>([]);
  const [totalCount, setTotalCount] = useState(0);
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(0);
  const [isLoading, setIsLoading] = useState(true);

  const onFilterChange = (filter: SSO_CUSTOMER_FILTER) => {
    getCustomerListData(0, filter, searchTerm);
    setCurrentPage(0);
    setSelectedFilter(filter);
  };

  const onSearchClick = () => {
    getCustomerListData(0, selectedFilter, searchTerm);
    setCurrentPage(0);
  };

  const getCustomerListData = async (
    page = currentPage,
    type = selectedFilter,
    search = searchTerm,
  ) => {
    try {
      setIsLoading(true);
      const response = await fetchCustomerLoginList({
        from: timeRange.start.valueOf(),
        to: timeRange.end.valueOf(),
        type,
        skip: page * 10,
        search,
      });
      setCustomerData(response?.data?.customers_latest_login || []);
      setTotalCount(response?.data?.pagination.total || 0);
    } catch (error: any) {
      showNotification({
        type: 'error',
        message: error?.errors?.[0] || 'Something went wrong',
      });
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    console.log('ye cll ho raha hai');
    getCustomerListData(0, selectedFilter, searchTerm);
    setCurrentPage(0);
  }, [timeRange.start, timeRange.end]);
  return (
    <Box>
      <Box display="flex" alignItems="center" marginBottom="spacing.6">
        <Box display="flex" flexDirection="column" flex="1">
          <Heading size="large" weight="semibold">
            Customer Login Data
          </Heading>
          <Text variant="body" size="medium">
            Look at all the users who have logged in through Login with Razorpay
          </Text>
        </Box>
        <ExportWidget timeRange={timeRange} />
      </Box>
      <CustomerFilters
        selectedFilter={selectedFilter}
        isFetching={isLoading}
        setSearch={setSearchTerm}
        onSearchClick={onSearchClick}
        onFilterChange={onFilterChange}
      />
      <CustomerTable
        isRefreshing={isLoading}
        timeRange={timeRange}
        data={customerData}
        currentPage={currentPage}
        totalCount={totalCount}
        getCustomerListData={getCustomerListData}
        setCurrentPage={setCurrentPage}
      />
    </Box>
  );
};

export default CustomerData;
