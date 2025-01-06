import React, { useState } from 'react';
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
  Text,
  Switch,
  TableToolbar,
  InfoIcon,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';

import StatusChangeAlertModal from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/components/StatusChangeAlertModal';
import DateCell from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/components/TerminalsTableComponent/Cells/DateCell';
import DualLineInfoCell from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/components/TerminalsTableComponent/Cells/DualLineInfoCell';
import StoreDetailsCell from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/components/TerminalsTableComponent/Cells/StoreDetailsCell';
import {
  TERMINAL_BIT_OPTIONS,
  ACTIVE_TERMINAL_INFO_INIT_VALUE,
} from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/constants';

import type { Terminal } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/types';
import type { PaginationLimitType } from 'merchant/views/BillMeSettings/common/types';

type TerminalsTableComponentProps = {
  tableProps: {
    isRefreshing: boolean;
    defaultPageSize: PaginationLimitType;
    changePage: (offset: number) => void;
    changePageSize: (limit: PaginationLimitType) => void;
    totalItemCount: number;
    terminalsData: Terminal[];
    currentPage: number;
  };
  onToggleStatus: ({ id, isActive }: { id: string; isActive: boolean }) => void;
};

type ActiveTerminalInfoType = {
  id: string;
  showModal: boolean;
};

const TerminalsTableComponent = ({
  tableProps: {
    isRefreshing,
    defaultPageSize = 10,
    changePage,
    totalItemCount,
    terminalsData,
    changePageSize,
    currentPage,
  },
  onToggleStatus,
}: TerminalsTableComponentProps): React.ReactElement => {
  const [activeTerminalInfo, setActiveTerminalInfo] = useState<ActiveTerminalInfoType>(
    ACTIVE_TERMINAL_INFO_INIT_VALUE,
  );

  const handleStatusToggleChange = ({ id, isActive }: { id: string; isActive: boolean }) => {
    if (
      !isActive &&
      window.sessionStorage.getItem('storeTerminalStatusAlertAcknowledged') !== 'true'
    ) {
      setActiveTerminalInfo({ id, showModal: true });
    } else {
      onToggleStatus({ id, isActive });
    }
  };

  const closeAlertModal = () => setActiveTerminalInfo(ACTIVE_TERMINAL_INFO_INIT_VALUE);

  const handleStatusChangeModalSubmission = () => {
    onToggleStatus({ id: activeTerminalInfo.id, isActive: false });
    closeAlertModal();
    window.sessionStorage.setItem('storeTerminalStatusAlertAcknowledged', 'true');
  };

  return (
    <>
      {activeTerminalInfo.showModal && (
        <StatusChangeAlertModal
          modalProps={{
            isOpen: activeTerminalInfo.showModal,
            onDismiss: closeAlertModal,
          }}
          onSubmit={handleStatusChangeModalSubmission}
        />
      )}
      <Box backgroundColor="surface.background.gray.intense" minHeight="400px" overflow="auto">
        <Table
          isRefreshing={isRefreshing}
          data={{
            nodes: terminalsData,
          }}
          pagination={
            terminalsData?.length > 0 ? (
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
          toolbar={<TableToolbar />}
          gridTemplateColumns="20% 15% 15% 15% 15% 10% 10%"
        >
          {(tableData) => (
            <>
              <TableHeader>
                <TableHeaderRow>
                  <TableHeaderCell>
                    <Box whiteSpace="normal">
                      <Text weight="medium">Store Code & Details</Text>
                    </Box>
                  </TableHeaderCell>
                  <TableHeaderCell>
                    <Box whiteSpace="normal">
                      <Text weight="medium">Terminal Key</Text>
                    </Box>
                  </TableHeaderCell>
                  <TableHeaderCell>
                    <Box whiteSpace="normal">
                      <Text weight="medium">POS Details</Text>
                    </Box>
                  </TableHeaderCell>
                  <TableHeaderCell>
                    <Box whiteSpace="normal">
                      <Text weight="medium">Last Transaction On</Text>
                    </Box>
                  </TableHeaderCell>
                  <TableHeaderCell>
                    <Box whiteSpace="normal">
                      <Text weight="medium">Last Updated At</Text>
                    </Box>
                  </TableHeaderCell>
                  <TableHeaderCell>
                    <Box whiteSpace="normal">
                      <Text weight="medium">Version & BIT</Text>
                    </Box>
                  </TableHeaderCell>
                  <TableHeaderCell>
                    <Box whiteSpace="normal" display="flex" gap="spacing.2">
                      <Text weight="medium">Status</Text>
                    </Box>
                  </TableHeaderCell>
                </TableHeaderRow>
              </TableHeader>
              {terminalsData.length ? (
                <TableBody>
                  {tableData.map((tableItem) => (
                    <TableRow key={tableItem?.id} item={tableItem}>
                      <TableCell>
                        <Box whiteSpace="normal">
                          <StoreDetailsCell store={tableItem?.store} />
                        </Box>
                      </TableCell>
                      <TableCell>
                        <Box whiteSpace="normal">
                          <Text>{tableItem?.terminalInfo?.licenseKey}</Text>
                        </Box>
                      </TableCell>
                      <TableCell>
                        <Box whiteSpace="normal">
                          <DualLineInfoCell
                            line1={`${tableItem?.name} - ${
                              tableItem?.terminalInfo?.ipAddress || ''
                            }`}
                            line2={tableItem?.terminalInfo?.macAddress}
                          />
                        </Box>
                      </TableCell>
                      <TableCell>
                        <Box whiteSpace="normal">
                          <DateCell time={tableItem?.transactionDates?.lastTransactionAt} />
                        </Box>
                      </TableCell>
                      <TableCell>
                        <Box whiteSpace="normal">
                          <DateCell time={tableItem?.dates?.updatedAt} />
                        </Box>
                      </TableCell>
                      <TableCell>
                        <Box whiteSpace="normal">
                          <DualLineInfoCell
                            line1={tableItem?.terminalInfo?.version}
                            line2={TERMINAL_BIT_OPTIONS[tableItem?.terminalInfo?.bit]}
                          />
                        </Box>
                      </TableCell>
                      <TableCell>
                        {tableItem?.store?.storeInfo?.linkedProducts?.includes(
                          'DIGITAL_BILLING',
                        ) ? (
                          <Switch
                            accessibilityLabel="Toggle terminal status for current row"
                            size="medium"
                            onChange={() =>
                              handleStatusToggleChange({
                                id: tableItem?.id,
                                isActive: !tableItem?.isActive,
                              })
                            }
                            isChecked={tableItem?.isActive}
                          />
                        ) : (
                          <Tooltip content="This terminal has been removed from the store and can no longer be activated or deactivated">
                            <TooltipInteractiveWrapper paddingTop="spacing.1">
                              <Switch
                                isDisabled
                                accessibilityLabel="Toggle terminal status for current row"
                                size="medium"
                                isChecked={tableItem?.isActive}
                              />
                            </TooltipInteractiveWrapper>
                          </Tooltip>
                        )}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              ) : (
                // Grid column end value is given in accordance to number of table columns + 1
                <Box gridColumn={`1/8`} padding="spacing.6">
                  <Box gap="spacing.2" display="flex" justifyContent="center" alignItems="center">
                    <InfoIcon size="xlarge" />{' '}
                    <Text weight="semibold" variant="body">
                      No Billing Terminals found. Add terminals to a store to see your terminals
                      data here.
                    </Text>
                  </Box>
                </Box>
              )}
            </>
          )}
        </Table>
      </Box>
    </>
  );
};

export default TerminalsTableComponent;
