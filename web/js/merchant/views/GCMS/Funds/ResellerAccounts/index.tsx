import React, { useState } from 'react';
import { Box } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import styled from 'styled-components';

import DataTable from 'common/ui/Table/DataTable';
import { fetchResellersBalance } from 'merchant/views/GCMS/Funds/queries';
import { ListApiResponse, ResellersBalance } from 'merchant/views/GCMS/Funds/types';
import Wrapper from 'merchant/views/GCMS/shared/Wrapper';

import ResellerAccountsFilter from './ResellerAccountsFilter';
import { RESELLER_NAME, RESELLER_ID, TOTAL_AVAILABLE_FUND } from './constants';

import type { ModeT } from 'common/services/mode';

interface ResellerAccountsProps {
  mode: ModeT;
  merchantId: string;
}

const DataTableWrapper = styled.div`
  .table-responsive > table > tbody > tr > td {
    vertical-align: middle;
  }
`;

const ResellerAccounts = ({ mode, merchantId }: ResellerAccountsProps) => {
  const [paginationState, setPagination] = useState({
    skip: 0,
    count: 25,
  });
  const [merchantNameFilter, setMerchantNameFilter] = useState('');

  const {
    isLoading,
    data: resellerAccounts,
    error,
  } = useQuery<ListApiResponse<ResellersBalance>, Error>({
    queryKey: ['gcms:funds:resellers', mode, paginationState, merchantNameFilter],
    queryFn: () =>
      fetchResellersBalance({
        ...paginationState,
        mode,
        merchantId,
        merchant_name: merchantNameFilter,
      }),
  });

  const handleSearch = ({ merchantName }) => {
    setMerchantNameFilter(merchantName);
  };

  return (
    <Wrapper>
      <div className="content-wrapper">
        <ResellerAccountsFilter onSearch={handleSearch} />
        <Box backgroundColor="surface.background.level2.lowContrast" paddingBottom="spacing.3">
          <DataTableWrapper>
            <DataTable
              title="Reseller Accounts"
              columns={[RESELLER_NAME, RESELLER_ID, TOTAL_AVAILABLE_FUND]}
              count={paginationState.count}
              skip={paginationState.skip}
              paginate={setPagination}
              error={error?.message}
              items={resellerAccounts?.items ?? []}
              loading={isLoading}
              hasMoreData={true}
              noStripe={true}
            />
          </DataTableWrapper>
        </Box>
      </div>
    </Wrapper>
  );
};

export default connect((state) => ({
  mode: state.session?.mode,
  merchantId: state.session?.user?.current,
}))(ResellerAccounts);
