import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { pickProps } from 'common/utils/rzp-utils';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { closeModal } from 'merchant_common/reducers/modals';
import { updateContactMobile } from 'merchant_common/reducers/user';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import { Modules } from 'common/constant/enums';
import { PersonalProfileFields } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import AccountDetailsUpdate from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/AccountDetails/v2/AccountDetailsUpdate';

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
class EditContactMobileForm extends React.Component {
  onCloseClick = () => {
    this.props.onClose?.();
  };

  onAnalyticsTrack = ({ objectName, actionName, properties = {} }) => {
    analyticsTrackWithUserInfo({
      objectName,
      actionName,
      screen: this.props.isNewAccountAndSettingsPage
        ? Modules.AccountAndSettings
        : Modules.MyAccount,
      properties,
    });
  };

  onContactUpdateSubmit = ({ contactMobile, setIsLoading }) => {
    const { otpAuthToken, showNotification } = this.props;

    const data = {
      contact_mobile: contactMobile,
      otp_auth_token: otpAuthToken,
    };
    setIsLoading(true);
    return this.props
      .updateContactMobile(data)
      .then(() => {
        this.onAnalyticsTrack({
          objectName: 'change contact number',
          actionName: 'result',
          properties: {
            status: 'success',
          },
        });
        this.props.onContactMobileUpdate(contactMobile);
      })
      .catch(({ errors }) => {
        this.onAnalyticsTrack({
          objectName: 'change contact number',
          actionName: 'result',
          properties: {
            status: 'failure',
            failureReason: errors[0],
          },
        });
        showNotification({
          type: 'error',
          message: errors[0],
        });
      })
      .finally(() => setIsLoading(false));
  };

  componentDidMount() {
    this.onAnalyticsTrack({
      objectName: 'change contact number confirmation popup',
      actionName: 'displayed',
      properties: {
        '2FaFlow': 'change mobile number',
      },
    });
  }

  render() {
    return (
      <AccountDetailsUpdate
        onModalDismiss={this.onCloseClick}
        entity={{
          id: PersonalProfileFields.CONTACT_MOBILE,
        }}
        onContactUpdateSubmit={this.onContactUpdateSubmit}
      />
    );
  }
}

const mapStateToProps = (state) => ({
  ...pickProps(state.session.user.user, ['contact_mobile', 'email']),
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    { closeModal, updateContactMobile, showNotification: fnShowNotification },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(EditContactMobileForm);
