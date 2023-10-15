import React, { useState } from 'react';
import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Text,
  TextInput,
} from '@razorpay/blade/components';
import { modalConfig } from './ModalConfig';
import { ActiveModalI } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';

interface ModalFormProps {
  entity: ActiveModalI | null;
  showModal: boolean;
  onModalDismiss: () => void;
  onUpdateClick: (textinput: string | undefined) => void;
}

const ModalForm = ({ entity, showModal, onModalDismiss, onUpdateClick }: ModalFormProps) => {
  const [textInput, settextInput] = useState<string | undefined>('');

  const handleUpdateClick = () => {
    if (textInput) {
      onUpdateClick(textInput);
    }
  };
  const handleModalDismiss = () => {
    onModalDismiss();
  };

  if (!entity) return null;

  const {
    title = 'Update details',
    label = 'Enter new details',
    bodyText = '',
  } = modalConfig[entity?.id];

  const isBtnDisabled = (): boolean => {
    if (textInput) return false;
    else return true;
  };

  return (
    <Modal isOpen={showModal} onDismiss={handleModalDismiss} size="small">
      <ModalHeader title={title} />
      <ModalBody>
        <TextInput
          label={label}
          onChange={(e) => settextInput(e.value)}
          isRequired
          type="text"
          necessityIndicator="required"
        />
        <Box marginTop="spacing.6">
          <Text
            color="surface.text.normal.lowContrast"
            size="medium"
            variant="body"
            weight="regular"
            type="normal"
          >
            {bodyText}
          </Text>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button onClick={handleUpdateClick} isDisabled={isBtnDisabled()}>
            Update
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default ModalForm;
