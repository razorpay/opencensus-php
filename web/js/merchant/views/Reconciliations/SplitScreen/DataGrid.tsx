import React, { useEffect, useState, useMemo } from 'react';
import {
  Box,
  Text,
  Button,
  ArrowLeftIcon,
  ArrowRightIcon,
  Dropdown,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  Badge,
} from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import {
  RowClickedEvent,
  ColDef,
  ModuleRegistry,
  ClientSideRowModelModule,
  TextFilterModule,
  RowSelectionModule,
  themeQuartz,
  ICellRendererParams,
} from 'ag-grid-community';
import { AgGridReact } from 'ag-grid-react';
import moment from 'moment';
import { useParams } from 'react-router-dom';

import { fetchSplitScreenSourceList } from 'merchant/views/Reconciliations/api';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';

import type { IData, DataGridProps } from 'merchant/views/Reconciliations/SplitScreen/types';

ModuleRegistry.registerModules([ClientSideRowModelModule, TextFilterModule, RowSelectionModule]);

const defaultColDef: ColDef = {
  flex: 1,
  resizable: true,
  sortable: true,
  filter: true,
  minWidth: 150,
  filterParams: {
    buttons: ['reset', 'apply'],
  },
};

const splitScreenTheme = themeQuartz.withParams({
  headerHeight: '36px',
  columnBorder: { style: 'solid' },
  rowHeight: '24px',
});

const ReconStatusRender = (params: ICellRendererParams) => {
  const isReconciled = params.value.toLowerCase() === 'reconciled';

  return (
    <Badge size="small" color={isReconciled ? 'positive' : 'negative'}>
      {params.value}
    </Badge>
  );
};

const ReconRemarksRender = (params: ICellRendererParams) => {
  const isSuccess = params.value.toLowerCase() === 'success';

  return (
    <Badge size="small" color={isSuccess ? 'positive' : 'negative'}>
      {params.value}
    </Badge>
  );
};

