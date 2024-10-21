import React from 'react';
import { Box, FileUpload, Text, List, ListItem } from '@razorpay/blade/components';

import { FileInputWrapper } from './styled';
import { UploadInvoiceType } from './types';
import { MAX_FILE_SIZE } from './constants';
import { previewFile } from './utils';

const UploadInvoice = ({
  onUpload,
  onRemove,
  showNotification,
  file,
}: UploadInvoiceType): JSX.Element => {
  const onPreviewFile = ({ file }: { file: File }) => {
    try {
      previewFile(file);
    } catch {
      showNotification({
        type: 'error',
        message: 'Something went wrong. Please try again!',
      });
    }
  };

  return (
    <Box display="flex" flexDirection="column">
      <Text marginBottom="spacing.4" color="surface.text.gray.normal" weight="semibold">
        Upload invoice file
      </Text>
      <List size="small" variant="unordered" marginBottom="spacing.3">
        <ListItem>Maximum file size {MAX_FILE_SIZE.toString()}MB</ListItem>
        <ListItem>Only PDF, JPEG, or JPG formats accepted</ListItem>
      </List>
      <Box>
        <FileInputWrapper>
          <FileUpload
            uploadType="single"
            fileList={file ? [file] : undefined}
            label=""
            accept=".jpg, .jpeg, .pdf"
            maxSize={MAX_FILE_SIZE * 100000}
            onChange={({ fileList }) => {
              onUpload(fileList[0]);
            }}
            onDrop={({ fileList }) => {
              onUpload(fileList[0]);
            }}
            onPreview={onPreviewFile}
            onRemove={onRemove}
          />
        </FileInputWrapper>
      </Box>
    </Box>
  );
};

export default UploadInvoice;
