import React, { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import { reduxForm, Field } from 'redux-form';
import { required } from 'common/utils/validators';
import InputField from 'common/ui/Forms/InputField';
import { closeModal as closeModalReducer } from 'merchant_common/reducers/modals';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { openCheckout } from 'merchant/utils/checkout-utility';
import { bindActionCreators, compose } from 'redux';

class AddFundsForm extends Component {
  addFunds = (fieldProps) => {
    const {
      closeModal,
      type,
      user,
      statusHandler,
      addHandler,
      analyticsHandler,
      currentBalance,
    } = this.props;
    const typeValue = type.charAt(0).toUpperCase() + type.slice(1);

    analyticsTrack({
      screen:
        type === ('reserve' || 'current')
          ? 'Dashboard - My Account (Balance)'
          : 'Dashboard - My Account (Credits)',
      objectName: type === ('reserve' || 'current') ? 'Add funds' : `Add ${typeValue} credits`,
      actionName: this.getAction(type),
      properties: {
        currentBalance,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    closeModal();
    openCheckout(fieldProps, type, user, addHandler, analyticsHandler, statusHandler);
  };

  getAction = (type) => {
    switch (type) {
      case 'current':
        return 'Current Balance Popup - Add Funds';
      case 'reserve':
        return 'Reserve Balance Popup - Add Funds';
      case 'fee':
        return 'Fee Credits Pop up - Add Funds';
      case 'refund':
        return 'Refund Credits Pop up - Add Funds';
      default:
        return '';
    }
  };

  formTitle = (type) => {
    switch (type) {
      case 'fee':
        return 'Add Fee Credits';
      case 'refund':
        return 'Add Refund Credits';
      case 'reserve':
        return 'Add Reserve Balance';
      case 'current':
        return 'Add Funds';
      default:
        return '';
    }
  };

  render() {
    const { handleSubmit, type } = this.props;
    const title = this.formTitle(type);
    const typesArray = ['fee', 'refund'];
    const text = typesArray.includes(type) ? 'Add Credits' : 'Add Funds';

    return (
      <form onSubmit={handleSubmit(this.addFunds)}>
        <ModalHeader
          title={title}
          onCloseClick={() => {
            analyticsTrack({
              objectName: 'add funds popup',
              actionName: 'clicked',
              screen: 'my account',
              properties: {
                action: 'cancel',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            this.props.closeModal();
          }}
        />
        <div class="modal-body">
          <div class="form-group">
            <Field
              component={InputField}
              type="text"
              placeholder="Enter Description"
              name="description"
              class="form-control"
              validate={required()}
              autoFocus={true}
            />
          </div>
          <div class="form-group">
            <Field
              component={InputField}
              type="text"
              placeholder="Enter Amount(INR)"
              name="amountInINR"
              class="form-control"
              validate={required()}
            />
          </div>
          {typesArray.includes(type) && (
            <div>
              <p>Note: Standard TDR charges applies on adding credits</p>
            </div>
          )}
          <div class="Modal__actions">
            <AsyncButton
              type="submit"
              class="btn btn-primary btn-block"
              text={text}
              pendingText="Adding..."
              onClick={handleSubmit(this.addFunds)}
            />
          </div>
        </div>
      </form>
    );
  }
}

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ closeModal: closeModalReducer }, dispatch);

export default compose(
  connect(null, mapDispatchToProps),
  reduxForm({
    form: 'AddFundsForm',
  }),
)(AddFundsForm);
