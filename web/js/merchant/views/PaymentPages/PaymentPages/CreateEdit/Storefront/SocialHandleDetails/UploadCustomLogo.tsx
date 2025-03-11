import React from 'react';
import {
  Box,
  CheckCircleIcon,
  IconButton,
  Text,
  TrashIcon,
  FileUpload,
  BladeFile,
} from '@razorpay/blade/components';
import { convertFileToBase64, validateFile } from '../utils';
import { showNotification } from 'merchant_common/reducers/notifications';

export interface UploadCustomLogoProps {
  onImageChange?: (file: File | null, name?: string) => void;
  uploadedFile: File | null;
  setUploadedFile: React.Dispatch<React.SetStateAction<File | null>>;
  uploadedLogo: string;
  setUploadedLogo: React.Dispatch<React.SetStateAction<string>>;
}

const UploadCustomLogo: React.FC<UploadCustomLogoProps> = ({
  setUploadedFile,
  uploadedFile,
  uploadedLogo,
  setUploadedLogo,
}) => {
  const [localFile, setLocalFile] = React.useState<BladeFile | null>(uploadedFile);
  const onImageUpload = async ({ fileList }) => {
    const file = fileList[0];
    const validationError = await validateFile(file || null);

    if (validationError) {
      setUploadedFile(null);
      showNotification({ type: 'error', message: validationError });
      return;
    }

    const base64Url = await convertFileToBase64(file);
    setUploadedLogo(base64Url);
    setUploadedFile(file);
    setLocalFile(file);
  };

  const handleRemove = () => {
    setUploadedFile(null);
    setUploadedLogo('');
    setLocalFile(null);
  };

  return (
    <>
      {uploadedLogo?.length > 0 ? (
        <Box width="100%">
          <Text
            weight="semibold"
            size="medium"
            color="surface.text.gray.muted"
            marginBottom="spacing.3"
          >
            Upload a thumbnail image
          </Text>
          <Box
            borderWidth="thin"
            borderColor="surface.border.primary.muted"
            borderRadius="small"
            height="54px"
            display="flex"
            alignItems="center"
            padding="spacing.3"
            justifyContent="space-between"
          >
            <Box display="flex" justifyContent="center" alignItems="center" gap="spacing.4">
              <img src={uploadedLogo} alt="uploaded-svg" width={38} height={38} />
              <Box width="240px">
                <Box display="flex" alignItems="center" gap="spacing.3">
                  <Text
                    weight="medium"
                    size="small"
                    color="surface.text.gray.subtle"
                    wordBreak="break-word"
                    truncateAfterLines={1}
                  >
                    {uploadedFile?.name}
                  </Text>
                  <CheckCircleIcon size="medium" color="interactive.icon.positive.normal" />
                </Box>

                <Text
                  weight="medium"
                  size="small"
                  color="surface.text.gray.subtle"
                  wordBreak="break-word"
                  truncateAfterLines={1}
                >
                  {uploadedFile?.size ? (uploadedFile.size / 1024).toFixed(2) : '0.00'} KB
                </Text>
              </Box>
            </Box>
            <IconButton
              icon={() => <TrashIcon size="large" color="interactive.icon.gray.muted" />}
              onClick={handleRemove}
              accessibilityLabel="delete-logo"
            />
          </Box>

          <Text
            variant="caption"
            weight="medium"
            size="small"
            color="surface.text.gray.muted"
            marginTop="spacing.3"
          >
            Your image must be in a 1:1 ratio with a maximum size of 1MB. Supported formats: PNG,
            JPEG, JPG.
          </Text>
        </Box>
      ) : (
        <Box width="100%">
          <FileUpload
            accept=".jpg, .jpeg, .png"
            fileList={localFile ? [localFile] : []}
            helpText="Your image must be in a 1:1 ratio with a maximum size of 1MB. Supported formats: PNG, JPEG, JPG."
            label="Upload a thumbnail image"
            maxCount={1}
            onChange={onImageUpload}
            uploadType="single"
          />
        </Box>
      )}
    </>
  );
};

export default UploadCustomLogo;
