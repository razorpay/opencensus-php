import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import { openModal, closeModal } from 'merchant_common/reducers/modals';

import ModalHeader from 'common/ui/ModalHeader';

import Form from 'common/new-ui/Form';
import { AsyncBtn } from 'common/new-ui/Button';

import VerifyContactMobile from 'common/ui/VerifyContactMobile';
import UpdateContactMobile from 'common/ui/UpdateContactMobile';

import DocsLink from 'merchant/components/DocsLink';

@connect(state => ({
  currentUser: state.session.user.user,
}), {
  closeModal,
  openModal,
})
export default class TwoFactorVerificationSetup extends React.Component {
  onCloseClick = () => {
    this.props.onClose();
    this.props.closeModal();
  }

  initiateTwoFactorVerificationSetup = () => {
    const currentUser = this.props.currentUser;

    if (currentUser.contact_mobile) {
      this.props.openModal({
        size: 'small',
        component: (
          <VerifyContactMobile
            onComplete={this.props.onComplete}
            onClose={this.onCloseClick}
          />
        ),
      });
    } else {
      this.props.openModal({
        size: 'small',
        component: (
          <UpdateContactMobile
            onComplete={this.props.onComplete}
            onClose={this.onCloseClick}
          />
        )
      })
    }
  }

  render() {
    return (
      <div class="2fa-modal">
        <ModalHeader
          title="Action Needs 2 Step Verification"
          onCloseClick={this.onCloseClick}
        />
        <div class="modal-body">
          <div class="illustration">
            <img src="/dist/css/assets/2fa/2fa-locked.svg" />
          </div>

          <p class="m-b" >
            You haven't set-up 2 step verification for your account. Please verify your mobile number to set it up.
          </p>
          <p class="m-b" >
            <DocsLink
              url="https://razorpay.com/docs/payment-gateway/dashboard-guide/profile/"
              title="What's 2-step verification?"
            />
          </p>

          <Form>
            <AsyncBtn.Primary
              pendingState="Processing..."
              type="submit"
              class="Button--full-width"
              onClick={this.initiateTwoFactorVerificationSetup}
            >
              Setup 2-step Verification
            </AsyncBtn.Primary>
          </Form>
        </div>
      </div>
    );
  }
}
