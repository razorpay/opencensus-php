import { connect } from 'react-redux';
import React from 'react';

import {
  AccountsBatchUpload,
  LoadsBatchUpload,
} from 'merchant/views/Wallet/BatchActions/BatchUpload';
import { openModal } from 'merchant_common/reducers/modals';

interface OpenModalArgs {
  size: string;
  component: JSX.Element;
  className?: string;
}

interface CreateBatchOptionsProps {
  openModal: (args: OpenModalArgs) => void;
}

export const CreateBatchOptions = (props: CreateBatchOptionsProps): JSX.Element => {
  const { openModal } = props;

  return (
    <div className="RouteBatch--dropdown">
      <div
        data-testid="batch-type-option"
        className="panel panel-default"
        onClick={() => openModal({ component: <AccountsBatchUpload />, size: 'large' })}
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
        style={{ marginBottom: 0 }}
        onClick={() =>
          openModal({
            component: <LoadsBatchUpload />,
            size: 'large',
          })
        }
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
    </div>
  );
};

export default connect(null, { openModal })(CreateBatchOptions);
