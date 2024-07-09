import React from 'react';
import {
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
  TablePagination,
  Link,
  ChevronRightIcon,
  Box,
  Text,
  Divider,
  Heading,
} from '@razorpay/blade/components';
import moment from 'moment';
import { SalesOnboardedMerchants } from '@dashboard/shared-utils/graphql/graph-types';
import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';
import StatusBadge from 'apps/pos/src/app/components/StatusBadge/StatusBadge';

interface DashboardTableProps {
  pages: SalesOnboardedMerchants[];
  page: number;
  size: number;
  isLoading: boolean;
  isFetching: boolean;
  handlePageChange: ({ page }: { page: number }) => void;
}

const SalesTable: React.FC<DashboardTableProps> = ({
  pages,
  page,
  size,
  isLoading,
  isFetching,
  handlePageChange,
}) => {
  const { isMobile } = useScreen();
  const merchants = (isFetching && !pages[page] ? [...pages, ...pages.slice(-1)] : pages)
    .map((page) => page.merchants)
    .flat();
  const tableData = merchants.map((merchant) => ({
    id: merchant.merchantId,
    ...merchant,
  }));

  if (page === 0 && tableData.length === 0 && !isLoading && !isFetching)
    return <Text>No data available</Text>;

  return (
    <Table
      data={{ nodes: tableData }}
      isHeaderSticky
      isLoading={isLoading}
      isRefreshing={isFetching}
      pagination={
        <TablePagination
          currentPage={page}
          totalItemCount={pages.slice(-1)?.[0]?.total || 0}
          defaultPageSize={size as 10}
          onPageChange={handlePageChange}
          showPageSizePicker={false}
          showPageNumberSelector={false}
          showLabel
        />
      }
      zIndex={0}
    >
      {(tableData) => (
        <>
          <TableHeader>
            {!isMobile ? (
              <TableHeaderRow>
                <TableHeaderCell>Initiated On</TableHeaderCell>
                <TableHeaderCell>MID</TableHeaderCell>
                <TableHeaderCell>Merchant Name</TableHeaderCell>
                <TableHeaderCell>Mobile Number</TableHeaderCell>
                <TableHeaderCell>Status</TableHeaderCell>
                <TableHeaderCell>{''}</TableHeaderCell>
              </TableHeaderRow>
            ) : (
              <TableHeaderRow>
                <TableHeaderCell>
                  <Heading>Merchant Onboarding Status</Heading>
                </TableHeaderCell>
              </TableHeaderRow>
            )}
          </TableHeader>
          <TableBody>
            {tableData.map((tableItem, index) => (
              <TableRow key={index} item={tableItem}>
                {!isMobile ? (
                  <React.Fragment>
                    <TableCell>
                      <Text>
                        {moment.unix(Number(tableItem.createdAt)).format('DD/MM/YYYY hh:mm A')}
                      </Text>
                    </TableCell>
                    <TableCell>{tableItem.merchantId}</TableCell>
                    <TableCell>
                      <Text>{tableItem.merchantName || 'Unavailable'}</Text>
                    </TableCell>
                    <TableCell>{tableItem.merchantMobile || 'Unavailable'}</TableCell>
                    <TableCell>
                      <Box display="flex" alignItems="center" height="auto">
                        <StatusBadge
                          type={String(tableItem.status).toLocaleLowerCase()}
                          size="large"
                        />
                      </Box>
                    </TableCell>
                    <TableCell>
                      <Link
                        margin="0px"
                        alignSelf="center"
                        iconPosition="right"
                        icon={ChevronRightIcon}
                        variant="anchor"
                        size="medium"
                      >
                        Details
                      </Link>
                    </TableCell>
                  </React.Fragment>
                ) : (
                  <Box>
                    <Box
                      display="flex"
                      justifyContent="space-between"
                      paddingX="spacing.3"
                      paddingY="spacing.5"
                    >
                      <Box>
                        <StatusBadge
                          type={String(tableItem.status).toLocaleLowerCase()}
                          size="medium"
                        />
                        <Text marginY="spacing.3">{tableItem.merchantName || 'Unavailable'}</Text>
                        <Text size="small" marginY="spacing.3">
                          {tableItem.merchantMobile || 'Unavailable'}
                        </Text>
                        <Text size="small">
                          {moment.unix(Number(tableItem.createdAt)).format('DD/MM/YYYY hh:mm A')}
                        </Text>
                      </Box>
                      <Box textAlign="right">
                        <Text marginBottom="spacing.5" size="small">
                          {tableItem.merchantId}
                        </Text>
                        <Link
                          margin="0px"
                          alignSelf="right"
                          iconPosition="right"
                          icon={ChevronRightIcon}
                          variant="anchor"
                          size="medium"
                        >
                          Details
                        </Link>
                      </Box>
                    </Box>
                    <Divider />
                  </Box>
                )}
              </TableRow>
            ))}
          </TableBody>
        </>
      )}
    </Table>
  );
};

export default SalesTable;
