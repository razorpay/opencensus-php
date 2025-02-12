import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import DetailRow from 'merchant/components/DetailRow';
import ShowWhen from 'merchant/components/ShowWhen';
import Button from 'common/new-ui/Button';
import UpdateContactMobile from 'common/ui/UpdateContactMobile';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { updateContactMobile, updateUser } from 'merchant_common/reducers/user';
import { verifyTwoFactorOtp } from 'merchant_common/reducers/twoFactor';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import TextHighlighter from 'common/ui/TextHighlighter';
import {
  CONTACT_NUMBER_UPDATE,
  UPDATE_CONTACT_NUMBER,
  ACTION_QUERY_PARAM_KEY,
} from 'merchant/views/Account/Profile/deeplink-constants';
import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { Modules } from 'common/constant/enums';

class UserContactMobile extends React.Component {
  onUpdateContactMobileComplete = (userData) => {
    this.props.updateUser(userData);
    this.props.closeModal();
  };

  onChangeContactMobile = () => {
    const { _user } = this.props;
    selfServeTrackInitiate({
      selfServeAction: 'Mobile Updated',
      page: _user.isAccountAndSettingsRevampEnabled ? 'Contact details' : 'Profile',
      screen: _user.isAccountAndSettingsRevampEnabled
        ? Modules.AccountAndSettings
        : Modules.MyAccount,
    });
    analyticsTrack({
      objectName: 'change contact number',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.props.openModal({
      size: 'small',
      component: <UpdateContactMobile onComplete={this.onUpdateContactMobileComplete} />,
      queryParams: {
        [ACTION_QUERY_PARAM_KEY]: UPDATE_CONTACT_NUMBER,
      },
    });
  };
  labelHandler = () => {
    return <TextHighlighter hashedWith={CONTACT_NUMBER_UPDATE}>Contact Number</TextHighlighter>;
  };
  render() {
    const contactMobile = this.props.user.contact_mobile;
    return (
      <TriggerOnQueryParamMatch
        queryParamsMapping={[
          { key: 'action', value: UPDATE_CONTACT_NUMBER, trigger: this.onChangeContactMobile },
        ]}
      >
        <DetailRow
          label={this.labelHandler}
          value={() => (
            <ContactMobileValue
              contactMobile={contactMobile}
              onChangeContactMobile={this.onChangeContactMobile}
            />
          )}
        />
      </TriggerOnQueryParamMatch>
    );
  }
}

function ContactMobileValue({ contactMobile, onChangeContactMobile }) {
  return (
    <span>
      <ShowWhen additionalCondition={(user) => user.isTwoFactorSetupDone}>
        <span className="text-success m-r">
          <i className="i i-done-all" />
        </span>
      </ShowWhen>
      {contactMobile || null}
      <ShowWhen additionalCondition={(user) => user.isContactMobileChangeAllowed}>
        <Button.Transparent onClick={onChangeContactMobile}>
          {contactMobile ? <i className="i i-edit p-l" /> : 'Set Contact Number'}
        </Button.Transparent>
      </ShowWhen>
    </span>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user.user,
  _user: state.session.user,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      updateContactMobile,
      updateUser,
      verifyTwoFactorOtp,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(UserContactMobile);
