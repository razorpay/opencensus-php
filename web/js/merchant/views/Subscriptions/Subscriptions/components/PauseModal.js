import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';

import Alert from 'common/ui/Forms/Alert';
import ModalHeader from 'common/ui/ModalHeader';

import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { pauseSubscription } from 'merchant/reducers/subscriptions';

// This code is dead. BE is not available for we are removing for now.

@connect(null, {
  closeModal,
  pauseSubscription,
  showNotification,
})
export default class PauseModal extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      errors: null,
    };
  }

  save = params => {
    params.id = this.props.subscriptionId;

    return this.props
      .pauseSubscription(params)
      .then(() => {
        this.props.closeModal();

        this.props.showNotification({
          type: 'success',
          message: 'Subscription paused successfully',
        });
      })
      .catch(({ errors }) => {
        this.setState({
          errors,
        });
      });
  };

  render() {
    const { handleSubmit } = this.props;

    return (
      <div>
        <ModalHeader
          title="Cancel Subscription?"
          onCloseClick={this.props.closeModal}
        />

        <form class="form-horizontal" onSubmit={handleSubmit(this.save)}>
          <div class="modal-body">
            <Alert type="error" message={this.state.errors} />

            <div class="text-muted">
              This subscription will not be charged till it is resumed. Are you
              sure you want to pause it?
            </div>

            <div class="btn-toolbar">
              <button
                type="button"
                class="btn btn-default btn-half"
                onClick={this.props.closeModal}
              >
                No, don't
              </button>

              <AsyncButton
                type="submit"
                class="btn btn-primary btn-half"
                text="Yes, Pause Now"
                pendingText="Pausing..."
                onClick={handleSubmit(this.save)}
              />
            </div>
          </div>
        </form>
      </div>
    );
  }
}
