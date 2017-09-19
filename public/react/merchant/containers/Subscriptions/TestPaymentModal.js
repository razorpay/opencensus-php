import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import ModalHeader from 'rzp/ui/ModalHeader';
import Alert from 'rzp/ui/Forms/Alert';
import { isBlank } from 'rzp/utils/rzp-utils';
import { closeModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import { testChargeSubscription } from 'merchant/modules/subscriptions';

@connect(null, {
  closeModal,
  testChargeSubscription,
  showNotification,
})
export default class TestPaymentModal extends Component {
  state = {
    errors: null,
    isDisabled: false,
  };

  handleSubmit = isSuccess => {
    var testChargeMessages = [
      'Subscription cancelled successfully',
      'Subscription will be cancelled at the end of current billing cycle.',
    ];

    this.setState({ isDisabled: true });
    return testChargeSubscription(this.props.subscriptionId, isSuccess)
      .then(() => {
        this.setState({ isDisabled: false });
        console.log('CHARGED....', isSuccess);

        this.props.closeModal();
        this.props.showNotification({
          type: 'success',
          message: testChargeMessages[isSuccess],
        });

        this.props.postAction();
      })
      .catch(({ errors }) => {
        this.setState({
          errors,
        });
      });
  };

  render() {
    return (
      <div>
        <ModalHeader title="Charge Now" onCloseClick={this.props.closeModal} />

        <div class="modal-body">
          <Alert type="error" message={this.state.errors} />

          <div class="text-muted">
            This is test payment. You can choose it to be success or failure.
          </div>

          <div class="btn-toolbar">
            <AsyncButton
              type="submit"
              class="btn btn-default"
              text="Charge as Success"
              pendingText="Charging..."
              disabled={this.state.isDisabled}
              onClick={() => this.handleSubmit(1)}
            />

            <AsyncButton
              type="submit"
              class="btn btn-primary"
              text="Charge as failure"
              pendingText="Charging..."
              disabled={this.state.isDisabled}
              onClick={() => this.handleSubmit(0)}
            />
          </div>
        </div>
      </div>
    );
  }
}
