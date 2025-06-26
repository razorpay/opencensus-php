import React, { useMemo, useState } from 'react';
import { connect } from 'react-redux';

import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import {
  createWalletAccountsBatch,
  validateWalletAccountsBatch,
  createWalletLoadsBatch,
  validateWalletLoadsBatch,
  validateContainerLoadsBatch,
  createContainerLoadsBatch,
  validateUsersBatch,
  createUsersBatch,
  createReversalBatch,
  validateReversalBatch,
  createGiftCardsBatch,
  validateGiftCardsBatch,
  createGCExpiryBatch,
  validateGCExpiryBatch,
  createGCTransferBatch,
  validateGCTransferBatch,
  createGiftCardCancellationBatch,
  validateGiftCardCancellationBatch,
} from 'merchant/reducers/batches';
import { BatchUploadWrapper } from 'merchant/views/Wallet/BatchActions/components/BatchUploadWrapper';
import InputSelector from 'merchant/views/Wallet/BatchActions/components/InputSelector';
import {
  ACCOUNT_TYPES,
  BATCH_TYPES,
  CREATE_ACCOUNTS_OPTIONS,
  CREATE_LOADS_OPTIONS,
  LOAD_TYPES,
  REVERSAL_TYPES_OPTIONS,
  REVERSAL_TYPES,
  REVERSAL_TYPE_MESSAGE,
} from 'merchant/views/Wallet/BatchActions/constants';

import { getSelectedBatchType } from './utils';

export const AccountsBatchUpload = connect(null, {
  createBatch: createWalletAccountsBatch as () => void,
  validateBatch: validateWalletAccountsBatch as () => void,
  validateUserLoadsBatch: validateUsersBatch as () => void,
  createUserLoadsBatch: createUsersBatch as () => void,
  createGiftCardsBatch: createGiftCardsBatch as () => void,
  validateGiftCardsBatch: validateGiftCardsBatch as () => void,
})(
  ({
    createBatch,
    validateBatch,
    validateUserLoadsBatch,
    createUserLoadsBatch,
    createGiftCardsBatch,
    validateGiftCardsBatch,
  }) => {
    const splitz = useSplitzService();
    const [provider, setProvider] = useState<string>('accounts');

    const isCreateGiftCardBatchEnabled = isExperimentEnabled(
      splitz?.abExperiments?.create_bulk_gift_cards,
    );

    const createAccountOptions = useMemo(() => {
      if (isCreateGiftCardBatchEnabled) {
        return CREATE_ACCOUNTS_OPTIONS;
      } else {
        return CREATE_ACCOUNTS_OPTIONS.filter(
          (options) => options?.name !== ACCOUNT_TYPES.GIFT_CARDS,
        );
      }
    }, [isCreateGiftCardBatchEnabled]);

    const selectedBatch = useMemo(() => {
      const batchType = getSelectedBatchType(provider);

      let selectedValidateBatch, selectedCreateBatch;
      if (provider === ACCOUNT_TYPES.ACCOUNT) {
        selectedValidateBatch = validateBatch;
        selectedCreateBatch = createBatch;
      } else if (provider === ACCOUNT_TYPES.CONTAINER) {
        selectedValidateBatch = validateUserLoadsBatch;
        selectedCreateBatch = createUserLoadsBatch;
      } else {
        selectedValidateBatch = validateGiftCardsBatch;
        selectedCreateBatch = createGiftCardsBatch;
      }

      const sampleUrl = `/files/sample_${batchType}.xlsx`;

      return {
        selectedValidateBatch,
        selectedCreateBatch,
        batchType,
        sampleUrl,
      };
    }, [
      createBatch,
      createGiftCardsBatch,
      createUserLoadsBatch,
      provider,
      validateBatch,
      validateGiftCardsBatch,
      validateUserLoadsBatch,
    ]);

    return (
      <BatchUploadWrapper
        batchType={selectedBatch.batchType}
        docUrl={selectedBatch.sampleUrl}
        title="Batch Accounts Upload"
        points={[
          'partner_customer_id, contact should be unique for each account.',
          'The number of rows in the file should not exceed 50 thousand.',
        ]}
        createBatch={selectedBatch.selectedCreateBatch}
        validateBatch={selectedBatch.selectedValidateBatch}
        component={<InputSelector options={createAccountOptions} setInput={setProvider} />}
      />
    );
  },
);

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

