import { Component } from 'react';
import { connect } from 'react-redux';
import { reduxForm, Field } from 'redux-form';
import AsyncButton from 'react-async-button';

import { closeModal } from 'rzp/modules/modals';
import { updateConfig } from 'merchant/modules/config';
import { showNotification } from 'rzp/modules/notifications';

import ModalHeader from 'rzp/ui/ModalHeader';
import InputField from 'rzp/ui/Forms/InputField';

import { amount, required } from 'rzp/utils/validators';
import { rupeesToPaise, paiseToRupees } from 'rzp/utils/rzp-utils';

@connect(
  state => {
    console.log({ state });
    return {
      feeCreditsThreshold: state.config.config.fee_credits_threshold,
    };
  },
  { closeModal, updateConfig, showNotification }
)
@reduxForm({
  form: 'setCreditAlert',
})
export default class SetCreditAlert extends Component {
  componentWillMount() {
    const feeCreditsThreshold = this.props.feeCreditsThreshold || 0;
    this.props.initialize({
      feeCreditsThreshold: paiseToRupees(feeCreditsThreshold),
    });
  }

  save = body => {
    return this.props
      .updateConfig({
        fee_credits_threshold: rupeesToPaise(Number(body.feeCreditsThreshold)),
      })
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Credits threshold updated successfully',
        });
        this.props.closeModal();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
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
              <label htmlFor="feeCreditsThreshold">Amount (in Rupees)</label>
              <Field
                name="feeCreditsThreshold"
                id="feeCreditsThreshold"
                component={InputField}
                class="form-control"
                validate={[required(), amount()]}
                required
              />
              <small class="help-block">
                <i class="i i-info-circle" />&nbsp;
                <span>
                  Set amount to 0 if you do not want to receive alerts
                </span>
              </small>
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
