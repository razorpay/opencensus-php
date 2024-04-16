import React, { useState } from 'react';
import {
  Box,
  Text,
  TableBody,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableRow,
  TableCell,
} from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';

import Spinner from 'common/ui/Spinner';
import { EmptyListWithTableRow } from 'merchant/components/EmptyList';
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
  const tableData = {
    nodes: resellerAccounts?.items ?? [],
  };
  return (
    <Wrapper>
      <div className="content-wrapper" style={{ marginTop: '-16px' }}>
        <Box backgroundColor="surface.background.gray.intense" paddingBottom="spacing.3">
          <div className="table-responsive">
            <ResellerAccountsFilter onSearch={handleSearch} />

            {isLoading ? (
              <div className="page-spinner-container">
                <Spinner center={undefined} />
              </div>
            ) : Array.isArray(tableData.nodes) && tableData.nodes.length > 0 ? (
              <>
                <Table data={tableData} showStripedRows={true}>
                  {(orderItems) => {
                    return (
                      <>
                        <TableHeader>
                          <TableHeaderRow>
                            {resellerAccountsColumns.map(({ label }) => (
                              <TableHeaderCell key={label}>{label}</TableHeaderCell>
                            ))}
                          </TableHeaderRow>
                        </TableHeader>
                        <TableBody>
                          {orderItems.map((order, index) => (
                            <TableRow key={index} item={order}>
                              {resellerAccountsColumns.map(({ label, value }) => (
                                <TableCell key={label}>{value(order)}</TableCell>
                              ))}
                            </TableRow>
                          ))}
                        </TableBody>
                      </>
                    );
                  }}
                </Table>
                <Box>
                  <Box position="absolute" paddingLeft="spacing.5" paddingTop="spacing.1">
                    <Text size="small" color="surface.text.gray.muted">{`Total ${
                      resellerAccounts?.count || 0
                    } Reseller Accounts`}</Text>
                  </Box>
                  <Pagination
                    next={handleNext}
                    prev={handlePrev}
                    listData={resellerAccounts?.items || []}
                    skip={skip}
                    count={LIST_FETCH_BATCH_SIZE}
                  />
                </Box>
              </>
            ) : (
              <Box display="flex" alignItems="center" justifyContent="center">
                <EmptyListWithTableRow
                  colSpan={8}
                  description={<div>No Reseller Accounts Found!</div>}
                />
              </Box>
            )}
          </div>
        </Box>
      </div>
    </Wrapper>
  );
};

export default connect((state) => ({
  mode: state.session?.mode,
  merchantId: state.session?.user?.current,
}))(ResellerAccounts);
