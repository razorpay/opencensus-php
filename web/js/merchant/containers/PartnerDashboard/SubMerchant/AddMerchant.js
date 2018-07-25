import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';

import { create } from 'merchant/modules/submerchant';
import { showNotification } from 'rzp/modules/notifications';
import { closeModal } from 'rzp/modules/modals';

import ModalHeader from 'rzp/ui/ModalHeader';
import InputField from 'rzp/ui/Forms/InputField';

import { required } from 'rzp/utils/validators';
import { showWhenUtil } from 'merchant/components/ShowWhen';

@connect(state => ({ ...state.session }), {
  create,
  showNotification,
  closeModal,
})
@reduxForm({
  form: 'addMerchant',
})
export default class AddMerchant extends Component {
  addNewMerchant = params => {
    return this.props
      .create(params)
      .then(data => {
        if (data) {
          this.props.showNotification({
            type: 'success',
            message: 'Submerchant created successfull',
          });
          this.props.closeModal();
        }
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    const { handleSubmit, user } = this.props;
    const emailMandatory = isEmailMandatory(user);
    const emailValidators = emailMandatory ? [required()] : [];

    return (
      <div>
        <ModalHeader
          title="Add New Merchant"
          onCloseClick={this.props.closeModal}
        />
        <div class="modal-body">
          {/* Merchant Name */}
          <div class="form-group">
            <label class="label-required">Merchant Name</label>
            <Field
              name="name"
              component={InputField}
              class="form-control"
              autoFocus
              validate={required()}
            />
          </div>

          {/* Merchant Email */}
          <div class="form-group">
            <label class={emailMandatory ? 'label-required' : ''}>
              Email Address
            </label>
            <Field
              name="email"
              component={InputField}
              validate={emailValidators}
              placeholder={emailMandatory ? '' : 'Optional'}
              class="form-control"
            />
            <span class="help-block">
              The Razorpay sign-up link will be sent to this email.
            </span>

            {!emailMandatory && (
              <span class="help-block">
                If no email is provided, your email will be mapped as the
                registered email ID of this merchant.
              </span>
            )}
          </div>

          <div class="Modal__Actions clearfix">
            <AsyncButton
              class="btn btn-primary btn-block"
              text="Add New Merchant"
              pendingText="Adding..."
              onClick={handleSubmit(this.addNewMerchant)}
            />
          </div>
        </div>
      </div>
    );
  }
}

function isEmailMandatory(user) {
  if (user.partner_type === 'aggregator') {
    return !showWhenUtil({ featureEnabled: 'allow_sub_without_email' });
  }
  return emailMandatoryMap[user.partner_type];
}

const emailMandatoryMap = {
  bank: true,
  reseller: true,
  pure_platform: true,
  fully_managed: false,
};
