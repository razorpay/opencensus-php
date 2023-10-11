import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import NewPaymentTransfer from 'merchant/views/Marketplace/Transfers/New';
import { fetchTransfersFn } from 'merchant/views/Transactions/model';
import ShowWhen from 'merchant/components/ShowWhen';
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
      additionalCondition={(user) => user.isAllowedView('payments')}
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

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchTransfers: fetchTransfersAction,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(PaymentTransferNew);
