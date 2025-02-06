import React from 'react';
import {
  Box,
  CheckCircleIcon,
  Divider,
  DownloadIcon,
  IconButton,
  Text,
  TrashIcon,
} from '@razorpay/blade/components';
import { formatBytes } from './helper';
import DocIcon from 'apps/pos/src/assets/docsIcon.svg';
import PngImageIcon from 'apps/pos/src/assets/pngImageIcon.svg';
import JpegImageIcon from 'apps/pos/src/assets/jpgImageIcon.svg';
import { FileItem } from 'apps/pos/src/app/types/fileUpload';

interface UploadedfileItemProps extends FileItem {
  isDisabled?: boolean;
  onDownloadClick: (fileStoreId: string) => void;
  onRemoveClick: (fileStoreId: string) => void;
}

const getFileUploadIcon = (name: string): string => {
  if (name.endsWith('.jpeg') || name.endsWith('.jpg')) {
    return JpegImageIcon;
  } else if (name.endsWith('.png')) {
    return PngImageIcon;
  }
  return DocIcon;
};

const UploadedfileItem = ({
  fileStoreId,
  name,
  size,
  isDisabled,
  onDownloadClick,
  onRemoveClick,
}: UploadedfileItemProps): JSX.Element => {
  const fileIcon = getFileUploadIcon(name as string);

  return (
    <Box
      display="flex"
      alignItems="center"
      key={fileStoreId}
      padding="spacing.3"
      backgroundColor="surface.background.gray.subtle"
      borderRadius="medium"
      borderWidth="thin"
      borderColor="surface.border.gray.subtle"
      marginBottom="spacing.3"
    >
      <Box marginRight="spacing.3">
        <img src={fileIcon} height="40px" alt="file icon" />
      </Box>
      <Box marginRight="spacing.3">
        <Box display="flex" alignItems="center">
          <Text wordBreak="break-all" truncateAfterLines={1} marginRight="spacing.3">
            {name}
          </Text>
          <Box>
            <CheckCircleIcon color="feedback.icon.positive.intense" size="medium" />
          </Box>
        </Box>
        <Text size="small" color="surface.text.gray.subtle">
          {formatBytes(size)}
        </Text>
      </Box>
      <Box marginLeft="auto" display="flex" alignItems="center">
        <IconButton
          icon={DownloadIcon}
          onClick={() => onDownloadClick(fileStoreId as string)}
          accessibilityLabel="download-file"
        />
        <Divider orientation="vertical" marginX="spacing.4" variant="normal" />
        <IconButton
          icon={TrashIcon}
          onClick={() => onRemoveClick(fileStoreId as string)}
          accessibilityLabel="delete-file"
          isDisabled={isDisabled}
        />
      </Box>
    </Box>
  );
};

export default UploadedfileItem;
