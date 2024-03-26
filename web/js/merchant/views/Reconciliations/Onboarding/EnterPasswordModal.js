import React from 'react';
import {
  Text,
  Box,
  Modal,
  ModalHeader,
  ModalBody,
  PasswordInput,
  LockIcon,
  ModalFooter,
  Button,
} from '@razorpay/blade/components';
import FileUploading from 'assets/reconciliations/file-uploading.svg';

export default function EnterPasswordModal({ activePassFile, setActivePassFile, handleSubmit }) {
  return (
    <Modal isOpen={activePassFile} onDismiss={() => setActivePassFile(null)} size="small">
      <ModalHeader
        title="Enter the document password"
        subtitle="Uploaded file is password protected"
        leading={<LockIcon />}
      />
      <ModalBody padding="spacing.6">
        <Text type="subdued" weight="bold" size="small">
          Document
        </Text>
        <Box
          paddingX="spacing.6"
          paddingY="spacing.4"
          display="flex"
          alignItems="center"
          marginTop="spacing.3"
          borderWidth="thick"
          borderColor="brand.gray.300.lowContrast"
          borderRadius="medium"
        >
          <img src={FileUploading} alt="File Icon" />
          <Text marginLeft="spacing.4">{activePassFile?.fileName || ''}</Text>
        </Box>
        <PasswordInput
          label="Enter password"
          marginTop="spacing.6"
          validationState={activePassFile?.error ? 'error' : 'default'}
          errorText={activePassFile?.error}
          value={activePassFile?.password || ''}
          onChange={({ value }) => setActivePassFile((prev) => ({ ...prev, password: value }))}
        />
        <ModalFooter>
          <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
            <Button variant="secondary" onClick={() => setActivePassFile(null)}>
              Cancel
            </Button>
            <Button onClick={handleSubmit} isDisabled={!activePassFile?.password}>
              Proceed
            </Button>
          </Box>
        </ModalFooter>
      </ModalBody>
    </Modal>
  );
}
