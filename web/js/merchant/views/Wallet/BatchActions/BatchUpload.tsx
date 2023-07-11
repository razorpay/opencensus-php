import React, { useMemo, useState } from 'react';
import { connect } from 'react-redux';

import {
  createWalletAccountsBatch,
  validateWalletAccountsBatch,
  createWalletLoadsBatch,
  validateWalletLoadsBatch,
  validateContainerLoadsBatch,
  createContainerLoadsBatch,
  validateUsersBatch,
  createUsersBatch,
} from 'merchant/reducers/batches';
import InputSelector from 'merchant/views/Wallet/BatchActions/components/InputSelector';
import { BatchUploadWrapper } from 'merchant/views/Wallet/BatchActions/components/BatchUploadWrapper';
import {
  BATCH_TYPES,
  CREATE_ACCOUNTS_OPTIONS,
  CREATE_LOADS_OPTIONS,
  LOAD_TYPES,
} from 'merchant/views/Wallet/BatchActions/constants';

export const AccountsBatchUpload = connect(null, {
  createBatch: createWalletAccountsBatch as () => void,
  validateBatch: validateWalletAccountsBatch as () => void,
  validateUserLoadsBatch: validateUsersBatch as () => void,
  createUserLoadsBatch: createUsersBatch as () => void,
})(({ createBatch, validateBatch, validateUserLoadsBatch, createUserLoadsBatch }) => {
  const [provider, setProvider] = useState<string>('accounts');

  const selectedBatch = useMemo(() => {
    const batchType =
      provider === LOAD_TYPES.ACCOUNT
        ? BATCH_TYPES.CREATE_WALLET_ACCOUNTS
        : BATCH_TYPES.CREATE_WALLET_USERS_CONTAINERS;

    let selectedValidateBatch, selectedCreateBatch, selectedBatchType;
    if (provider === LOAD_TYPES.ACCOUNT) {
      selectedValidateBatch = validateBatch;
      selectedCreateBatch = createBatch;
      selectedBatchType = BATCH_TYPES.CREATE_WALLET_ACCOUNTS;
    } else {
      selectedValidateBatch = validateUserLoadsBatch;
      selectedCreateBatch = createUserLoadsBatch;
      selectedBatchType = batchType;
    }

    const sampleUrl = `/files/sample_${batchType}.xlsx`;

    return {
      selectedValidateBatch,
      selectedCreateBatch,
      selectedBatchType,
      sampleUrl,
    };
  }, [createBatch, createUserLoadsBatch, provider, validateBatch, validateUserLoadsBatch]);

  return (
    <BatchUploadWrapper
      batchType={selectedBatch.selectedBatchType}
      docUrl={selectedBatch.sampleUrl}
      title="Batch Accounts Upload"
      points={[
        'partner_customer_id, contact should be unique for each account.',
        'The number of rows in the file should not exceed 50 thousand.',
      ]}
      createBatch={selectedBatch.selectedCreateBatch}
      validateBatch={selectedBatch.selectedValidateBatch}
      component={<InputSelector options={CREATE_ACCOUNTS_OPTIONS} setInput={setProvider} />}
    />
  );
});

export const LoadsBatchUpload = connect(null, {
  createBatch: createWalletLoadsBatch as () => void,
  validateBatch: validateWalletLoadsBatch as () => void,
  validateContainerLoadsBatch: validateContainerLoadsBatch as () => void,
  createContainerLoadsBatch: createContainerLoadsBatch as () => void,
})(({ createBatch, validateBatch, createContainerLoadsBatch, validateContainerLoadsBatch }) => {
  const [provider, setProvider] = useState<string>('accounts');

  const selectedBatch = useMemo(() => {
    const batchType =
      provider === LOAD_TYPES.ACCOUNT
        ? BATCH_TYPES.CREATE_WALLET_LOADS
        : BATCH_TYPES.CREATE_WALLET_CONTAINER_LOADS;
    let selectedValidateBatch, selectedCreateBatch, selectedBatchType;
    if (provider === LOAD_TYPES.CONTAINER) {
      selectedValidateBatch = validateContainerLoadsBatch;
      selectedCreateBatch = createContainerLoadsBatch;
      selectedBatchType = BATCH_TYPES.CREATE_WALLET_CONTAINER_LOADS;
    } else {
      selectedValidateBatch = validateBatch;
      selectedCreateBatch = createBatch;
      selectedBatchType = batchType;
    }

    const sampleUrl = `/files/sample_${batchType}.xlsx`;

    return {
      selectedValidateBatch,
      selectedCreateBatch,
      selectedBatchType,
      sampleUrl,
    };
  }, [
    createBatch,
    createContainerLoadsBatch,
    provider,
    validateBatch,
    validateContainerLoadsBatch,
  ]);

  return (
    <BatchUploadWrapper
      batchType={selectedBatch.selectedBatchType}
      docUrl={selectedBatch.sampleUrl}
      title="Create Batch Loads"
      points={[
        'Ensure you have enough balance in your escrow account, loads would fail incase balance is insufficient.',
        'Category can have values of topup, cashback, refund.',
      ]}
      createBatch={selectedBatch.selectedCreateBatch}
      validateBatch={selectedBatch.selectedValidateBatch}
      component={<InputSelector options={CREATE_LOADS_OPTIONS} setInput={setProvider} />}
    />
  );
});
