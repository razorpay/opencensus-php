import React, { useEffect, useState, memo } from 'react';
import {
  DropdownOverlay,
  AutoComplete,
  ActionList,
  ActionListItem,
  Box,
  Text,
  Button,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableEditableDropdownCell,
  PlusIcon,
  TableCell,
  IconButton,
  TrashIcon,
  ActionListItemBadge,
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
} from '@razorpay/blade/components';
import {
  MatchingFieldKeySelectionProps,
  Process,
} from 'merchant/views/Reconciliations/Dashboard/types';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';
import { showNotification } from 'merchant_common/reducers/notifications';

const MatchingFieldKeySelection: React.FC<MatchingFieldKeySelectionProps> = memo(
  ({
    processes,
    isLoadingForColumnsForMatchingFields,
    isErrorForColumnsForMatchingFields,
    showNotification,
    setHasCompletedCreateReportStep,
    matchingFieldKeys,
    joiningConfigData,
    setMatchingFieldKeys,
    joiningConfigMutation,
    isSuccessForJoiningConfig,
    isLoadingForJoiningConfig,
  }) => {
    const [isOpenValidateModal, setIsOpenValidateModal] = useState(false);
    const [dataNode, setDataNode] = useState<Record<string, Process>[]>([]);

    const [validationDataNode, setValidationDataNode] = useState<Array<Record<string, string>>>([]);

    const maxRows = 3 * Object.keys(processes).length;

    const addNewRowInTable = () => {
      const currentRowIndex = matchingFieldKeys.length - 1;
      const currentRowValues = matchingFieldKeys[currentRowIndex] || {};
      const selectedCount = Object.values(currentRowValues).filter(Boolean).length;

      if (selectedCount < 2) {
        showNotification({
          type: 'error',
          message: 'Please select at least two options before adding a new row.',
        });
        return;
      }

      if (matchingFieldKeys.length >= maxRows) {
        showNotification({
          type: 'error',
          message: `You can only add up to ${maxRows} rows.`,
        });
        return;
      }

      setDataNode((prevData) => [...prevData, { ...processes }]);
      setMatchingFieldKeys((prevValues) => [...prevValues, {}]);
    };

    const handleSelectChange = (rowIndex, processId, option) => {
      setMatchingFieldKeys((prevValues) => {
        const newValues = [...prevValues];
        newValues[rowIndex] = {
          ...newValues[rowIndex],
          [processId]: option,
        };
        return newValues;
      });
    };

    const deleteRow = (rowIndex) => {
      if (matchingFieldKeys.length === 1) {
        showNotification({
          type: 'error',
          message:
            'At least one row is required for matching fields. You cannot delete the last remaining row.',
        });
        return;
      }

      setDataNode((prevData) => prevData.filter((_, index) => index !== rowIndex));
      setMatchingFieldKeys((prevValues) => prevValues.filter((_, index) => index !== rowIndex));
    };

    useEffect(() => {
      if (Object.values(processes).length) {
        setDataNode([processes]);
      }
    }, [processes]);

    useEffect(() => {
      const isValidationTableSchemaExist =
        isSuccessForJoiningConfig && joiningConfigData?.data?.frontend_joining_config.length;
      if (isValidationTableSchemaExist) {
        setIsOpenValidateModal(true);
        const validataionTableDataNode = joiningConfigData?.data?.frontend_joining_config.map(
          (field) => {
            return field.matching_fields.reduce((acc, column) => {
              if (column.column) {
                acc[column.merchant_process_id] = column.column;
              }
              return acc;
            }, {});
          },
        );

        setValidationDataNode(validataionTableDataNode.length ? validataionTableDataNode : []);
      }
    }, [isSuccessForJoiningConfig]);

    return (
      <>
        <Box overflow="auto">
          <RenderErrorLoadingOrChild
            isError={isErrorForColumnsForMatchingFields}
            isLoading={isLoadingForColumnsForMatchingFields}
          >
            {dataNode.length > 0 ? (
              <>
                <Table
                  data={{
                    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                    // @ts-ignore
                    nodes: dataNode,
                  }}
                  display="grid"
                  gridTemplateColumns={`repeat(${
                    Object.keys(processes).length
                  },minmax( 300px,1fr)) 0.1fr`}
                  showBorderedCells={true}
                >
                  {(tableData) => (
                    <>
                      <TableHeader>
                        <TableHeaderRow>
                          {Object.values(processes).map((process) => (
                            <TableHeaderCell key={process.id}>
                              <Text weight="semibold">{process.name}</Text>
                            </TableHeaderCell>
                          ))}
                          <TableHeaderCell>{''}</TableHeaderCell>
                        </TableHeaderRow>
                      </TableHeader>
                      <TableBody>
                        {tableData.map((tableItem, rowIndex) => (
                          <TableRow key={rowIndex} item={tableItem}>
                            {Object.keys(processes).map((processId) => (
                              <TableEditableDropdownCell key={processId}>
                                <AutoComplete
                                  placeholder="Select"
                                  onChange={({ values }) => {
                                    if (values.length) {
                                      handleSelectChange(rowIndex, processId, values[0]);
                                    }
                                  }}
                                  inputValue={
                                    matchingFieldKeys?.[rowIndex]?.[processId]?.name
                                      ? matchingFieldKeys[rowIndex][processId].name
                                      : ''
                                  }
                                />
                                <DropdownOverlay>
                                  <ActionList>
                                    {processes[processId].columns.map((option, index) => (
                                      <ActionListItem
                                        key={index}
                                        title={option.name}
                                        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                                        // @ts-ignore
                                        value={option}
                                        titleSuffix={
                                          <ActionListItemBadge color="positive" marginLeft="auto">
                                            {option.merchant_source_name}
                                          </ActionListItemBadge>
                                        }
                                      />
                                    ))}
                                  </ActionList>
                                </DropdownOverlay>
                              </TableEditableDropdownCell>
                            ))}
                            <TableCell>
                              <Box
                                display="flex"
                                justifyContent="center"
                                alignItems="center"
                                width="100%"
                              >
                                <IconButton
                                  accessibilityLabel="delete "
                                  icon={TrashIcon}
                                  onClick={() => deleteRow(rowIndex)}
                                />
                              </Box>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </>
                  )}
                </Table>
                <Box marginTop="spacing.4" display="flex" gap="spacing.4" justifyContent="flex-end">
                  <Button onClick={addNewRowInTable} icon={PlusIcon} variant="secondary">
                    Add Another Key
                  </Button>
                  <Button
                    isDisabled={
                      matchingFieldKeys.length === 0 ||
                      matchingFieldKeys.some((row) => {
                        const selectedCount = Object.values(row).filter(Boolean).length;
                        return selectedCount < 2;
                      }) ||
                      isLoadingForJoiningConfig
                    }
                    onClick={(e) => {
                      e.preventDefault();
                      joiningConfigMutation();
                    }}
                  >
                    Submit
                  </Button>
                </Box>
              </>
            ) : null}
          </RenderErrorLoadingOrChild>
        </Box>
        <Modal
          isOpen={isOpenValidateModal}
          onDismiss={() => setIsOpenValidateModal(false)}
          size="medium"
        >
          <ModalHeader title="Review and Confirm" />
          <ModalBody>
            <Box overflow="auto">
              <RenderErrorLoadingOrChild
                isError={!isSuccessForJoiningConfig}
                isLoading={isLoadingForJoiningConfig}
              >
                {validationDataNode && validationDataNode.length ? (
                  <Table
                    data={{
                      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                      // @ts-ignore
                      nodes: validationDataNode,
                    }}
                    display="grid"
                    gridTemplateColumns={`repeat(${Object.keys(processes).length}, 1fr)`}
                    showBorderedCells={true}
                  >
                    {(tableData) => (
                      <>
                        <TableHeader>
                          <TableHeaderRow>
                            {Object.values(processes).map((process) => (
                              <TableHeaderCell key={process.id}>
                                <Text weight="semibold">{process.name}</Text>
                              </TableHeaderCell>
                            ))}
                          </TableHeaderRow>
                        </TableHeader>
                        <TableBody>
                          {tableData.map((tableItem, index) => (
                            <TableRow key={index} item={tableItem}>
                              {Object.keys(processes).map((processId) => (
                                <TableCell key={processId}>
                                  {tableItem[processId] ? tableItem[processId] : ''}
                                </TableCell>
                              ))}
                            </TableRow>
                          ))}
                        </TableBody>
                      </>
                    )}
                  </Table>
                ) : null}
              </RenderErrorLoadingOrChild>
            </Box>
          </ModalBody>
          <ModalFooter>
            <Box display="flex" justifyContent="flex-end" gap="spacing.4">
              <Button
                variant="tertiary"
                onClick={() => {
                  setIsOpenValidateModal(false);
                }}
              >
                Go back
              </Button>
              <Button
                variant="primary"
                onClick={() => {
                  setHasCompletedCreateReportStep((prevStep) => ({
                    ...prevStep,
                    matchingFields: true,
                  }));
                  setIsOpenValidateModal(false);
                }}
              >
                Confirm
              </Button>
            </Box>
          </ModalFooter>
        </Modal>
      </>
    );
  },
);

export default compose(
  connect(null, {
    showNotification,
  }),
)(MatchingFieldKeySelection);
