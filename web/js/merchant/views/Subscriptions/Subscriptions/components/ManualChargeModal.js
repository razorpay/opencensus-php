import { Component } from 'react';
import { connect } from 'react-redux';

import ModalHeader from 'common/ui/ModalHeader';
import Alert from 'common/ui/Forms/Alert';
import AsyncButton from 'react-async-button';

import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { testChargeSubscription } from 'merchant/reducers/subscriptions';
import DocsLink from 'merchant/components/DocsLink';

// This code is dead. BE is not available for we are removing for now.

@connect(null, {
  closeModal,
  testChargeSubscription,
  showNotification,
})
export default class ManualChargeModal extends Component {
  state = {
    errors: null,
  };

  handleManualCharge = () => {
    return testChargeSubscription(this.props.subscriptionId, isSuccess)
      .then(() => {
        this.setState({ isDisabled: false });

        this.props.closeModal();
        this.props.showNotification({
          type: 'success',
          message: '',
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
      <div class="Subscription--Manual-charge">
        <ModalHeader
          title="ManualCharge"
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          <Alert type="error" message={this.state.errors} />

          <div class="text-muted">
            Manually charging this subscription will fail the scheduled payment
            for current cycle!
          </div>

          <div class="billing-details">
            <div class="row">
              <div class="col-sm-6">
                <div class="heading">Billing Cycle</div>
              </div>
              <div class="col-sm-6">
                <div class="heading">Upcoming Charge</div>
              </div>
            </div>
          </div>

          <div>
            To know more about manually charging a UPI subscription and its
            impact,{' '}
            <a href="">
              see documentation. <i class="i i-external-link" />
            </a>
          </div>

          <div class="title">Are you sure you want to charge manually?</div>

          <div class="Modal__actions">
            <button
              type="button"
              class="btn btn-default m-r"
              onClick={this.props.closeModal}
            >
              No, Don’t Charge
            </button>

            <AsyncButton
              type="button"
              class="btn btn-primary m-l"
              onClick={this.handleManualCharge}
              text="Yes, Charge Now"
            />
          </div>
        </div>
      </div>
    );
  }
}
