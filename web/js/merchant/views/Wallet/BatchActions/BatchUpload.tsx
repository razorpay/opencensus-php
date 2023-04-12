import React from 'react';
import { connect } from 'react-redux';

import BatchUpload from 'merchant/containers/BatchNew/Upload';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import {
  createWalletAccountsBatch,
  validateWalletAccountsBatch,
  createWalletLoadsBatch,
  validateWalletLoadsBatch,
} from 'merchant/reducers/batches';
import styled from 'styled-components';
const gaEvents = setGaTrack('Dashboard - Route - BU');

export const DISPLAY_MESSAGES = {
  process: 'The file is being processed. Please wait as this may take some time.',
  success: 'The file has been processed successfully.',
  error: 'There was an error while processing the file. Please try again after some time.',
  exceed: 'The file size exceeds the maximum size limit. Please upload a smaller file.',
};

interface InfoProps {
  sampleUrl?: string;
  points: string[];
}

const OrderedList = styled.ol`
  padding-inline-start: 24px;
`;

export const InfoComponent = ({ sampleUrl, points = [] }: InfoProps): JSX.Element => (
  <div className="modal-info">
    <h5 className="modal-info-heading">KEEP IN MIND</h5>
    <OrderedList className="validate-modal-ul">
      {sampleUrl && (
        <li>
          File should follow the template format. Download{' '}
          <a className="btn-link" href={sampleUrl}>
            <strong>sample file</strong>
          </a>{' '}
          for the template.
        </li>
      )}
      {points.map((point, index) => (
        <li key={index}>{point}</li>
      ))}
    </OrderedList>
  </div>
);

const baseBatchUpload =
  ({
    batchType,
    title,
    docUrl,
    points = [],
  }: {
    batchType: string;
    title: string;
    docUrl?: string;
    points: string[];
  }) =>
  ({ createBatch, validateBatch }) =>
    (
      <BatchUpload
        title={title}
        docUrl={docUrl}
        displayMsgs={DISPLAY_MESSAGES}
        acceptFileInfo={['csv']}
        createBatch={createBatch}
        validateBatch={validateBatch}
        gaEvents={gaEvents}
        maxRows={50000}
        maxFileSize={10485760} // 10 MB
        batchType={batchType}
        validateModalInfo={
          <InfoComponent sampleUrl={`/files/sample_${batchType}.xlsx`} points={points} />
        }
        sampleUrl={`/files/sample_${batchType}.xlsx`}
      />
    );

export const AccountsBatchUpload = connect(null, {
  createBatch: createWalletAccountsBatch,
  validateBatch: validateWalletAccountsBatch,
})(
  baseBatchUpload({
    batchType: 'create_wallet_accounts',
    title: 'Batch Accounts Upload',
    points: [
      'partner_customer_id, contact should be unique for each account.',
      'The number of rows in the file should not exceed 50 thousand.',
    ],
  }),
);

export const LoadsBatchUpload = connect(null, {
  createBatch: createWalletLoadsBatch,
  validateBatch: validateWalletLoadsBatch,
})(
  baseBatchUpload({
    batchType: 'create_wallet_loads',
    title: 'Create Batch Loads',
    points: [
      'Ensure you have enough balance in your escrow account, loads would fail incase balance is insufficient.',
      'Category can have values of topup, cashback, refund.',
    ],
  }),
);
