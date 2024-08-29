import React, { useEffect, useState } from 'react';
import { Box, ProgressBar, Text } from '@razorpay/blade/components';
import { getUser } from 'shell/commonStore';
import { useMutation } from '@tanstack/react-query';
import UploadedfileItem from './UploadedfileItem';
import { formatBytes } from './helper';
import { FileItem } from 'apps/pos/src/app/types/fileUpload';
import {
  UploadFileContainer,
  UploadFileLabel,
} from 'apps/pos/src/app/components/SalesFileUpload/styles';
import { getFile, uploadFileToUFH } from 'apps/pos/src/app/apis/fileUpload';

interface useUfhFileUploadMutationProps {
  name: string;
  files: File[];
  userId: string;
}

interface SalesFileUploadProps {
  name: string;
  accept: string;
  label: string;
  uploadType: 'single' | 'multiple';
  error?: string;
  isLoading?: boolean;
  isDisabled?: boolean;
  defaultValue?: FileItem[];
  onChange: (files: FileItem[]) => void;
  onError?: (errorData: unknown) => void;
  maxSize: number;
  maxLimit: number;
  value?: FileItem[];
  merchantId: string;
}

const SalesFileUpload = ({
  merchantId,
  name,
  label,
  accept,
  uploadType,
  error,
  isLoading,
  isDisabled,
  maxLimit,
  maxSize,
  defaultValue,
  onError,
  onChange,
  value,
}: SalesFileUploadProps): JSX.Element | null => {
  const { user } = getUser() ?? {};
  const [internalError, setInternalError] = useState<string | null>(null);
  const [fileItemList, setFileItemList] = useState<FileItem[]>(defaultValue ?? []);

  const {
    mutate,
    isLoading: isUfhUploadLoading,
    isError,
  } = useMutation<FileItem[], unknown, useUfhFileUploadMutationProps>({
    mutationFn: async ({ name, files, userId }) => {
      const fileUploadObject = files.map((file) => ({
        name: file.name,
        size: file.size,
        promise: uploadFileToUFH({ file, name, userId, merchantId }),
      }));

      const ufhResponseItems = await Promise.all(fileUploadObject.map((file) => file.promise));
      return fileUploadObject.map((item, index) => ({
        fileStoreId: (ufhResponseItems?.[index]?.data?.[name]?.file_id as string).replace(
          /file_/g,
          '',
        ),
        name: item.name,
        size: item.size,
      }));
    },
    onSuccess: (data) => {
      const newFiles = [...fileItemList, ...data];
      setFileItemList(newFiles);
      onChange?.(newFiles);
    },
    onError: (errorData) => {
      onError?.(errorData);
    },
  });

  const handleOnChange = async (event) => {
    const newFiles = Array.from(event.target.files as File[]);
    if (newFiles.length + fileItemList.length > maxLimit) {
      setInternalError(`Cannot upload more than ${maxLimit} files!`);
      event.target.value = '';
      return;
    }

    if (newFiles.some((file) => file.size > maxSize)) {
      const maxSizeToReadAble = formatBytes(maxSize);
      setInternalError(`File size should not exceed ${maxSizeToReadAble}!`);
      return;
    }
    setInternalError(null);
    mutate({ name, files: newFiles, userId: user?.id as string });
    event.target.value = '';
  };

  const handleOnRemove = (fileStoreId: string) => {
    const newFiles = fileItemList.filter((item) => item.fileStoreId !== fileStoreId);
    setFileItemList(newFiles);
    onChange?.(newFiles);
  };

  const handleFileDownloadClick = async (fileStoreId: string) => {
    setInternalError(null);
    try {
      const fileMetaData = await getFile(fileStoreId);
      window.open(fileMetaData.data?.signed_url as string, '_blank');
    } catch {
      setInternalError('Some error occurred while downloading file!');
    }
  };

  const isShowFileUploadBtn =
    uploadType === 'multiple' || (uploadType === 'single' && fileItemList.length === 0);

  useEffect(() => {
    if (!value) return;
    if (value.length > 0 && value.some((item) => !item.fileStoreId)) throw new Error();
    setFileItemList(value);
  }, [value]);

  return (
    <Box marginBottom="spacing.5">
      <Text marginBottom="spacing.3">{label}</Text>
      {isShowFileUploadBtn ? (
        <UploadFileLabel htmlFor="file" className="file-upload-label" isDisabled={isDisabled}>
          <UploadFileContainer>
            <input
              type="file"
              id="file"
              aria-label="file-upload-input"
              style={{ display: 'none' }}
              onChange={handleOnChange}
              accept={accept}
              disabled={isLoading || isUfhUploadLoading || isDisabled}
              {...(uploadType === 'multiple' && maxLimit > 1 ? { multiple: true } : {})}
            />
            <Text color="surface.text.staticBlack.subtle">Choose Files</Text>
          </UploadFileContainer>
        </UploadFileLabel>
      ) : null}
      {!!(isError || error || internalError) ? (
        <Text
          color="feedback.text.negative.intense"
          marginBottom="spacing.3"
          size="small"
          variant="caption"
        >
          {error || internalError || 'Some error occurred while uploading file!'}
        </Text>
      ) : null}
      {isLoading || isUfhUploadLoading ? (
        <ProgressBar
          isIndeterminate
          label=""
          marginBottom="spacing.3"
          accessibilityLabel="file-upload-loader"
        />
      ) : null}
      {fileItemList.map(({ fileStoreId, name, size }) => (
        <UploadedfileItem
          key={fileStoreId}
          name={name}
          size={size}
          fileStoreId={fileStoreId}
          onDownloadClick={handleFileDownloadClick}
          onRemoveClick={handleOnRemove}
          isDisabled={isDisabled}
        />
      ))}
    </Box>
  );
};

export default SalesFileUpload;
