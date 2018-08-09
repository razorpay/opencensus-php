import { Component } from 'react';
import { connect } from 'react-redux';
import { reduxForm, Field } from 'redux-form';
import AsyncButton from 'react-async-button';

import { closeModal } from 'rzp/modules/modals';
import { required } from 'rzp/utils/validators';

import ModalHeader from 'rzp/ui/ModalHeader';
import InputField from 'rzp/ui/Forms/InputField';

@connect(null, { closeModal })
@reduxForm({
  form: 'setCreditAlert',
})
export default class SetCreditAlert extends Component {
  save = body => {
    // TODO: write method for handling save in SetAlert.js
  };

  render() {
    const { handleSubmit } = this.props;
    return (
      <div>
        <ModalHeader
          title="Manage Alerts"
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          <span class="help-block">
            Set an amount to start receiving email alerts. You will receive an
            email when your credit balance goes below this amount.
          </span>

          <form>
            <div class="form-group">
              <label htmlFor="amount">Amount (in Rupees)</label>
              <Field
                name="amount"
                id="amount"
                component={InputField}
                class="form-control"
                value={[required()]}
              />
            </div>

            <div class="Modal__Actions clearfix">
              <AsyncButton
                text="Save"
                pendingText="Saving..."
                class="btn btn-primary btn-block"
                onClick={handleSubmit(this.save)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}
