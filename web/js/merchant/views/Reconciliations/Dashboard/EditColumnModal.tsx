import React from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Box,
  TextInput,
  Button,
} from '@razorpay/blade/components';
import { EditColumnModalProps } from 'merchant/views/Reconciliations/Dashboard/types';

const EditColumnModal: React.FC<EditColumnModalProps> = ({
  isOpenEditNameModal,
  setOpenEditNameModal,
  editColumn,
  updatedNameForColumn,
  setUpdatedNameForColumn,
  renameSelectedColumnForReport,
}) => {
  return (
    <Modal
      isOpen={isOpenEditNameModal}
      onDismiss={() => {
        setOpenEditNameModal(false);
        setUpdatedNameForColumn('');
      }}
    >
      <ModalHeader title="Edit Column" />
      <ModalBody>
        <Box display="flex" flexDirection="column" gap="spacing.4" padding="spacing.4">
          <TextInput
            label="Current Column Name"
            value={editColumn?.editedName || editColumn?.name || ''}
            isDisabled
          />
          <TextInput
            label="Updated Name"
            placeholder="Enter the new name for column here"
            isRequired={true}
            necessityIndicator="required"
            onChange={({ value }) => {
              if (value) {
                setUpdatedNameForColumn(value);
              }
            }}
            value={updatedNameForColumn}
          />
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.4" justifyContent="flex-end">
          <Button variant="tertiary" onClick={() => setOpenEditNameModal(false)}>
            Cancel
          </Button>
          <Button
            variant="primary"
            onClick={() => {
              if (editColumn) {
                renameSelectedColumnForReport({
                  columnId: editColumn.id,
                  editedName: updatedNameForColumn,
                });
              }
            }}
          >
            Save
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default EditColumnModal;
