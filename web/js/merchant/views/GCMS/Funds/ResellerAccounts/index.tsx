import React, { useState } from 'react';
import { Box, Text } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';

import TableBody from 'common/ui/TableBody';
import { EmptyListWithTableRow } from 'merchant/components/EmptyList';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import { fetchResellersBalances, LIST_FETCH_BATCH_SIZE } from 'merchant/views/GCMS/Funds/queries';
import { ListApiResponse, ResellersBalance } from 'merchant/views/GCMS/Funds/types';
import Wrapper from 'merchant/views/GCMS/shared/Wrapper';
import Pagination from 'merchant/views/Settlements/v2/components/Pagination';

import ResellerAccountsFilter from './ResellerAccountsFilter';
import { resellerAccountsColumns } from './constants';

import type { ModeT } from 'common/services/mode';

interface ResellerAccountsProps {
  mode: ModeT;
  merchantId: string;
}

const ResellerAccounts = ({ mode, merchantId }: ResellerAccountsProps) => {
  const [skip, setSkip] = useState(0);
  const [merchantNameFilter, setMerchantNameFilter] = useState('');

  const { isLoading, data: resellerAccounts } = useQuery<ListApiResponse<ResellersBalance>, Error>({
    queryKey: ['gcms:funds:resellers', mode, skip, merchantNameFilter],
    queryFn: () =>
      fetchResellersBalances({
        skip,
        mode,
        merchantId,
        merchant_name: merchantNameFilter,
      }),
  });

  const handleNext = () => {
    setSkip(skip + LIST_FETCH_BATCH_SIZE);
  };

  const handlePrev = () => {
    setSkip(skip - LIST_FETCH_BATCH_SIZE);
  };

  const handleSearch = ({ merchantName }) => {
    setMerchantNameFilter(merchantName);
  };

  return (
    <Wrapper>
      <div className="content-wrapper" style={{ marginTop: '-16px' }}>
        <Box backgroundColor="surface.background.level2.lowContrast" paddingBottom="spacing.3">
          <div className="table-responsive">
            <ResellerAccountsFilter onSearch={handleSearch} />

            <table className="table table-hover">
              <thead>
                <tr>
                  {resellerAccountsColumns.map(({ label }) => (
                    <th key={label} style={{ paddingLeft: 16 }}>
                      {label}
                    </th>
                  ))}
                </tr>
              </thead>
              <TableBody
                isLoading={isLoading}
                colSpan={8}
                rows={resellerAccounts?.items || []}
                emptyTableRow={() => (
                  <EmptyListWithTableRow
                    colSpan={8}
                    description={<Text>No Reseller Accounts Found!</Text>}
                  />
                )}
              >
                {resellerAccounts?.items?.map((transaction) => (
                  <EntityItemRow key={transaction.id} id={transaction.id}>
                    {resellerAccountsColumns.map(({ label, value }) => (
                      <td
                        style={{
                          paddingTop: 16,
                          paddingBottom: 16,
                          paddingLeft: 16,
                          paddingRight: 16,
                        }}
                        key={`${transaction.id} + ${label}`}
                      >
                        {value(transaction)}
                      </td>
                    ))}
                  </EntityItemRow>
                ))}
              </TableBody>
            </table>
          </div>
          <Box>
            <Box position="absolute" paddingLeft="spacing.5" paddingTop="spacing.1">
              <Text size="small" color="surface.text.subdued.lowContrast">{`Total ${
                resellerAccounts?.count || 0
              } records`}</Text>
            </Box>
            <Pagination
              next={handleNext}
              prev={handlePrev}
              listData={resellerAccounts?.items || []}
              skip={skip}
              count={LIST_FETCH_BATCH_SIZE}
            />
          </Box>
        </Box>
      </div>
    </Wrapper>
  );
};

export default connect((state) => ({
  mode: state.session?.mode,
  merchantId: state.session?.user?.current,
}))(ResellerAccounts);
