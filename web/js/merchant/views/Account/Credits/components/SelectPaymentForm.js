import ModalHeader from 'common/ui/ModalHeader';
import React from 'react';
import { bindActionCreators, compose } from 'redux';
import { closeModal as closeModalReducer } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { reduxForm, Field } from 'redux-form';
import RadioButton from 'common/ui/Forms/RadioButton';
import AsyncButton from 'react-async-button';

function SelectPaymentForm({ closeModal, handleSubmit, pristine, destroy, handleCreditMethod }) {
  const handleSelectPaymentSubmit = async (fieldProps) => {
    await handleCreditMethod(fieldProps, destroy);
  };

  return (
    <form>
      <ModalHeader
        title="Select Payment Method"
        onCloseClick={() => {
          closeModal();
          destroy();
        }}
      />
      <div className="modal-body">
        <div className="form-group credit-method-radio">
          <Field
            component={RadioButton}
            name="paymentMethod"
            htmlValue="online_payment"
            label="UPI"
          />
        </div>
        <div className="form-group credit-method-radio">
          <Field
            name="paymentMethod"
            component={RadioButton}
            htmlValue="account_transfer"
            label="Bank Transfer (IMPS, NEFT, RGTS)"
          />
        </div>
        <div>
          <p>Note: Funds can only be added from bank account linked to your Razorpay account.</p>
        </div>
        <div className="Modal__actions">
          <AsyncButton
            type="submit"
            className="btn btn-primary btn-block"
            text="Continue"
            pendingText="Opening Bank details..."
            disabled={pristine}
            onClick={handleSubmit(handleSelectPaymentSubmit)}
          />
        </div>
      </div>
    </form>
  );
}

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ closeModal: closeModalReducer }, dispatch);

export default compose(
  connect(null, mapDispatchToProps),
  reduxForm({
    form: 'AddCreditsForm',
    destroyOnUnmount: false, // <------ preserve form data
    forceUnregisterOnUnmount: true,
  }),
)(SelectPaymentForm);
