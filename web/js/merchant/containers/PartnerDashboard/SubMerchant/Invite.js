import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';

import ModalHeader from 'rzp/ui/ModalHeader';
import InputField from 'rzp/ui/Forms/InputField';
import { closeModal } from 'rzp/modules/modals';
import { required, email } from 'rzp/utils/validators';

@connect(null, { closeModal })
@reduxForm({
  form: 'InviteMerchant',
})
export default class Invite extends Component {
  save = () => {
    alert('saving');
  };
  render() {
    const { handleSubmit } = this.props;
    return (
      <div>
        <ModalHeader
          title="Invite Merchant"
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          <form onSubmit={handleSubmit(this.save)}>
            <div class="form-group">
              <label>E-mail ID</label>
              <Field
                name="email"
                id="email"
                component={InputField}
                class="form-control"
                validate={[required(), email('Please provide a valid email')]}
              />
            </div>

            <span class="help-block">
              By inviting the merchant to sign up on Razorpay dashboard, you
              both can manage the account.
            </span>

            <div class="alert alert-warning custom-banner arrow-up">
              To change the registered email ID please, send a request to{' '}
              <a href="mailto:support@razorpay.com">support@razorpay.com</a>
            </div>

            <div class="Modal__Actions clearfix">
              <AsyncButton
                text="Assign New E-mail ID"
                pendingText="Assigning..."
                type="submit"
                class="btn btn-primary btn-block"
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}
