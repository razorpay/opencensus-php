import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import { compose } from 'redux';
import { closeModal } from 'merchant_common/reducers/modals';
import { required, email } from 'common/utils/validators';
import { invite as inviteSubmerchant } from 'merchant/reducers/submerchant';
import { showNotification } from 'merchant_common/reducers/notifications';

import ModalHeader from 'common/ui/ModalHeader';
import InputField from 'common/ui/Forms/InputField';

class Invite extends Component {
  save = (data) => {
    const submerchantId = this.props.id;
    return this.props
      .inviteSubmerchant(submerchantId, data)
      .then((data) => {
        if (data) {
          this.props.showNotification({
            type: 'success',
            message: 'Merchant invited to manage dashboard successfully',
          });
        }
        this.props.closeModal();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors[0],
        });
      });
  };
  render() {
    const { handleSubmit } = this.props;
    return (
      <div>
        <ModalHeader title="Invite Merchant" onCloseClick={this.props.closeModal} />

        <div className="modal-body">
          <form>
            <div className="form-group">
              <label>E-mail ID</label>
              <Field
                name="email"
                id="email"
                component={InputField}
                className="form-control"
                validate={[required(), email('Please provide a valid email')]}
              />
            </div>

            <span className="help-block">
              By inviting the merchant to sign up on the dashboard, you both can manage the account.
            </span>

            <div className="alert alert-warning custom-banner arrow-up">
              To change the registered email ID please, you can <a href="#ticket">write to us</a>
            </div>

            <div className="Modal__Actions clearfix">
              <AsyncButton
                text="Assign New E-mail ID"
                pendingText="Assigning..."
                className="btn btn-primary btn-block"
                onClick={handleSubmit(this.save)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}

export default compose(
  connect((state) => ({ ...state.submerchant.item }), {
    closeModal,
    inviteSubmerchant,
    showNotification,
  }),
  reduxForm({
    form: 'InviteMerchant',
  }),
)(Invite);
