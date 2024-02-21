import React from 'react';

import BatchUpload from 'merchant/containers/BatchNew/Upload';
import setGaTrack from 'merchant/containers/BatchNew/ga';

import { InfoComponent } from './InfoComponent';
import { DISPLAY_MESSAGES } from './constants';
import { BatchUploadContainer } from './styled';

const gaEvents = setGaTrack('Dashboard - Route - BU');

interface BatchUploadWrapperProps {
  batchType: string;
  title: string;
  docUrl?: string;
  points: string[];
  createBatch: () => void;
  validateBatch: () => void;
  component?: JSX.Element;
  onSuccess: () => void;
}

export const BatchUploadWrapper = ({
  batchType,
  title,
  docUrl,
  points = [],
  component: Component,
  createBatch,
  validateBatch,
  onSuccess,
}: BatchUploadWrapperProps) => {
  return (
    <BatchUploadContainer>
      <BatchUpload
        acceptFileInfo={['csv']}
        title={title}
        validateBatch={validateBatch}
        displayMsgs={DISPLAY_MESSAGES}
        validateModalInfo={<InfoComponent sampleUrl={docUrl} points={points} />}
        maxFileSize={52428800} // 50 MB
        batchListClass="rto-history-upload"
        docUrl={docUrl}
        createBatch={createBatch}
        gaEvents={gaEvents}
        batchType={batchType}
        sampleUrl={docUrl}
        component={Component}
        onSuccess={onSuccess}
        successStata
      />
    </BatchUploadContainer>
  );
};
