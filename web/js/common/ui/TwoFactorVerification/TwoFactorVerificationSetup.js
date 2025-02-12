import React from 'react';
import { connect } from 'react-redux';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import ModalHeader from 'common/ui/ModalHeader';
import Form from 'common/new-ui/Form';
import { AsyncBtn } from 'common/new-ui/Button';
import DocsLink from 'merchant/components/DocsLink';

import IMG2FALocked from 'assets/2fa/2fa-locked.svg';
import { compose } from 'redux';

class TwoFactorVerificationSetup extends React.Component {
  onCloseClick = () => {
    this.props.onClose();
    this.props.closeModal();
  };

  render() {
    return (
      <div className="2fa-modal">
        <ModalHeader title="Action Needs 2 Step Verification" onCloseClick={this.onCloseClick} />
        <div className="modal-body">
          <div className="illustration">
            <img src={IMG2FALocked} />
          </div>

          <p className="m-b">
            {
              "You haven't set-up 2 step verification for your account. Please verify your mobile number to set it up."
            }
          </p>
          <p className="m-b">
            <DocsLink
              url="https://razorpay.com/docs/payment-gateway/dashboard-guide/profile/"
              title="What's 2-step verification?"
            />
          </p>

          <Form>
            <AsyncBtn.Primary
              pendingState="Processing..."
              type="submit"
              className="Button--full-width"
              onClick={this.props.onClickSetup}
            >
              Setup 2-step Verification
            </AsyncBtn.Primary>
          </Form>
        </div>
      </div>
    );
  }
}

export default compose(
  connect(null, {
    closeModal,
    openModal,
  }),
)(TwoFactorVerificationSetup);
