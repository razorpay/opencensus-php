import React, { useEffect, useState } from 'react';
import { Box, Text, Heading, Divider, LoaderIcon } from '@razorpay/blade/components';

import { fetchMerchantsKYC } from '../../utils/apis';
import useAPI from '../../hooks/useAPI';
import { All, FilterStateType, SearchBy, TableItem } from '../../types';
import DashboardTable from '../../components/DashboardTable';
import Filter from '../../components/Filter';
import { getAllMerchantsStatus } from '../../utils/helpers';
import DatePicker from '../../components/DatePicker';
import AddMerchant from '../SalesAssistedOnboarding/SalesDashboard/AddMerchant';

const POSDashboard: React.FC = () => {
  const [currentDate, setCurrentDate] = useState<Date>(new Date());
  const [isLoading, merchantsKyc, setMerchantsKyc] = useAPI<TableItem[], FilterStateType>(
    [],
    fetchMerchantsKYC,
  );

  const onFilterSearchClick = (filterState: FilterStateType): void => {
    setMerchantsKyc(filterState);
  };

  useEffect(() => {
    const initialFilterState: FilterStateType = {
      businessModel: All.ALL,
      pricing: All.ALL,
      searchBy: SearchBy.MID,
      searchField: '',
      status: All.ALL,
    };
    setMerchantsKyc(initialFilterState);
  }, []);

  return (
    <Box display="flex" flexDirection="column" width="100%" padding="spacing.5">
      <Box display="flex" flexDirection="row" justifyContent="space-between" width="100%">
        <Heading color="surface.text.gray.subtle" size="large">
          Dashboard
        </Heading>
        <Box>
          <DatePicker currentDate={currentDate} setCurrentDate={setCurrentDate} />
          <AddMerchant />
        </Box>
      </Box>
      <Box
        display="flex"
        flexDirection="column"
        width="100%"
        marginTop="spacing.10"
        flexWrap="wrap"
      >
        <Box display="flex" flexDirection="row" width="100%" justifyContent="space-between">
          <Heading color="surface.text.gray.subtle" size="small">
            All merchants
          </Heading>
        </Box>
        <Box
          display="flex"
          flexDirection="row"
          width="100%"
          justifyContent="space-between"
          marginTop="spacing.5"
          flexWrap="wrap"
        >
          {getAllMerchantsStatus(currentDate).map((statusObj) => (
            <Box
              key={statusObj.status}
              display="flex"
              flexDirection="column"
              justifyContent="space-between"
              padding="spacing.5"
              backgroundColor="surface.background.gray.intense"
              height="92px"
              width="15%"
              minWidth="170px"
              borderColor="surface.border.gray.muted"
              borderWidth="thin"
              marginBottom="spacing.5"
            >
              <Text color="surface.text.gray.subtle">{statusObj.status.split('_').join(' ')}</Text>
              <Heading size="small" color="surface.text.gray.subtle">
                {statusObj.count}
              </Heading>
            </Box>
          ))}
        </Box>
      </Box>
      <Box
        display="flex"
        flexDirection="column"
        justifyContent="space-between"
        backgroundColor="surface.background.gray.intense"
        borderColor="surface.border.gray.subtle"
        borderWidth="thin"
        width="100%"
        padding="spacing.5"
      >
        <Heading size="small" color="surface.text.gray.subtle">
          Merchants
        </Heading>
        <Divider marginTop="spacing.3" marginBottom="spacing.3" />
        <Filter onSearchClick={onFilterSearchClick} />
      </Box>
      {merchantsKyc.length ? (
        // eslint-disable-next-line @typescript-eslint/no-unnecessary-condition
        isLoading ? (
          <LoaderIcon alignSelf="center" size="2xlarge" marginTop="spacing.11" />
        ) : (
          <DashboardTable merchantsKYC={merchantsKyc} />
        )
      ) : null}
    </Box>
  );
};

export default POSDashboard;
