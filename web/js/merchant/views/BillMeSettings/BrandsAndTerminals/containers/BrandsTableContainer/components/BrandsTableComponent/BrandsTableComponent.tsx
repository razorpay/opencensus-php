import React from 'react';
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
  TableToolbar,
  Text,
  TableToolbarActions,
  Button,
  PlusIcon,
  InfoIcon,
} from '@razorpay/blade/components';

import BrandNameCell from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/components/BrandsTableComponent/Cells/BrandNameCell';

import type { Brand } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/types';
import type { PaginationLimitType } from 'merchant/views/BillMeSettings/common/types';

type BrandsTableComponentProps = {
  tableProps: {
    isRefreshing: boolean;
    defaultPageSize: PaginationLimitType;
    changePage: (offset: number) => void;
    changePageSize: (limit: PaginationLimitType) => void;
    totalItemCount: number;
    brandsData: Brand[];
    currentPage: number;
  };
  onBrandNameClick: (brandId: string) => void;
  onAddNewBrandClick: () => void;
};

const BrandsTableComponent = ({
  tableProps: {
    isRefreshing,
    defaultPageSize = 10,
    changePage,
    totalItemCount,
    brandsData,
    changePageSize,
    currentPage,
  },
  onBrandNameClick,
  onAddNewBrandClick,
}: BrandsTableComponentProps): React.ReactElement => {
  return (
    <Box backgroundColor="surface.background.gray.intense" minHeight="500px" overflow="auto">
      <Table
        isRefreshing={isRefreshing}
        data={{
          nodes: brandsData,
        }}
        data-analytics-name="store-brands-table"
        pagination={
          brandsData.length > 0 ? (
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
          <TableToolbar>
            <TableToolbarActions>
              <Box width="138px">
                <Button
                  size="medium"
                  icon={PlusIcon}
                  iconPosition="left"
                  onClick={onAddNewBrandClick}
                  data-analytics-name="add-new-brand"
                >
                  New Brand
                </Button>
              </Box>
            </TableToolbarActions>
          </TableToolbar>
        }
      >
        {(tableData) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                <TableHeaderCell>
                  <Box whiteSpace="normal">Name</Box>
                </TableHeaderCell>
                <TableHeaderCell>
                  <Box whiteSpace="normal">Description</Box>
                </TableHeaderCell>
              </TableHeaderRow>
            </TableHeader>
            <TableBody>
              {tableData.map((tableItem) => (
                <TableRow key={tableItem?.id} item={tableItem}>
                  <TableCell>
                    <Box whiteSpace="normal">
                      <BrandNameCell tableItem={tableItem} onBrandNameClick={onBrandNameClick} />
                    </Box>
                  </TableCell>
                  <TableCell>
                    <Box whiteSpace="normal">
                      <Text wordBreak="break-all">{tableItem?.description || '-'}</Text>
                    </Box>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </>
        )}
      </Table>
      {brandsData.length === 0 && !isRefreshing ? (
        // Grid column end value is given in accordance to number of table columns + 1
        <Box gridColumn={`1/3`} padding="spacing.6">
          <Box gap="spacing.2" display="flex" justifyContent="center" alignItems="center">
            <InfoIcon size="xlarge" />{' '}
            <Text weight="semibold" variant="body">
              No Brands found. Click on Add New Brand to create one.
            </Text>
          </Box>
        </Box>
      ) : null}
    </Box>
  );
};

export default BrandsTableComponent;
