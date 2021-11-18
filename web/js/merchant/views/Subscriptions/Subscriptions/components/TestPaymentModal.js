import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import Alert from 'common/ui/Forms/Alert';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { testChargeSubscription } from 'merchant/reducers/subscriptions';

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

  handleSubmit = (isSuccess = 0) => {
    const testChargeMessages = [
      'Charge marked as FAILURE',
      'Charge marked as SUCCESS',
      'Upcoming invoice is issued',
    ];

    this.setState({ isDisabled: true });
    return testChargeSubscription(this.props.subscriptionId, isSuccess)
      .then(() => {
        this.setState({ isDisabled: false });

        this.props.closeModal();
        this.props.showNotification({
          type: 'neutral',
          message:
            this.props.subscriptionStatus === 'halted'
              ? testChargeMessages[2]
              : testChargeMessages[isSuccess],
          closeTimeout: 6500,
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
    let title, description, button;

    if (this.props.subscriptionStatus === 'halted') {
      title = 'Issue upcoming invoice';
      description = 'The upcoming invoice will be issued in test mode.';
      button = (
        <div class="btn-toolbar m-t">
          <AsyncButton
            type="submit"
            class="btn btn-primary full-width m-t"
            text="Issue upcoming invoice"
            pendingText="Issuing..."
            disabled={this.state.isDisabled}
            onClick={() => this.handleSubmit()}
          />
        </div>
      );
    } else {
      title = 'Charge Now';
      description = 'This is test payment. You can choose it to be success or failure.';
      button = (
        <div class="btn-toolbar m-t">
          <AsyncButton
            type="submit"
            class="btn btn-success full-width m-t"
            text="Charge as Success"
            pendingText="Charging..."
            disabled={this.state.isDisabled}
            onClick={() => this.handleSubmit(1)}
          />

          <AsyncButton
            type="submit"
            class="btn btn-danger full-width m-t"
            text="Charge as failure"
            pendingText="Charging..."
            disabled={this.state.isDisabled}
            onClick={() => this.handleSubmit(0)}
          />
        </div>
      );
    }

    return (
      <div>
        <ModalHeader title={title} onCloseClick={this.props.closeModal} />

        <div class="modal-body">
          <Alert type="error" message={this.state.errors} />

          <div class="text-muted">{description}</div>

          {button}
        </div>
      </div>
    );
  }
}
