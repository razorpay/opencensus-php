import React, { useMemo } from 'react';
import { useParams } from 'react-router-dom';
import { Box, useToast } from '@razorpay/blade/components';
import { ArrayOfDocumentFieldsUpload, ModularPayload } from 'apps/pos/src/app/types/modular';
import SalesFileUpload from 'apps/pos/src/app/components/SalesFileUpload';
import { FileItem } from 'apps/pos/src/app/types/fileUpload';
import { processFilesForModularSave } from 'apps/pos/src/app/components/SalesFileUpload/helper';
import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

interface DeviceConfirmationCustomPricingProps {
  defaultValues: ArrayOfDocumentFieldsUpload[];
  handleModularUpdate: (payload: ModularPayload) => void;
  isDisabled?: boolean;
  error?: string;
}

const DeviceConfirmationCustomPricing = ({
  defaultValues,
  handleModularUpdate,
  isDisabled,
  error,
}: DeviceConfirmationCustomPricingProps): JSX.Element => {
  const [isLoading, setIsLoading] = React.useState(false);
  const toast = useToast();
  const { id } = useParams();
  const defaultUploadedDocs: FileItem[] = useMemo(
    () =>
      defaultValues?.map((doc) => ({
        fileStoreId: doc.fileStoreId as string,
        name: doc.name as string,
        size: doc.size as number,
      })),
    [defaultValues],
  );

  // gets called on mutation success //
  const handleOnPricingFileUploadChange = (files: FileItem[]) => {
    setIsLoading(true);
    const payload = {
      [MODULAR_DEVICE_FIELDS.DEVICE_CUSTOM_PRICING_DOC]: processFilesForModularSave(files),
      [MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]: () => setIsLoading(false),
    };

    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.LINK,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Upload - Custom Pricing Proof',
        section: 'Order Confirmation',
        subSection: 'POS Product Confirmation',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ORDER_CONFIRMATION,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.POS_PRODUCT_CONFIRMATION,
      },
    });

    handleModularUpdate(payload);
  };

  return (
    <Box marginBottom="spacing.5">
      <SalesFileUpload
        merchantId={id}
        name={MODULAR_DEVICE_FIELDS.DEVICE_CUSTOM_RATES_DOCUMENTS}
        label="Upload custom pricing proof"
        accept=".pdf, .png, .jpeg, .jpg"
        uploadType="single"
        onChange={handleOnPricingFileUploadChange}
        maxSize={5 * 1024 * 1023}
        maxLimit={1}
        isLoading={isLoading}
        defaultValue={defaultUploadedDocs}
        error={error}
        isDisabled={isDisabled}
        onError={() => {
          toast.show({
            content: 'Failed to upload pricing proof',
            color: 'negative',
            autoDismiss: true,
          });
        }}
      />
    </Box>
  );
};

export default DeviceConfirmationCustomPricing;
