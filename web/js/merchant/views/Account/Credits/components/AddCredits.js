import React, { useState } from 'react';
import { closeModal as closeModalReducer } from 'merchant_common/reducers/modals';
import { bindActionCreators, compose } from 'redux';
import { connect } from 'react-redux';
import { openCheckout } from 'merchant/utils/checkout-utility';
import SelectPaymentForm from './SelectPaymentForm';
import AddCreditsForm from './AddCreditsForm';
import BankTransferDetails from './BankTransferDetails';
import { merchantFetch } from 'merchant/utils/ajax';
import { showNotification as showNotificationReducer } from 'merchant_common/reducers/notifications';

const creditsType = (type) => {
  switch (type) {
    case 'fee':
      return 'fee_credit';
    case 'refund':
      return 'refund_credit';
    case 'reserve':
      return 'reserve_balance';
    default:
      return '';
  }
};

function AddCredits({
  closeModal,
  type,
  statusHandler,
  addHandler,
  user,
  analyticsHandler,
  showNotification,
}) {
  const [paymentMethodSeleted, setPaymentMethodSeleted] = useState('');
  const [bankDetails, setBankDetails] = useState(null);

  const handleAddCredits = (fieldProps, destroy) => {
    closeModal();
    openCheckout(fieldProps, type, user, addHandler, analyticsHandler, statusHandler);

    // reset form fields on submit
    destroy();
  };

  const getBankDetails = (data) => {
    return merchantFetch({
      url: `fund_addition/initialize`,
      method: 'post',
      data,
    });
  };

  const handleCreditMethod = async (fieldProps, destroy) => {
    const { paymentMethod } = fieldProps;

    if (paymentMethod === 'account_transfer') {
      try {
        const { data } = await getBankDetails({ type: creditsType(type), method: paymentMethod });
        setBankDetails(data);
        destroy();
      } catch (error) {
        showNotification({
          type: 'error',
          message: `${error?.errors?.join(' ')}`,
        });

        return;
      }
    }

    setPaymentMethodSeleted(paymentMethod);
  };

  return (
    <>
      {!paymentMethodSeleted && <SelectPaymentForm handleCreditMethod={handleCreditMethod} />}
      {paymentMethodSeleted === 'online_payment' && (
        <AddCreditsForm
          type={type}
          onBack={() => setPaymentMethodSeleted('')}
          handleAddCredits={handleAddCredits}
        />
      )}
      {paymentMethodSeleted === 'account_transfer' && (
        <BankTransferDetails bankDetails={bankDetails} onBack={() => setPaymentMethodSeleted('')} />
      )}
    </>
  );
}

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    { closeModal: closeModalReducer, showNotification: showNotificationReducer },
    dispatch,
  );

export default compose(connect(null, mapDispatchToProps))(AddCredits);
