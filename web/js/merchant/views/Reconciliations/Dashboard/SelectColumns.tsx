import React, { useState, useRef, useEffect, memo } from 'react';
import {
  Box,
  Button,
  Text,
  CheckboxGroup,
  Checkbox,
  SearchInput,
  Tabs,
  TabItem,
  TabList,
  TabPanel,
  Dropdown,
  DropdownOverlay,
  DropdownLink,
  ActionList,
  MoreVerticalIcon,
  ActionListItem,
  Badge,
  InstantSettlementIcon,
  ServerIcon,
} from '@razorpay/blade/components';
import {
  SelectColumnsProps,
  SelectColumnCheckboxesProps,
  SelectedColumn,
} from 'merchant/views/Reconciliations/Dashboard/types';
import List from 'rc-virtual-list';
import { connect } from 'react-redux';
import { compose } from 'redux';

import EditColumnModal from 'merchant/views/Reconciliations/Dashboard/EditColumnModal';
import SaveConfigModal from 'merchant/views/Reconciliations/Dashboard/SaveConfigModal';
import { MAX_COLUMN_LIMIT } from 'merchant/views/Reconciliations/Dashboard/constants';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';
import { useScrollPosition } from 'merchant/views/Reconciliations/hooks';
import { showNotification } from 'merchant_common/reducers/notifications';

const SelectColumnCheckboxes: React.FC<SelectColumnCheckboxesProps> = memo(
  ({
    columns,
    selectedColumnForReport,
    setSelectedColumnForReport,
    sourceId,
    searchQuery,
    showNotification,
    columnList,
    selectedColumnCheckbox,
    setSelectedColumnCheckBox,
  }) => {
    const filteredColumns = columns.filter((column) =>
      column.name.toLowerCase().includes(searchQuery.toLowerCase()),
    );

    const filteredSelectedColumnForReport = selectedColumnForReport.filter(
      (item) => item?.merchant_source_id === sourceId,
    );

    const isChecked = Boolean(
      searchQuery.length === 0 &&
        selectedColumnForReport.length &&
        columns.length &&
        filteredSelectedColumnForReport.length === columns.length,
    );

    const isIndeterminate = Boolean(
      searchQuery.length === 0 &&
        selectedColumnForReport.length &&
        columns.length &&
        filteredSelectedColumnForReport.length > 0 &&
        filteredSelectedColumnForReport.length < columns.length,
    );

    const onChangeInterminateCheckbox = ({ isChecked, sourceId }) => {
      const allValues = columns.filter((column) => column.merchant_source_id === sourceId);
      const allColumnIds = allValues.map((item) => item.id);
      if (isChecked) {
        setSelectedColumnForReport((prevValues) => {
          const existingIds = new Set(prevValues.map((item) => item.id));
          const newValues = allValues.filter((item) => !existingIds.has(item.id));
          if (prevValues?.length + newValues?.length > MAX_COLUMN_LIMIT) {
            showNotification({
              type: 'error',
              message: `You can't have more than ${MAX_COLUMN_LIMIT} columns for reporting`,
            });
            return prevValues;
          }
          return [...prevValues, ...newValues];
        });
        setSelectedColumnCheckBox((prevValues) => {
          const totalSelectedColumns = Object.values(prevValues).flat().length + allValues.length;
          if (totalSelectedColumns > MAX_COLUMN_LIMIT) {
            return prevValues;
          }
          return { ...prevValues, [sourceId]: allColumnIds };
        });
      } else {
        setSelectedColumnForReport((prevValues) =>
          prevValues.filter((item) => item.merchant_source_id !== sourceId),
        );
        setSelectedColumnCheckBox((prevValues) => {
          const updatedValues = { ...prevValues };
          if (updatedValues[sourceId] && updatedValues[sourceId].length) {
            updatedValues[sourceId] = [];
          }
          return updatedValues;
        });
      }
    };

    const onChangeCheckGroup = ({ values }) => {
      if (
        selectedColumnForReport.length === MAX_COLUMN_LIMIT &&
        values.length > selectedColumnCheckbox?.length
      ) {
        showNotification({
          type: 'error',
          message: `You can't have more than ${MAX_COLUMN_LIMIT} columns for reporting`,
        });
        return;
      }

      const columns = values
        .map((id) => columnList.find((column) => column.id === id))
        .filter((column) => column !== undefined && column !== null);

      setSelectedColumnForReport((prevAddedColumn) => {
        const prevColumnIds = new Set(prevAddedColumn.map((col) => col.id));
        const newColumns = columns.filter((column) => !prevColumnIds.has(column.id));
        const remainingColumns = prevAddedColumn.filter(
          (col) => col.merchant_source_id !== sourceId || values.includes(col.id),
        );
        return [...remainingColumns, ...newColumns];
      });

      setSelectedColumnCheckBox((prevCheckedValues) => {
        return {
          ...prevCheckedValues,
          [sourceId]: values,
        };
      });
    };

    return (
      <Box height="400px" overflowY="scroll" overflowX="hidden">
        {filteredColumns.length > 0 ? (
          <>
            <Checkbox
              marginY="spacing.3"
              display={searchQuery.length > 0 ? 'none' : 'block'}
              isDisabled={searchQuery.length > 0}
              isChecked={isChecked}
              isIndeterminate={isIndeterminate}
              onChange={({ isChecked }) => onChangeInterminateCheckbox({ isChecked, sourceId })}
            >
              Select All
            </Checkbox>
            <CheckboxGroup
              key={`checkbox-${sourceId}`}
              display="flex"
              // eslint-disable-next-line @typescript-eslint/ban-ts-comment
              // @ts-ignore
              flexDirection="column"
              gap="spacing.2"
              value={selectedColumnCheckbox.length ? selectedColumnCheckbox : []}
              onChange={({ values }) => onChangeCheckGroup({ values })}
            >
              <List data={filteredColumns} itemKey="id">
                {(column) => (
                  <Checkbox marginY="spacing.3" value={column.id}>
                    {column.name}
                  </Checkbox>
                )}
              </List>
            </CheckboxGroup>
          </>
        ) : (
          <Box padding="spacing.3" display="flex" justifyContent="center" alignItems="center">
            <Text weight="medium">No columns found.</Text>
          </Box>
        )}
      </Box>
    );
  },
);

