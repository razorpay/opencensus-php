import React, { useState } from 'react';
import {
  Table,
  Heading,
  Box,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TablePagination,
  Text,
  Skeleton,
} from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { Dispatch, bindActionCreators } from 'redux';

import { showNotification } from 'merchant_common/reducers/notifications';

import DataRows from './DataRows';
import SkeletonRows from './SkeletonRows';
import { REWARD_HISTORY_STALE_TIME, TABLE_PAGE_SIZE } from '../constants';
import { fetchRewardsHistory } from '../services';

interface RewardsHistory {
  showNotification: (args: { type: string; message: string[] }) => void;
}

const RewardsHistory = ({ showNotification }: RewardsHistory): JSX.Element => {
  const [page, setPage] = useState(0);

  const { isLoading, data } = useQuery({
    queryKey: ['reward-history', page],
    queryFn: () => fetchRewardsHistory(page),
    onError: (err: { errors: Array<string> }) => {
      showNotification?.({ type: 'error', message: err.errors });
    },
    staleTime: REWARD_HISTORY_STALE_TIME,
    retry: false,
    refetchOnWindowFocus: false,
  });

  const handlePageChange = ({ page }: { page: number }) => setPage(page);

  return (
    <Box
      backgroundColor="surface.background.gray.intense"
      padding="spacing.7"
      borderRadius="large"
      minHeight="500px"
      overflow="auto"
    >
      <Box paddingBottom="spacing.4">
        <Heading>Previous milestones and rewards</Heading>
      </Box>
      <Table
        data={data?.tableData || { nodes: [] }}
        pagination={
          <TablePagination
            paginationType="server"
            showPageNumberSelector
            showPageSizePicker={false}
            defaultPageSize={TABLE_PAGE_SIZE}
            onPageChange={handlePageChange}
            totalItemCount={data?.totalCount || 0}
          />
        }
      >
        {(tableData) => (
          <>
            {isLoading ? (
              <TableHeader>
                <TableHeaderRow>
                  {Array.from({ length: 6 }).map((_, index) => (
                    <TableHeaderCell key={index}>
                      <Skeleton width="100px" height="20px" />
                    </TableHeaderCell>
                  ))}
                </TableHeaderRow>
              </TableHeader>
            ) : (
              <TableHeader>
                <TableHeaderRow>
                  <TableHeaderCell>Cycle Period</TableHeaderCell>
                  <TableHeaderCell>
                    <Box width="100%">
                      <Text textAlign="right">GMV achieved</Text>
                    </Box>
                  </TableHeaderCell>
                  <TableHeaderCell>Reward</TableHeaderCell>
                  <TableHeaderCell>Disbursal date</TableHeaderCell>
                  <TableHeaderCell>Milestone</TableHeaderCell>
                  <TableHeaderCell>Reward Status</TableHeaderCell>
                </TableHeaderRow>
              </TableHeader>
            )}
            <TableBody>
              {isLoading ? (
                <SkeletonRows rows={10} columns={6} />
              ) : (
                <DataRows tableData={tableData} />
              )}
            </TableBody>
          </>
        )}
      </Table>
    </Box>
  );
};

export default connect(null, (dispatch: Dispatch) =>
  bindActionCreators({ showNotification }, dispatch),
)(RewardsHistory);
