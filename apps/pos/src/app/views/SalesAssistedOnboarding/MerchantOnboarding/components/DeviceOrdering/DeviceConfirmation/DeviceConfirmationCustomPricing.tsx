import React, { useMemo } from 'react';
import { Box, useToast } from '@razorpay/blade/components';
import { ArrayOfDocumentFieldsUpload, ModularPayload } from 'apps/pos/src/app/types/modular';
import SalesFileUpload from 'apps/pos/src/app/components/SalesFileUpload';
import { FileItem } from 'apps/pos/src/app/types/fileUpload';
import { processFilesForModularSave } from 'apps/pos/src/app/components/SalesFileUpload/helper';
import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';

interface DeviceConfirmationCustomPricingProps {
  defaultValues: ArrayOfDocumentFieldsUpload[];
  handleModularUpdate: (payload: ModularPayload) => void;
}

const DeviceConfirmationCustomPricing = ({
  defaultValues,
  handleModularUpdate,
}: DeviceConfirmationCustomPricingProps): JSX.Element => {
  const [isLoading, setIsLoading] = React.useState(false);
  const toast = useToast();
  const defaultUploadedDocs: FileItem[] = useMemo(
    () =>
      defaultValues?.map((doc) => ({
        fileStoreId: doc.fileStoreId as string,
        name: doc.name as string,
        size: doc.size as number,
      })),
    [defaultValues],
  );

  const handleOnPricingFileUploadChange = (files: FileItem[]) => {
    setIsLoading(true);
    const payload = {
      [MODULAR_DEVICE_FIELDS.DEVICE_CUSTOM_PRICING_DOC]: processFilesForModularSave(files),
      [MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]: () => setIsLoading(false),
    };

    handleModularUpdate(payload);
  };

  return (
    <Box marginBottom="spacing.5">
      <SalesFileUpload
        name="custom_pricing_proof"
        label="Upload custom pricing proof"
        accept=".pdf"
        uploadType="single"
        onChange={handleOnPricingFileUploadChange}
        maxSize={5 * 1024 * 1023}
        maxLimit={1}
        isLoading={isLoading}
        defaultValue={defaultUploadedDocs}
        onError={() => {
          toast.show({
            content: 'Failed to upload pricing proof',
            color: 'negative',
          });
        }}
      />
    </Box>
  );
};

export default DeviceConfirmationCustomPricing;