const SelectColumns: React.FC<SelectColumnsProps> = memo(
  ({
    columnList,
    merchantSourcesColumn,
    isLoadingForColumnsForMatchingFields,
    isErrorForColumnsForMatchingFields,
    selectedColumnForReport,
    setSelectedColumnForReport,
    isLoadingForCreateReport,
    updateReportIsLoading,
    setHasCompletedCreateReportStep,
    configForm,
    setConfigForm,
    showNotification,
    selectedColumnCheckbox,
    setSelectedColumnCheckBox,
    type,
  }) => {
    const [isOpenSaveConfigModal, setIsOpenSaveConfigModal] = useState<boolean>(false);
    const [isOpenEditNameModal, setOpenEditNameModal] = useState<boolean>(false);
    const [searchQueries, setSearchQueries] = useState<Record<string, string>>({});
    const [editColumn, setEditColumn] = useState<SelectedColumn | null>(null);
    const [updatedNameForColumn, setUpdatedNameForColumn] = useState<string>('');

    const columnSelectionRef = useRef<HTMLElement | null>(null);
    const draggingItemIdRef = useRef<string | null>(null);

    const scrollPosition = useScrollPosition(columnSelectionRef);

    const handleSearchChange = (processId, sourceId, value) => {
      setSearchQueries((prevQueries) => ({
        ...prevQueries,
        [`${processId}_${sourceId}`]: value,
      }));
    };

    const removeSelectedColumnForReport = ({ columnId, sourceId }) => {
      setSelectedColumnForReport((prevSelectedColumn) => {
        return prevSelectedColumn.filter((col) => col.id !== columnId);
      });
      setSelectedColumnCheckBox((prevCheckValues) => {
        if (!sourceId) return prevCheckValues;
        const updatedValue = { ...prevCheckValues };
        if (updatedValue[sourceId]?.length) {
          updatedValue[sourceId] = updatedValue[sourceId].filter((id) => id !== columnId);
        }
        return updatedValue;
      });
    };

    const handleDragStart = (id) => {
      draggingItemIdRef.current = id;
    };

    const handleDragOver = (e) => {
      e.preventDefault();
    };

    const handleDrop = (id) => {
      const draggingItemId = draggingItemIdRef.current;
      if (draggingItemId === null) return;
      const draggingItemIndex = selectedColumnForReport.findIndex(
        (item) => item.id === draggingItemId,
      );
      const dropTargetIndex = selectedColumnForReport.findIndex((item) => item.id === id);
      if (draggingItemIndex === -1 || dropTargetIndex === -1) return;
      setSelectedColumnForReport((prevList) => {
        const updatedItems = [...prevList];
        const [draggedItem] = updatedItems.splice(draggingItemIndex, 1);
        updatedItems.splice(dropTargetIndex, 0, draggedItem);
        return updatedItems;
      });
      draggingItemIdRef.current = null;
    };

    const renameSelectedColumnForReport = ({ editedName }) => {
      const isDuplicateName = selectedColumnForReport.some(
        (column) => column.editedName === editedName || column.name === editedName,
      );
      if (isDuplicateName) {
        showNotification({
          type: 'error',
          message: `This name is already in use. Please choose a different name.`,
        });
        return;
      }
      const newSelectionColumns = selectedColumnForReport.map((column) =>
        column.id === editColumn?.id ? { ...column, editedName } : column,
      );
      setSelectedColumnForReport([...newSelectionColumns]);
      setOpenEditNameModal(false);
      setUpdatedNameForColumn('');
    };

    const findDuplicateName = (selectedColumnForReport: SelectedColumn[]) => {
      const seenNames = new Set<string>();
      const duplicateNames: string[] = [];
      for (const column of selectedColumnForReport) {
        const { editedName = '', name = '' } = column;
        const currentName = editedName || name;
        if (currentName && seenNames.has(currentName)) {
          duplicateNames.push(currentName);
        } else {
          seenNames.add(currentName);
        }
      }
      return duplicateNames;
    };

    const handleCreateAndEditSelectColumns = (e) => {
      e.preventDefault();
      const duplicateNames = findDuplicateName(selectedColumnForReport);
      if (duplicateNames.length > 0) {
        showNotification({
          type: 'error',
          message: `The following names are already in use: ${duplicateNames.join(
            ', ',
          )}. Please choose different names.`,
        });
        return;
      }
      setIsOpenSaveConfigModal(true);
    };

    useEffect(() => {
      if (isOpenEditNameModal === false) {
        setEditColumn(null);
        setUpdatedNameForColumn('');
      }
    }, [isOpenEditNameModal]);

    useEffect(() => {
      if (columnSelectionRef.current) {
        columnSelectionRef.current.scrollLeft = scrollPosition.scrollLeft;
      }
    }, [selectedColumnCheckbox]);

    return (
      <>
        <Box>
          <RenderErrorLoadingOrChild
            isError={isErrorForColumnsForMatchingFields}
            isLoading={isLoadingForColumnsForMatchingFields}
          >
            <Box display="flex" flexDirection="column" gap="spacing.4">
              <Box
                display="grid"
                gridTemplateColumns={`repeat(${
                  Object.keys(merchantSourcesColumn).length
                }, minmax(350px,1fr))`}
                overflowX="auto"
                overflowY="hidden"
                ref={columnSelectionRef}
              >
                {Object.keys(merchantSourcesColumn).length > 0
                  ? Object.keys(merchantSourcesColumn).map((processId) => (
                      <Box
                        key={processId}
                        display="flex"
                        flexDirection="column"
                        minWidth="350px"
                        borderRightWidth="thin"
                        borderRightColor="surface.border.gray.muted"
                      >
                        <Box
                          padding="spacing.4"
                          backgroundColor="surface.background.gray.subtle"
                          width="100%"
                        >
                          <Text weight="semibold">{merchantSourcesColumn[processId].name}</Text>
                        </Box>
                        <Box padding="spacing.3">
                          <Box padding="spacing.3">
                            <Tabs>
                              <TabList>
                                {Object.keys(merchantSourcesColumn[processId].sources).length > 0
                                  ? Object.keys(merchantSourcesColumn[processId].sources).map(
                                      (sourceId) => (
                                        <TabItem key={`${sourceId}_${processId}`} value={sourceId}>
                                          {merchantSourcesColumn[processId].sources[sourceId].name}
                                        </TabItem>
                                      ),
                                    )
                                  : null}
                              </TabList>
                              {Object.keys(merchantSourcesColumn[processId].sources).length > 0
                                ? Object.keys(merchantSourcesColumn[processId].sources).map(
                                    (sourceId) => (
                                      <TabPanel key={`${processId}-${sourceId}`} value={sourceId}>
                                        <Box
                                          display="flex"
                                          flexDirection="column"
                                          gap="spacing.3"
                                          marginY="spacing.4"
                                        >
                                          <SearchInput
                                            accessibilityLabel="Search"
                                            value={searchQueries[`${processId}_${sourceId}`] || ''}
                                            onChange={({ value }) =>
                                              handleSearchChange(processId, sourceId, value)
                                            }
                                            onClearButtonClick={() =>
                                              handleSearchChange(processId, sourceId, '')
                                            }
                                          />
                                        </Box>
                                        {merchantSourcesColumn[processId].sources[sourceId].columns
                                          .length > 0 ? (
                                          <SelectColumnCheckboxes
                                            columns={
                                              merchantSourcesColumn[processId].sources[sourceId]
                                                .columns
                                            }
                                            selectedColumnForReport={selectedColumnForReport}
                                            setSelectedColumnForReport={setSelectedColumnForReport}
                                            sourceId={sourceId}
                                            searchQuery={
                                              searchQueries[`${processId}_${sourceId}`] || ''
                                            }
                                            showNotification={showNotification}
                                            columnList={columnList}
                                            selectedColumnCheckbox={
                                              sourceId &&
                                              selectedColumnCheckbox[`${sourceId}`]?.length
                                                ? selectedColumnCheckbox[`${sourceId}`]
                                                : []
                                            }
                                            setSelectedColumnCheckBox={setSelectedColumnCheckBox}
                                          />
                                        ) : null}
                                      </TabPanel>
                                    ),
                                  )
                                : null}
                            </Tabs>
                          </Box>
                        </Box>
                      </Box>
                    ))
                  : null}
              </Box>
              <Box>
                {selectedColumnForReport.length > 0 ? (
                  <Box padding="spacing.4" width="100%">
                    <Text weight="semibold">Selected Columns</Text>
                    <Text>Selected Columns count: {selectedColumnForReport.length}</Text>
                  </Box>
                ) : null}

                <Box
                  overflowX="auto"
                  display="grid"
                  gridTemplateColumns={`repeat(${
                    selectedColumnForReport.length || 0
                  }, minmax(200px, 1fr))`}
                  gridTemplateRows="1fr"
                  gridAutoFlow="column"
                >
                  {selectedColumnForReport.length
                    ? selectedColumnForReport.map((column) => (
                        <Box
                          key={column.id}
                          onDragStart={() => handleDragStart(column?.id)}
                          onDragOver={handleDragOver}
                          onDrop={() => handleDrop(column?.id)}
                          draggable
                          borderRightWidth="thin"
                          borderBottomWidth="thin"
                          borderRightColor="surface.border.gray.muted"
                          borderBottomColor="surface.border.gray.muted"
                        >
                          <Box
                            display="flex"
                            justifyContent="space-between"
                            alignItems="center"
                            height="36px"
                            backgroundColor="surface.background.gray.subtle"
                            padding="spacing.4"
                            borderBottomWidth="thin"
                            borderBottomColor="surface.border.gray.muted"
                          >
                            <Text truncateAfterLines={1} weight="semibold">
                              {column?.editedName || column?.name}
                            </Text>
                            <Dropdown>
                              <DropdownLink
                                icon={() => <MoreVerticalIcon color="surface.icon.gray.muted" />}
                              />
                              <DropdownOverlay>
                                <ActionList>
                                  <ActionListItem
                                    title="Rename"
                                    value=""
                                    onClick={() => {
                                      setOpenEditNameModal(true);
                                      setEditColumn(column);
                                    }}
                                  />
                                  <ActionListItem
                                    title="Delete"
                                    value=""
                                    onClick={() =>
                                      removeSelectedColumnForReport({
                                        columnId: column?.id,
                                        sourceId: column?.merchant_source_id,
                                      })
                                    }
                                  />
                                </ActionList>
                              </DropdownOverlay>
                            </Dropdown>
                          </Box>
                          <Box
                            display="flex"
                            flexDirection="column"
                            justifyContent="center"
                            alignItems="start"
                            padding="spacing.4"
                            gap="spacing.4"
                          >
                            {column?.merchant_process_name ? (
                              <Badge size="medium" icon={ServerIcon}>
                                {column?.merchant_process_name}
                              </Badge>
                            ) : null}
                            {column?.merchant_source_name ? (
                              <Badge size="medium" icon={InstantSettlementIcon}>
                                {column?.merchant_source_name}
                              </Badge>
                            ) : null}
                          </Box>
                        </Box>
                      ))
                    : null}
                </Box>
              </Box>
              <Box display="flex" justifyContent="flex-end" gap="spacing.4">
                {type === 'create' ? (
                  <Button
                    isDisabled={selectedColumnForReport.length <= 0 || isLoadingForCreateReport}
                    variant="primary"
                    onClick={(e) => handleCreateAndEditSelectColumns(e)}
                  >
                    Create
                  </Button>
                ) : null}
                {type === 'edit' ? (
                  <Button
                    isDisabled={selectedColumnForReport.length <= 0 || updateReportIsLoading}
                    variant="primary"
                    onClick={(e) => handleCreateAndEditSelectColumns(e)}
                  >
                    Update
                  </Button>
                ) : null}
              </Box>
            </Box>
          </RenderErrorLoadingOrChild>
        </Box>
        <SaveConfigModal
          isOpenSaveConfigModal={isOpenSaveConfigModal}
          setIsOpenSaveConfigModal={setIsOpenSaveConfigModal}
          configForm={configForm}
          setConfigForm={setConfigForm}
          setHasCompletedCreateReportStep={setHasCompletedCreateReportStep}
        />
        <EditColumnModal
          isOpenEditNameModal={isOpenEditNameModal}
          setOpenEditNameModal={setOpenEditNameModal}
          setUpdatedNameForColumn={setUpdatedNameForColumn}
          updatedNameForColumn={updatedNameForColumn}
          editColumn={editColumn}
          renameSelectedColumnForReport={renameSelectedColumnForReport}
        />
      </>
    );
  },
);

export default compose(
  connect(null, {
    showNotification,
  }),
)(SelectColumns);
