import React from 'react';
import { Box } from '@razorpay/blade/components';
import { FileItem } from 'apps/pos/src/app/types/fileUpload';
import SalesFileUpload from 'apps/pos/src/app/components/SalesFileUpload';

interface PosAgreementUploadProps {
  merchantId: string;
  name: string;
  accept: string;
  label: string;
  uploadType: 'single' | 'multiple';
  error?: string;
  isLoading?: boolean;
  isDisabled?: boolean;
  defaultValue?: FileItem[];
  onChange: (files: FileItem[]) => void;
  onError?: () => void;
  maxSize: number;
  maxLimit: number;
}

const PosAgreementUpload = ({
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
  onChange,
}: PosAgreementUploadProps) => {
  return (
    <Box marginBottom="spacing.5">
      <SalesFileUpload
        merchantId={merchantId}
        name={name}
        label={label}
        accept={accept}
        uploadType={uploadType}
        error={error}
        isLoading={isLoading}
        isDisabled={isDisabled}
        maxLimit={maxLimit}
        maxSize={maxSize}
        defaultValue={defaultValue}
        onChange={onChange}
      />
    </Box>
  );
};

export default PosAgreementUpload;
