import React, { useCallback } from 'react';
import { Box } from '@razorpay/blade/components';
import { FileItem } from 'apps/pos/src/app/types/fileUpload';
import SalesFileUpload from 'apps/pos/src/app/components/SalesFileUpload';
import { trackEvent } from 'apps/pos/src/services/analytics';
import {
  ANALYTICS_ACTIONS,
  ANALYTICS_EVENTS,
  L1_FUNNEL_STAGE,
  L2_FUNNEL_STAGE,
  STATUS,
} from 'apps/pos/src/services/analytics/types';

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
  const handleOnUpload = useCallback(() => {
    trackEvent({
      eventName: ANALYTICS_EVENTS.DOCUMENT_UPLOAD,
      action: ANALYTICS_ACTIONS.RESPONSE_RECEIVED,
      properties: {
        label: 'TnC & Pricing Agreement',
        l1FunnelStage: L1_FUNNEL_STAGE.AGREEMENT_SIGNING,
        l2FunnelStage: L2_FUNNEL_STAGE.OFFLINE_METHOD,
        documentUploaded: 'TnC & Pricing Agreement',
        status: STATUS.SUCCESS,
      },
    });
  }, []);

  const handleOnError = useCallback(() => {
    trackEvent({
      eventName: ANALYTICS_EVENTS.DOCUMENT_UPLOAD,
      action: ANALYTICS_ACTIONS.RESPONSE_RECEIVED,
      properties: {
        label: 'TnC & Pricing Agreement',
        l1FunnelStage: L1_FUNNEL_STAGE.AGREEMENT_SIGNING,
        l2FunnelStage: L2_FUNNEL_STAGE.OFFLINE_METHOD,
        documentUploaded: 'TnC & Pricing Agreement',
        status: STATUS.FAILURE,
      },
    });
  }, []);

  const handleOnRemove = useCallback(() => {
    trackEvent({
      eventName: ANALYTICS_EVENTS.ICON,
      action: ANALYTICS_ACTIONS.CLICKED,
      properties: {
        type: 'Delete icon',
        section: 'Agreement Signing',
        subSection: 'Offline Method',
        l1FunnelStage: L1_FUNNEL_STAGE.AGREEMENT_SIGNING,
        l2FunnelStage: L2_FUNNEL_STAGE.OFFLINE_METHOD,
      },
    });
  }, []);

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
        onUpload={handleOnUpload}
        onError={handleOnError}
        onRemove={handleOnRemove}
      />
    </Box>
  );
};

export default PosAgreementUpload;