export const ReversalsBatchUpload = connect(null, {
  createBatch: createReversalBatch as () => void,
  validateBatch: validateReversalBatch as () => void,
  createGiftCardCancellationBatch: createGiftCardCancellationBatch,
  validateGiftCardCancellationBatch: validateGiftCardCancellationBatch,
})(
  ({
    createBatch,
    validateBatch,
    createGiftCardCancellationBatch,
    validateGiftCardCancellationBatch,
  }) => {
    const [provider, setProvider] = useState<string>(REVERSAL_TYPES.WALLET_LOAD);

    const splitz = useSplitzService();
    const isCreateGiftCardBatchEnabled = isExperimentEnabled(
      splitz?.abExperiments?.create_bulk_gift_cards,
    );

    const reversalOptions = () => {
      if (isCreateGiftCardBatchEnabled) {
        return REVERSAL_TYPES_OPTIONS;
      } else {
        return REVERSAL_TYPES_OPTIONS.filter(
          (options) => options?.name !== REVERSAL_TYPES.GIFT_CARD,
        );
      }
    };

    const selectedBatch =
      provider === REVERSAL_TYPES.WALLET_LOAD
        ? {
            selectedValidateBatch: validateBatch,
            selectedCreateBatch: createBatch,
            batchType: provider,
            sampleUrl: '/files/sample_create_wallet_container_reversals.xlsx',
            title: 'Create Batch Reversals',
            points: REVERSAL_TYPE_MESSAGE[provider],
          }
        : {
            selectedValidateBatch: validateGiftCardCancellationBatch,
            selectedCreateBatch: createGiftCardCancellationBatch,
            batchType: provider,
            sampleUrl: '/files/sample_cancel_gift_card.xlsx',
            title: 'Create Batch Gift Card Cancellations',
            points: REVERSAL_TYPE_MESSAGE[provider],
          };

    return (
      <BatchUploadWrapper
        batchType={selectedBatch.batchType}
        docUrl={selectedBatch.sampleUrl}
        title={selectedBatch.title}
        points={selectedBatch.points}
        component={
          <InputSelector
            options={reversalOptions()}
            setInput={setProvider}
            defaultValue={provider}
          />
        }
        createBatch={selectedBatch.selectedCreateBatch}
        validateBatch={selectedBatch.selectedValidateBatch}
      />
    );
  },
);

export const GCExpiryBatchUpload = connect(null, {
  createBatch: createGCExpiryBatch as () => void,
  validateBatch: validateGCExpiryBatch as () => void,
})(({ createBatch, validateBatch }) => {
  const sampleUrl = `/files/sample_extend_gift_card_expiry.xlsx`;

  return (
    <BatchUploadWrapper
      batchType={BATCH_TYPES.UPDATE_GIFT_CARD_EXPIRY}
      docUrl={sampleUrl}
      title="Extend Gift Card Expiry"
      points={[
        'Either the gift card ID or the gift card number must be present for a successful update.',
        'Contact (email/phone) of the customer making the extension request, link to the support ticket with customer request details, timestamp of the request and source of the request (email, social media, chat etc.) are mandatory parameters.',
      ]}
      createBatch={createBatch}
      validateBatch={validateBatch}
    />
  );
});

export const GCTransferBatchUpload = connect(null, {
  createBatch: createGCTransferBatch as () => void,
  validateBatch: validateGCTransferBatch as () => void,
})(({ createBatch, validateBatch }) => {
  const sampleUrl = `/files/sample_gift_card_transfers.xlsx`;

  return (
    <BatchUploadWrapper
      batchType={BATCH_TYPES.CREATE_GIFT_CARD_TRANSFERS}
      docUrl={sampleUrl}
      title="Create Batch Transfers"
      points={[
        'This feature can work only if the source user is blocked using Razorpay close user API',
        'You can upload only 10000 combination of source and destination accounts',
      ]}
      createBatch={createBatch}
      validateBatch={validateBatch}
    />
  );
});
