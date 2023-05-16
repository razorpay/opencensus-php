import React from 'react';

import { BatchUploadContainer } from 'merchant/views/Wallet/BatchActions/styled';
import BatchUpload from 'merchant/containers/BatchNew/Upload';

import { DISPLAY_MESSAGES } from 'merchant/views/Wallet/BatchActions/constants';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import { InfoComponent } from 'merchant/views/Wallet/BatchActions/components/InfoComponent';

const gaEvents = setGaTrack('Dashboard - Route - BU');

interface BatchUploadWrapperProps {
  batchType: string;
  title: string;
  docUrl?: string;
  points: string[];
  createBatch: any;
  validateBatch: any;
  component?: JSX.Element;
}

export const BatchUploadWrapper = ({
  batchType,
  title,
  docUrl,
  points = [],
  component: Component,
  createBatch,
  validateBatch,
}: BatchUploadWrapperProps) => {
  return (
    <BatchUploadContainer>
      <BatchUpload
        acceptFileInfo={['csv']}
        title={title}
        validateBatch={validateBatch}
        displayMsgs={DISPLAY_MESSAGES}
        validateModalInfo={<InfoComponent sampleUrl={docUrl} points={points} />}
        maxFileSize={10485760} // 10 MB
        batchListClass="rto-history-upload"
        docUrl={docUrl}
        createBatch={createBatch}
        gaEvents={gaEvents}
        maxRows={50000}
        batchType={batchType}
        sampleUrl={docUrl}
        component={Component}
      />
    </BatchUploadContainer>
  );
};
