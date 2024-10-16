import React, { useState } from 'react';
import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Text,
  Divider,
} from '@razorpay/blade/components';

import UploadInvoice from './UploadInvoice';
import FetchInvoice from './FetchInvoice';
import { uploadInvoice } from './services';
import { AddInvoiceType, FileType } from './types';
import { FILE_TYPE } from './constants';

const AddInvoice = ({
  id,
  onDismiss,
  showNotification,
  onUploadSuccess,
}: AddInvoiceType): JSX.Element => {
  const [selectedFile, setSelectedFile] = useState<{ file: File | null; type: FileType | null }>({
    file: null,
    type: null,
  });
  const [isUploading, setIsUploading] = useState(false);

  const onFileUpload = (type: FileType) => (file: File) => {
    setSelectedFile({ file, type });
  };

  const onFileRemove = () => {
    setSelectedFile({ file: null, type: null });
  };

  const onSaveAndClose = async () => {
    try {
      if (selectedFile.file) {
        setIsUploading(true);
        const documentId = await uploadInvoice(id, selectedFile.file);
        onUploadSuccess(documentId);
        showNotification({
          type: 'success',
          message: 'Invoice uploaded successfully!',
        });
        onDismiss();
      }
    } catch (error) {
      showNotification({
        type: 'error',
        message: error as string,
      });
    } finally {
      setIsUploading(false);
    }
  };

  return (
    <Modal isOpen={true} onDismiss={onDismiss} zIndex={10000}>
      <ModalHeader title="Add invoice" subtitle="Upload a file or add an invoice ID/number" />
      <ModalBody padding="spacing.6">
        {(!selectedFile.file || selectedFile.type === FILE_TYPE.UPLOAD) && (
          <UploadInvoice
            file={selectedFile.file}
            onUpload={onFileUpload(FILE_TYPE.UPLOAD)}
            onRemove={onFileRemove}
          />
        )}
        {!selectedFile.file && (
          <Box
            position="relative"
            display="flex"
            justifyContent="center"
            alignItems="center"
            paddingTop="spacing.7"
            paddingBottom="spacing.7"
          >
            <Divider orientation="horizontal" thickness="thinner" variant="muted" />
            <Box
              display="flex"
              justifyContent="center"
              alignItems="center"
              height="24px"
              width="36px"
              borderRadius="2xlarge"
              backgroundColor="surface.background.gray.subtle"
              position="absolute"
            >
              <Text>OR</Text>
            </Box>
          </Box>
        )}
        {(!selectedFile.file || selectedFile.type === FILE_TYPE.FETCH) && (
          <FetchInvoice
            file={selectedFile.file}
            showNotification={showNotification}
            onUpload={onFileUpload(FILE_TYPE.FETCH)}
            onRemove={onFileRemove}
          />
        )}
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end">
          <Button isLoading={isUploading} onClick={onSaveAndClose} isDisabled={!selectedFile}>
            Save and close
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default AddInvoice;
