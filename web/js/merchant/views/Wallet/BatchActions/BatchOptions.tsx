import { connect } from 'react-redux';
import React from 'react';
import {
  AccountsBatchUpload,
  GCExpiryBatchUpload,
  GCTransferBatchUpload,
  LoadsBatchUpload,
  ReversalsBatchUpload,
} from 'merchant/views/Wallet/BatchActions/BatchUpload';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { Box, Heading, Text } from '@razorpay/blade/components';
import Button from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/components/Button';
import ErrorIcon from 'assets/error_illustration.svg';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';

interface OpenModalArgs {
  size: string;
  component: JSX.Element;
  className?: string;
}

interface CreateBatchOptionsProps {
  openModal: (args: OpenModalArgs) => void;
  batches: any;
}

export const CreateBatchOptions = (props: CreateBatchOptionsProps): JSX.Element => {
  const { openModal, batches } = props;

  const splitz = useSplitzService();
  const isGiftCardsTransferBatchEnabled = isExperimentEnabled(
    splitz?.abExperiments?.gift_cards_transfer,
  );

  const handleBatchUpload = (batchType) => {
    const unprocessedWalletBatches = batches.items.filter(
      (batch) =>
        [
          'create_wallet_accounts',
          'create_wallet_loads',
          'create_wallet_container_loads',
          'create_wallet_user_containers',
          'create_wallet_container_reversals',
          'create_bulk_gift_cards',
          'update_gift_cards_expiry',
          'create_gift_card_transfers',
        ].includes(batch.type) &&
        ['created', 'processing', 'partially_processed'].includes(batch.status),
    );

    if (unprocessedWalletBatches.length >= 3) {
      openModal({
        component: (
          <Box
            padding={'spacing.8'}
            justifyContent={'center'}
            display={'flex'}
            flexDirection={'column'}
          >
            <Heading textAlign="center" marginBottom={'spacing.4'}>
              Maximum number of batch uploads reached
            </Heading>
            <img src={ErrorIcon} alt="error" />
            <Text textAlign="center" marginBottom={'spacing.4'}>
              You can only have 3 unprocessed batch uploads at a time. Please try again once your
              previous batches are processed.
            </Text>
            <Button onClick={closeModal} alignSelf="center" size="large">
              Go Back
            </Button>
          </Box>
        ),
        size: 'small',
      });
      return;
    }

    //If maximum batch upload process is not reached, open the batch upload modal
    if (batchType === 'accountBatchUpload') {
      openModal({ component: <AccountsBatchUpload />, size: 'large' });
      return;
    }
    if (batchType === 'loadsBatchUpload') {
      openModal({ component: <LoadsBatchUpload />, size: 'large' });
      return;
    }
    if (batchType === 'reversalsBatchUpload') {
      openModal({ component: <ReversalsBatchUpload />, size: 'large' });
    }
    if (batchType === 'gcExpiryBatchUpload') {
      openModal({ component: <GCExpiryBatchUpload />, size: 'large' });
    }
    if (batchType === 'gcTransferBatchUpload') {
      openModal({
        component: <GCTransferBatchUpload />,
        size: 'large',
      });
    }
  };

  return (
    <div className="RouteBatch--dropdown">
      <div
        data-testid="batch-type-option"
        className="panel panel-default"
        onClick={() => handleBatchUpload('accountBatchUpload')}
      >
        <div className="panel-body">
          <div className="description">
            <div className="text-primary">
              <strong>Accounts</strong>
            </div>
            <div>Create multiple accounts for your users.</div>
          </div>
          <i className="i-chevron-right pull-right text-primary" />
        </div>
      </div>
      <div
        data-testid="batch-type-option"
        className="panel panel-default"
        onClick={() => handleBatchUpload('loadsBatchUpload')}
      >
        <div className="panel-body">
          <div className="description">
            <div className="text-primary">
              <strong>Loads</strong>
            </div>
            <div>Load money into wallet for multiple customers at once.</div>
          </div>
          <i className="i-chevron-right pull-right text-primary" />
        </div>
      </div>
      <div
        data-testid="batch-type-option"
        className="panel panel-default"
        onClick={() => handleBatchUpload('reversalsBatchUpload')}
      >
        <div className="panel-body">
          <div className="description">
            <div className="text-primary">
              <strong>Reversals</strong>
            </div>
            <div>Reverse wallet loads from multiple customers at once.</div>
          </div>
          <i className="i-chevron-right pull-right text-primary" />
        </div>
      </div>
      <div
        data-testid="batch-type-option"
        className="panel panel-default"
        onClick={() => handleBatchUpload('gcExpiryBatchUpload')}
      >
        <div className="panel-body">
          <div className="description">
            <div className="text-primary">
              <strong>Expiries</strong>
            </div>
            <div>Extend the expiry date for multiple gift cards at once.</div>
          </div>
          <i className="i-chevron-right pull-right text-primary" />
        </div>
      </div>
      {isGiftCardsTransferBatchEnabled ? (
        <div
          data-testid="batch-type-option"
          className="panel panel-default"
          onClick={() => handleBatchUpload('gcTransferBatchUpload')}
        >
          <div className="panel-body">
            <div className="description">
              <div className="text-primary">
                <strong>Transfers</strong>
              </div>
              <div>
                Create gift card transfers for multiple combinations of source and destination
                accounts.
              </div>
            </div>
            <i className="i-chevron-right pull-right text-primary" />
          </div>
        </div>
      ) : null}
    </div>
  );
};

const mapStateToProps = (state) => ({
  batches: state.batches,
});

export default connect(mapStateToProps, { openModal, closeModal })(CreateBatchOptions);
