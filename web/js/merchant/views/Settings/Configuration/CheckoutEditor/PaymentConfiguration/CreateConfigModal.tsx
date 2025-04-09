import React, { useEffect, useState } from 'react';
import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  TextInput,
} from '@razorpay/blade/components';

export type ConfigNameModalProps = {
  isOpen: boolean;
  onClose: () => void;
  onSave: (name: string) => void;
  ctaText: string;
  initialConfigName?: string;
};

export function CreateConfigModal({
  isOpen,
  onClose,
  onSave,
  ctaText,
  initialConfigName = '',
}: ConfigNameModalProps) {
  const [configName, setConfigName] = useState(initialConfigName);

  function handleConfigNameSave() {
    onSave(configName);
    onClose();
  }

  useEffect(() => {
    if (isOpen) {
      setConfigName(initialConfigName);
    }
  }, [isOpen, initialConfigName]);

  return (
    <Modal isOpen={isOpen} onDismiss={onClose} size="small">
      <ModalHeader
        title="Create a custom configuration"
        subtitle="Customise the payment blocks on your checkout"
      />
      <ModalBody>
        <TextInput
          label="Configuration name"
          onChange={({ value }) => setConfigName(value ?? '')}
          value={configName}
          autoFocus={true}
          maxCharacters={22}
        />
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button variant="tertiary" onClick={onClose}>
            Cancel
          </Button>
          <Button onClick={handleConfigNameSave} isDisabled={configName === initialConfigName}>
            {ctaText}
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
}
