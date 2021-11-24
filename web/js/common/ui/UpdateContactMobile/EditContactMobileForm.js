import React from 'react';
import { connect } from 'react-redux';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';
import ModalHeader from 'common/ui/ModalHeader';

import { pickProps, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { isPhone } from 'common/utils/validators';
import { analyticsTrack } from 'common/utils/analytics';

import { closeModal } from 'merchant_common/reducers/modals';
import { updateContactMobile } from 'merchant_common/reducers/user';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';

@connect(
  (state) => ({
    ...pickProps(state.session.user.user, ['contact_mobile', 'email']),
  }),
  {
    closeModal,
    updateContactMobile,
    showNotification: fnShowNotification,
  },
)
export default class EditContactMobileForm extends React.Component {
  state = {
    contactMobile: this.props.contactMobile,
  };

  onCloseClick = () => {
    if (this.props.onClose) this.props.onClose();
    this.props.closeModal();
  };

  onContactMobileChange = ({ target }) => {
    const { value } = target;
    this.setState({
      contactMobile: value,
    });
  };

  onContactUpdateSubmit = () => {
    const { contactMobile } = this.state;
    const { otpAuthToken, showNotification } = this.props;

    const data = {
      contact_mobile: contactMobile,
      otp_auth_token: otpAuthToken,
    };

    return this.props
      .updateContactMobile(data)
      .then(() => {
        analyticsTrack({
          objectName: 'change contact number',
          actionName: 'result',
          screen: 'my account',
          properties: {
            status: 'success',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        this.props.onContactMobileUpdate(this.state.contactMobile);
      })
      .catch(({ errors }) => {
        analyticsTrack({
          objectName: 'change contact number',
          actionName: 'result',
          screen: 'my account',
          properties: {
            status: 'failure',
            failureReason: errors[0],
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        showNotification({
          type: 'error',
          message: errors[0],
        });
      });
  };

  isFormValid = () => {
    const { contactMobile } = this.state;
    if (contactMobile && isPhone(contactMobile)) {
      return true;
    }
    return false;
  };

  componentDidMount() {
    analyticsTrack({
      objectName: 'change contact number confirmation popup',
      actionName: 'displayed',
      screen: 'my account',
      properties: {
        '2FaFlow': 'change mobile number',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }

  render() {
    return (
      <div class="2fa-modal">
        <ModalHeader title="Change Mobile Number" onCloseClick={this.onCloseClick} />
        <div class="modal-body">
          <p>Enter your new mobile number here.</p>
          <p class="m-t">You will have to verify this number with an OTP.</p>
          <Form>
            <Input
              name="contact_mobile"
              type="text"
              class="Input--vTop is-focused"
              autoFocus
              label="Enter your phone number"
              defaultValue={this.state.contactMobile}
              required
              onChange={this.onContactMobileChange}
            />

            <AsyncBtn.Primary
              pendingState="Updating"
              type="submit"
              class="Button--full-width"
              onClick={this.onContactUpdateSubmit}
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
