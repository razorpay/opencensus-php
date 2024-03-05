// TODO: Fix the imports, currently out of scope
// @ts-nocheck
import React from 'react';
import { connect } from 'react-redux';
import { AnyAction, Dispatch, bindActionCreators } from 'redux';
import NewPaymentTransfer from 'apps/self-serve/src/App/Marketplace/Transfers/New';
import { fetchTransfersFn } from 'apps/self-serve/src/App/Transactions/model';
import ShowWhen from 'shell/components/ShowWhen';
import { fetchTransfers as fetchTransfersAction } from 'merchant/reducers/payments/details';
import { useNavigate } from 'react-router-dom';
import { IPaymentTransferNew } from './types';

const PaymentTransferNew = ({ fetchTransfers, id, user }: IPaymentTransferNew): JSX.Element => {
  const navigate = useNavigate();

  const onCreateTransfer = () => {
    fetchTransfers({ fetchTransfers: fetchTransfersFn(id) });
  };

  const onClose = () => {
    navigate(-1);
  };

  return (
    <ShowWhen
      apiFeatureEnabled="Marketplace"
      additionalCondition={(user: any) => user.isAllowedView('payments')}
    >
      <NewPaymentTransfer
        paymentId={id}
        onClose={onClose}
        onCreate={onCreateTransfer}
        isDirectTransferEnabled={user.isDirectTransferEnabled}
      />
    </ShowWhen>
  );
};

const mapStateToProps = (state: any) => {
  return {
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators(
    {
      fetchTransfers: fetchTransfersAction,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(PaymentTransferNew);
