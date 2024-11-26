import React, { useState } from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
  Box,
  Text,
  Button,
  Badge,
} from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import {
  ProcessSelectionModalProps,
  ProcessesData,
} from 'merchant/views/Reconciliations/Dashboard/types';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { fetchProcessList } from 'merchant/views/Reconciliations/api';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';
import { showNotification } from 'merchant_common/reducers/notifications';

const maxProcessLimit = 4;

const ProcessSelectionModal: React.FC<ProcessSelectionModalProps> = ({
  openProcessSelectionModal,
  selectedProcessesItem,
  setSelectedProcessesItem,
  setIsProcessesSelectionDone,
  setOpenProcessSelectionModal,
  showNotification,
}) => {
  const {
    data: processesDataList,
    isLoading,
    isError,
  } = useQuery<ProcessesData>({
    queryKey: ['processList'],
    queryFn: fetchProcessList,
  });

  const [processSelectedIds, setProcessSelectedIds] = useState<string[]>([]);

  const handleSelectionChange = ({ selectedIds = [] }: { selectedIds: any[] }) => {
    setProcessSelectedIds([...selectedIds]);

    if (selectedIds.length > maxProcessLimit) {
      showNotification({ type: 'error', message: 'You can only select four processes' });
      setSelectedProcessesItem([]);
      return;
    }

    const selectedList =
      processesDataList?.data?.items?.filter((data) => selectedIds.includes(data.id)) || [];

    setSelectedProcessesItem(selectedList);
  };

  return (
    <Modal
      isOpen={openProcessSelectionModal}
      onDismiss={() => setOpenProcessSelectionModal(false)}
      size="medium"
    >
      <ModalHeader
        title="Select the process"
        subtitle="Select the process you want the source data to be added from for your custom report."
      />
      <ModalBody>
        <RenderErrorLoadingOrChild isError={isError} isLoading={isLoading}>
          {processesDataList?.data?.items.length ? (
            <Box overflow="auto" maxHeight="500px">
              <Table
                data={{ nodes: processesDataList?.data?.items }}
                selectionType="multiple"
                onSelectionChange={({ selectedIds }) => handleSelectionChange({ selectedIds })}
                multiSelectTrigger="row"
              >
                {(tableData) => (
                  <>
                    <TableHeader>
                      <TableHeaderRow>
                        <TableHeaderCell>
                          <Text>Process</Text>
                        </TableHeaderCell>
                        <TableHeaderCell>
                          <Text>Source Type</Text>
                        </TableHeaderCell>
                      </TableHeaderRow>
                    </TableHeader>
                    <TableBody>
                      {tableData.map((tableItem) => (
                        <TableRow key={tableItem.id} item={tableItem}>
                          <TableCell>
                            <Text>{tableItem.name}</Text>
                          </TableCell>
                          <TableCell>
                            <Box
                              display="grid"
                              gridTemplateColumns="repeat(1, 1fr)"
                              gap="spacing.3"
                              margin="spacing.3"
                            >
                              {tableItem?.merchant_sources?.length
                                ? tableItem.merchant_sources.map((type) => (
                                    <Badge size="medium" color="information" key={type?.id}>
                                      {type?.name}
                                    </Badge>
                                  ))
                                : null}
                            </Box>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </>
                )}
              </Table>
            </Box>
          ) : null}
        </RenderErrorLoadingOrChild>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="space-between">
          <Text>
            {selectedProcessesItem?.length
              ? `${selectedProcessesItem.length} process selected`
              : 'No process selected'}
          </Text>
          <Box display="flex" gap="spacing.4">
            <Button
              variant="tertiary"
              onClick={() => {
                setOpenProcessSelectionModal(false);
                setSelectedProcessesItem([]);
              }}
            >
              Cancel
            </Button>
            <Button
              variant="primary"
              onClick={() => {
                if (processSelectedIds.length > maxProcessLimit) {
                  showNotification({
                    type: 'error',
                    message: 'You can only select four processes',
                  });
                  setSelectedProcessesItem([]);
                  return;
                }
                if (processSelectedIds.length > 0) {
                  setIsProcessesSelectionDone(true);
                } else {
                  setIsProcessesSelectionDone(false);
                }
                setOpenProcessSelectionModal(false);
              }}
            >
              Add
            </Button>
          </Box>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default compose(connect(null, { showNotification }))(ProcessSelectionModal);
