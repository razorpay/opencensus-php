import { connect } from 'react-redux';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';
import ModalHeader from 'common/ui/ModalHeader';
import TwoFactorVerificationOTP from 'common/ui/TwoFactorVerification/TwoFactorVerificationOTP';

import { isPhone } from 'common/utils/validators';

@connect(
  state => ({
    contactMobile: (state.session.user.user || {}).contact_mobile,
  }),
  {
    closeModal,
    openModal,
    showNotification,
  }
)
export default class UpdateContactMobile extends React.Component {
  state = {
    contactMobile: this.props.contactMobile,
  };

  onOtpResend = () => {
    const data = {
      contact_mobile: this.state.contactMobile,
    };

    return this.props.onSubmit(data);
  };

  onComplete = () => {
    // Passing contact_mobile_verified hardcoded as true in callback
    // Ideally this should come from API, but BE is unable send that as response
    // in current state
    return this.props.onComplete({ contact_mobile_verified: true });
  };

  onSubmit = () => {
    const data = {
      contact_mobile: this.state.contactMobile,
    };

    return this.props
      .onSubmit(data)
      .then(() => {
        this.props.openModal({
          size: 'small',
          component: (
            <TwoFactorVerificationOTP
              contactMobile={this.state.contactMobile}
              onSuccess={this.onComplete}
              onClose={this.props.onClose}
              onConfirm={this.props.onOtpConfirm}
              onResend={this.onSubmit}
            />
          ),
        });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors[0],
        });
      });
  };

  onChange = ({ target }) => {
    const { value } = target;
    this.setState({
      contactMobile: value,
    });
  };

  onCloseClick = () => {
    this.props.onClose && this.props.onClose();
    this.props.closeModal();
  };

  isFormValid = () => {
    const { contactMobile } = this.state;
    if (contactMobile && isPhone(contactMobile)) {
      return true;
    }
    return false;
  };

  render() {
    return (
      <div class="2fa-modal">
        <ModalHeader
          title="Setting up 2-step verification"
          onCloseClick={this.onCloseClick}
        />
        <div class="modal-body">
          <p>
            Let's setup a mobile number where you will receive an SMS with OTP
            for 2FA verification.
          </p>
          <Form>
            <Input
              name="contact_mobile"
              type="text"
              class="Input--vTop is-focused"
              autoFocus
              label="Enter your phone number"
              defaultValue={this.props.contactMobile}
              required
              onChange={this.onChange}
            />

            <AsyncBtn.Primary
              pendingState="Updating"
              type="submit"
              class="Button--full-width"
              onClick={this.onSubmit}
              disabled={!this.isFormValid()}
            >
              Update
            </AsyncBtn.Primary>
          </Form>
        </div>
      </div>
    );
  }
}
