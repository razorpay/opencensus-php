import React, { useState, useEffect } from 'react';
import type {
  SSOLoginDetails,
  TableCellProps,
} from 'merchant/views/MagicCheckout/SSODashboard/types';
import {
  Box,
  Link,
  Modal,
  ModalBody,
  ModalHeader,
  Tabs,
  TabList,
  TabItem,
  TabPanel,
} from '@razorpay/blade/components';

import { showNotification } from 'merchant_common/reducers/notifications';
import CustomerInfoCard from 'merchant/views/MagicCheckout/SSODashboard/components/CustomerData/CustomerDetails/InfoCard';
import CustomerLoginActivityCard from 'merchant/views/MagicCheckout/SSODashboard/components/CustomerData/CustomerDetails/LoginActivityCard';
import CustomerMarketInfoCard from 'merchant/views/MagicCheckout/SSODashboard/components/CustomerData/CustomerDetails/MarketingInfoCard';
import CustomerLoginTable from 'merchant/views/MagicCheckout/SSODashboard/components/CustomerData/CustomerLoginsTable';

import { fetchCustomerDetails } from 'merchant/views/MagicCheckout/SSODashboard/api';

const CustomerDetails = ({ value, customer, timeRange }: TableCellProps) => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [lastLoginData, setLastLoginData] = useState<SSOLoginDetails[]>([]);

  const getCustomerData = async () => {
    try {
      setIsLoading(true);
      const {
        data: { customer_all_logins },
      } = await fetchCustomerDetails({
        customer_id: customer?.customer_id,
        from: timeRange.start.valueOf(),
        to: timeRange.end.valueOf(),
      });

      setLastLoginData(customer_all_logins);
    } catch (error: any) {
      showNotification({
        type: 'error',
        message: error?.errors?.[0] || 'Something went wrong',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const onModalOpen = () => {
    setIsModalOpen(true);
  };

  useEffect(() => {
    if (isModalOpen) {
      getCustomerData();
    }
  }, [isModalOpen]);

  return (
    <Box display="flex">
      <Link variant="button" onClick={onModalOpen}>
        {value}
      </Link>
      {isModalOpen && (
        <Modal isOpen={isModalOpen} onDismiss={() => setIsModalOpen(false)} size="large">
          <ModalHeader
            title="Contact Details"
            subtitle={`Detailed information about ${customer?.name || '-'}`}
          />
          <ModalBody>
            <Box display="flex" flexDirection="column">
              <Box display="flex" gap="spacing.7" justifyContent="space-between">
                <CustomerInfoCard customer={customer} />
                <CustomerLoginActivityCard customer={customer} />
                <CustomerMarketInfoCard customer={customer} />
              </Box>
              <Tabs orientation="horizontal" size="medium" variant="bordered">
                <TabList>
                  <TabItem value="login_activity">Login Activity</TabItem>
                </TabList>
                <TabPanel value="login_activity">
                  <CustomerLoginTable isFetching={isLoading} data={lastLoginData} />
                </TabPanel>
              </Tabs>
            </Box>
          </ModalBody>
        </Modal>
      )}
    </Box>
  );
};

export default CustomerDetails;
