import React, { useState, useRef, RefObject } from 'react';
import {
  Box,
  Button,
  FileIcon,
  Text,
  Link,
  CloseIcon,
  IconButton,
  InfoIcon,
} from '@razorpay/blade/components';

import { SAMPLE_XML_FILE } from './constants';
import { UploadButtonProps } from './types';

const UploadButton = ({ value, fileError, onFileChange }: UploadButtonProps) => {
  const [selectedFile, setSelectedFile] = useState<File | null>(value);
  const [error, setError] = useState<string | null>(null);
  const inputRef: RefObject<HTMLInputElement> = useRef(null);

  const onUploadClick = () => {
    inputRef?.current?.click();
  };

  const onChange = ({ target: { files } }) => {
    const fileSize = files[0].size / 1024 / 1024;
    if (fileSize <= 5) {
      onFileChange(files[0]);
      setSelectedFile(files[0]);
    } else {
      setError('File size cannot be greater than 5 MB');
    }
  };

  const onCloseClick = () => {
    onFileChange(null);
    setSelectedFile(null);
  };

  const onDownloadSampleFile = () => {
    window.open(SAMPLE_XML_FILE, '_blank');
  };

  return (
    <Box display="flex" flexDirection={{ base: 'column', m: 'row' }}>
      <Box
        width="120px"
        marginRight="spacing.5"
        marginTop="spacing.3"
        marginBottom={{ base: 'spacing.3', m: 'spacing.0' }}
      >
        <Text weight="bold" type="subdued">
          Value(s)
        </Text>
      </Box>
      <Box
        display="flex"
        flexDirection="column"
        alignItems="center"
        flex="1"
        marginBottom="spacing.5"
      >
        {!selectedFile ? (
          <Box marginBottom="spacing.3" width="100%">
            <Box display="none">
              <input
                ref={inputRef}
                type="file"
                accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                data-testid="file-input"
                onChange={onChange}
              />
            </Box>
            <Button variant="secondary" icon={FileIcon} onClick={onUploadClick} isFullWidth>
              Upload XLS or XLSV of list items (less than 5 MB in size)
            </Button>
            {(error || fileError) && (
              <Box display="flex" flexDirection="row" alignItems="center" marginTop="spacing.2">
                <InfoIcon
                  size="small"
                  color="feedback.negative.action.icon.primary.active.lowContrast"
                  marginRight="spacing.2"
                />
                <Text
                  variant="caption"
                  color="feedback.negative.action.text.primary.active.lowContrast"
                >
                  {error || fileError}
                </Text>
              </Box>
            )}
          </Box>
        ) : (
          <Box
            display="flex"
            flexDirection="row"
            alignItems="center"
            padding="spacing.3"
            marginBottom="spacing.3"
            width="100%"
            backgroundColor="brand.gray.300.lowContrast"
          >
            <FileIcon />
            <Box flex="1" marginLeft="spacing.4">
              <Text>{selectedFile.name}</Text>
            </Box>
            <IconButton onClick={onCloseClick} icon={CloseIcon} accessibilityLabel="Close" />
          </Box>
        )}
        <Text weight="bold">
          Download <Link onClick={onDownloadSampleFile}>sample XLS file</Link>
        </Text>
      </Box>
    </Box>
  );
};

export default UploadButton;