const DataGrid: React.FC<DataGridProps> = ({
  reconFilter,
  dateRange,
  sourceId,
  setIsDrawerOpen,
  setClickedRow,
  merchantSources,
  setReconPairSources,
}) => {
  const { runId } = useParams();

  const [columnData, setColumnData] = useState<ColDef<IData>[]>([]);
  const [rowData, setRowData] = useState<IData[]>([]);

  const [currentPage, setCurrentPage] = useState<number>(1);
  const [paginationPageId, setPaginationPageId] = useState<{
    firstId: string | null;
    lastId: string | null;
  }>({
    firstId: null,
    lastId: null,
  });

  const pageId = paginationPageId.lastId
    ? paginationPageId.lastId
    : paginationPageId.firstId
    ? paginationPageId.firstId
    : '';

  const shouldRetriggerPaginatedApi = useMemo(() => {
    return (
      runId &&
      sourceId &&
      Boolean(
        currentPage === 1 ||
          (currentPage > 1 && (paginationPageId.firstId || paginationPageId.lastId)),
      )
    );
  }, [runId, sourceId, paginationPageId, currentPage]);

  const {
    data: splitScreenData,
    isLoading: isLoadingForSplitScreen,
    isSuccess: isSuccessForSplitScreen,
    isError: isErrorForSplitScreen,
  } = useQuery({
    queryKey: [
      'runs',
      runId,
      sourceId,
      reconFilter,
      pageId,
      dateRange.startDate,
      dateRange.endDate,
    ],
    queryFn: () =>
      fetchSplitScreenSourceList({
        runId,
        sourceId,
        first_id: paginationPageId.firstId,
        last_id: paginationPageId.lastId,
        filter: reconFilter,
        fromDate: dateRange.startDate ? moment(dateRange.startDate).unix() : 0,
        toDate: dateRange.endDate ? moment(dateRange.endDate).unix() : 0,
      }),
    enabled: shouldRetriggerPaginatedApi,
  });

  useEffect(() => {
    if (isSuccessForSplitScreen && splitScreenData?.data?.cols.length) {
      const columnsDef = splitScreenData.data.cols.map((column) => {
        const columnDef = {
          field: column,
          filter: 'agTextColumnFilter',
        };
        if (column.toLowerCase() === 'recon status') {
          return {
            ...columnDef,
            cellRenderer: ReconStatusRender,
          };
        }
        if (column.toLowerCase() === 'recon remarks') {
          return {
            ...columnDef,
            cellRenderer: ReconRemarksRender,
          };
        }
        return columnDef;
      });

      if (columnsDef.length) {
        setColumnData([...columnsDef]);
      }
    }

    if (isSuccessForSplitScreen && !splitScreenData?.data?.cols.length) {
      setColumnData([]);
    }

    if (isSuccessForSplitScreen && splitScreenData?.data?.items.length) {
      const tableData = splitScreenData.data.items.map((item) =>
        splitScreenData.data.cols.reduce(
          (acc, column) => {
            acc[column] = item[column] ?? '';
            return acc;
          },
          { _id: item._id },
        ),
      );

      if (tableData.length) {
        setRowData(tableData);
      }
    }

    if (isSuccessForSplitScreen && !splitScreenData?.data?.items.length) {
      setRowData([]);
    }
  }, [isSuccessForSplitScreen, splitScreenData]);

  const onClickHandler = (event: RowClickedEvent<IData>) => {
    setIsDrawerOpen(true);
    const clickedRowData = event.data as IData;
    if (event && event?.data) {
      setClickedRow(clickedRowData);
    }
  };

  const handlePagination = (type) => {
    if (type === 'prev' && currentPage > 1) {
      setCurrentPage((prevPage) => prevPage - 1);
      if (splitScreenData?.data?.first_id) {
        setPaginationPageId({
          firstId: splitScreenData.data.first_id,
          lastId: null,
        });
      }
    } else if (type === 'next' && splitScreenData?.data?.has_more) {
      setCurrentPage((prevPage) => prevPage + 1);
      if (splitScreenData?.data?.last_id) {
        setPaginationPageId({
          firstId: null,
          lastId: splitScreenData.data.last_id,
        });
      }
    }
  };

  const handleSourceChange = ({ event }) => {
    const currentValue = event.values[0];
    setReconPairSources((prevReconPair) => {
      const index = prevReconPair.indexOf(sourceId);
      if (index === -1) return prevReconPair;
      const updatedReconPair = [...prevReconPair];
      updatedReconPair[index] = currentValue;
      return updatedReconPair;
    });
  };

  return (
    <RenderErrorLoadingOrChild isError={isErrorForSplitScreen} isLoading={isLoadingForSplitScreen}>
      <Box>
        <Box marginBottom="spacing.2" padding="spacing.1">
          <Dropdown selectionType="single">
            <SelectInput
              size="medium"
              label="Select a Recon Source"
              labelPosition="inside-input"
              placeholder="Select a Recon Source"
              value={sourceId}
              onChange={(event) => handleSourceChange({ event })}
            />
            <DropdownOverlay>
              <ActionList>
                {merchantSources.map((source) => (
                  <ActionListItem key={source.id} title={source.name} value={source.id} />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
        <div className="ag-theme-quartz" style={{ width: '100%', height: '500px' }}>
          {columnData?.length > 0 ? (
            <AgGridReact
              theme={splitScreenTheme}
              rowData={rowData}
              columnDefs={columnData}
              defaultColDef={defaultColDef}
              rowSelection={{
                checkboxes: true,
                mode: 'multiRow',
                enableClickSelection: false,
              }}
              onRowClicked={onClickHandler}
            />
          ) : null}
        </div>
        <Box display="flex" justifyContent="flex-end" alignItems="center" marginTop="spacing.3">
          <Text marginRight="spacing.4" size="small">
            Showing Page: {currentPage}
          </Text>
          <Button
            size="xsmall"
            icon={ArrowLeftIcon}
            marginRight="spacing.4"
            isDisabled={currentPage === 1}
            onClick={() => handlePagination('prev')}
          >
            Previous
          </Button>
          <Button
            size="xsmall"
            icon={ArrowRightIcon}
            isDisabled={!splitScreenData?.data?.has_more || rowData.length === 0}
            onClick={() => handlePagination('next')}
          >
            Next
          </Button>
        </Box>
      </Box>
    </RenderErrorLoadingOrChild>
  );
};

export default DataGrid;
