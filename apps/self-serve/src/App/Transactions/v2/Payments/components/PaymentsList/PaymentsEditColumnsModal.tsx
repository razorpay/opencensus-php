import React, { useState, useEffect } from 'react';
import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Checkbox,
} from '@razorpay/blade/components';
import { useStore } from '@federated/apps/shell/commonStore';
import { ERROR_MESSAGES } from './constants';
import { saveMerchantColumnPreferences } from './model';

interface PaymentsEditColumnsModalProps {
  isOpen: boolean;
  columnsList: string[];
  selectedColumnsList: string[];
  fixedColumns: string[];
  optionalColumns: string[];
  onClose: () => void;
  onSubmit: (selectedColumns: string[]) => void;
}

export const PaymentsEditColumnsModal = ({
  isOpen,
  columnsList,
  selectedColumnsList,
  fixedColumns,
  optionalColumns,
  onClose,
  onSubmit,
}: PaymentsEditColumnsModalProps) => {
  const [selectedColumns, setSelectedColumns] = useState(selectedColumnsList);
  const [isSavePreferencesChecked, setIsSavePreferencesChecked] = useState(false);
  const showNotification = useStore((state) => state.showNotification);

  useEffect(() => {
    setSelectedColumns(selectedColumnsList);
  }, [selectedColumnsList]);

  const saveColumnsPreferences = async () => {
    onSubmit(selectedColumns);
    if (isSavePreferencesChecked) {
      try {
        const { status_code } = await saveMerchantColumnPreferences(selectedColumns);
        if (status_code !== 200) throw new Error();
      } catch (error) {
        showNotification({ type: 'error', message: ERROR_MESSAGES.SAVE_PREFERENCES });
      }
    }
  };

  const updateSelectedColumns = (e) => {
    const { name: columnName, checked } = e.event.target;
    const updatedColumnsList = checked
      ? [...selectedColumns, columnName]
      : selectedColumns.filter((column) => column !== columnName);
    setSelectedColumns(updatedColumnsList);
  };

  const handleSavePreferencesChange = ({ isChecked }) => {
    setIsSavePreferencesChecked(isChecked);
  };

  /* 
    This list preserves the order of columns in the modal, starting with fixed columns 
    followed by optional columns, followed by selected columns which are not part of optional columns
    and finally the remaining columns, ensuring no duplicate entries are present
  */
  const updatedColumnsList = [
    ...fixedColumns,
    ...optionalColumns,
    ...selectedColumnsList.filter((col) => !optionalColumns.includes(col)),
    ...columnsList.filter((col) => !selectedColumnsList.includes(col)),
  ];

  const renderColumnsList = updatedColumnsList.map(
    (columnName) =>
      columnName && (
        <Checkbox
          size="medium"
          key={columnName}
          name={columnName}
          isChecked={selectedColumns.includes(columnName) || fixedColumns.includes(columnName)}
          isDisabled={fixedColumns.includes(columnName)}
          onChange={updateSelectedColumns}
        >
          {columnName}
        </Checkbox>
      ),
  );

  return (
    <Modal isOpen={isOpen} onDismiss={onClose} size="large">
      <ModalHeader title="Edit Columns" />
      <ModalBody>
        <Box
          display="grid"
          overflowX="auto"
          gridTemplateColumns={{ s: 'repeat(2, 1fr)', l: 'repeat(3, 1fr)', xl: 'repeat(4, 1fr)' }}
        >
          {renderColumnsList}
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" alignItems="center" justifyContent="space-between">
          <Checkbox
            key="save-preferences"
            size="medium"
            isChecked={isSavePreferencesChecked}
            onChange={handleSavePreferencesChange}
          >
            Save my preferences
          </Checkbox>
          <Button onClick={saveColumnsPreferences}>Save Changes</Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};
