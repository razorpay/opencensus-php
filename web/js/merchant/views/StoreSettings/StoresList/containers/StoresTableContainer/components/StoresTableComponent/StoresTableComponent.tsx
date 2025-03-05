import React from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Box,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
  TablePagination,
  TableToolbarActions,
  TableToolbar,
  Button,
  PlusIcon,
  Text,
  Badge,
  Link,
  InfoIcon,
} from '@razorpay/blade/components';

import { STORE_TYPE_MAP } from 'merchant/views/BillMeSettings/common/constants';
import LinkedProductsCell from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/components/StoresTableComponent/Cells/LinkedProductsCell';

import type { PaginationLimitType } from 'merchant/views/BillMeSettings/common/types';
import type { Store } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/types';

type StoresTableComponentProps = {
  tableProps: {
    isRefreshing: boolean;
    defaultPageSize: PaginationLimitType;
    changePage: (offset: number) => void;
    changePageSize: (limit: PaginationLimitType) => void;
    totalItemCount: number;
    storesData: Store[];
    currentPage: number;
  };
  showCreateStoreButton: boolean;
};

const StoresTableComponent = ({
  tableProps: {
    isRefreshing,
    defaultPageSize = 10,
    changePage,
    totalItemCount,
    storesData,
    changePageSize,
    currentPage,
  },
  showCreateStoreButton,
}: StoresTableComponentProps): React.ReactElement => {
  const navigate = useNavigate();
  return (
    <Box
      backgroundColor="surface.background.gray.intense"
      minHeight="400px"
      overflow="auto"
      marginTop="spacing.4"
    >
      <Table
        isRefreshing={isRefreshing}
        data={{ nodes: storesData }}
        pagination={
          storesData.length > 0 ? (
            <TablePagination
              showLabel
              showPageNumberSelector
              showPageSizePicker
              paginationType="server"
              totalItemCount={totalItemCount}
              defaultPageSize={defaultPageSize}
              onPageChange={({ page }) => changePage(page * defaultPageSize)}
              onPageSizeChange={({ pageSize }) => changePageSize(pageSize as PaginationLimitType)}
              currentPage={currentPage}
            />
          ) : (
            <></>
          )
        }
        rowDensity="comfortable"
        toolbar={
          showCreateStoreButton ? (
            <TableToolbar>
              <TableToolbarActions>
                <Box width="135px">
                  <Button
                    icon={PlusIcon}
                    onClick={() => {
                      navigate('/store-settings/store-create');
                    }}
                  >
                    New Store
                  </Button>
                </Box>
              </TableToolbarActions>
            </TableToolbar>
          ) : (
            <TableToolbar />
          )
        }
        gridTemplateColumns="40% 20% 15% 25%"
      >
        {(tableData) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                <TableHeaderCell>
                  <Box whiteSpace="normal">
                    <Text weight="medium">Store Name</Text>
                  </Box>
                </TableHeaderCell>
                <TableHeaderCell>
                  <Box whiteSpace="normal">
                    <Text weight="medium">Store Code</Text>
                  </Box>
                </TableHeaderCell>
                <TableHeaderCell>
                  <Box whiteSpace="normal">
                    <Text weight="medium">Store Type</Text>
                  </Box>
                </TableHeaderCell>
                <TableHeaderCell>
                  <Box whiteSpace="normal">
                    <Text weight="medium">Linked Razorpay Products</Text>
                  </Box>
                </TableHeaderCell>
              </TableHeaderRow>
            </TableHeader>
            {storesData.length ? (
              <TableBody>
                {tableData.map((tableItem) => {
                  const { label: storeTypeLabel, color: storeTypeColor } =
                    STORE_TYPE_MAP[tableItem?.storeInfo?.storeType] || {};
                  return (
                    <TableRow key={tableItem?.id} item={tableItem}>
                      <TableCell>
                        <Box whiteSpace="normal">
                          <Link
                            variant="button"
                            onClick={() => navigate(`/store-settings/stores-list/${tableItem?.id}`)}
                          >
                            {tableItem?.name}
                          </Link>
                        </Box>
                      </TableCell>
                      <TableCell>
                        <Box whiteSpace="normal">
                          <Text wordBreak="break-all">{tableItem?.storeInfo?.storeCode}</Text>
                        </Box>
                      </TableCell>
                      <TableCell>
                        <Box whiteSpace="normal">
                          <Badge color={storeTypeColor}>{storeTypeLabel}</Badge>
                        </Box>
                      </TableCell>
                      <TableCell>
                        <Box whiteSpace="normal">
                          <LinkedProductsCell products={tableItem?.storeInfo?.linkedProducts} />
                        </Box>
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            ) : (
              // Grid column end value is given in accordance to number of table columns + 1
              <Box gridColumn={`1/5`} padding="spacing.6">
                <Box gap="spacing.2" display="flex" justifyContent="center" alignItems="center">
                  <InfoIcon size="xlarge" />{' '}
                  <Text weight="semibold" variant="body">
                    No store data present. Click on Add New Store to see your stores data here.
                  </Text>
                </Box>
              </Box>
            )}
          </>
        )}
      </Table>
    </Box>
  );
};

export default StoresTableComponent;
