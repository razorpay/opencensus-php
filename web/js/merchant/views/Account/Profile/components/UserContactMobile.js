import { connect } from 'react-redux';

import DetailRow from 'merchant/components/DetailRow';
import ShowWhen from 'merchant/components/ShowWhen';

import Button from 'common/new-ui/Button';
import UpdateContactMobile from 'common/ui/UpdateContactMobile';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { updateContactMobile, updateUser } from 'merchant_common/reducers/user';
import { verifyTwoFactorOtp } from 'merchant_common/reducers/twoFactor';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';

@connect(
  (state) => ({
    user: state.session.user.user,
  }),
  {
    openModal,
    closeModal,

    updateContactMobile,
    updateUser,
    verifyTwoFactorOtp,
  },
)
export default class UserContactMobile extends React.Component {
  onUpdateContactMobileComplete = (userData) => {
    this.props.updateUser(userData);
    this.props.closeModal();
  };

  onChangeContactMobile = () => {
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
    });
  };

  render() {
    const contactMobile = this.props.user.contact_mobile;
    return (
      <DetailRow
        label="Contact Number"
        value={() => (
          <ContactMobileValue
            contactMobile={contactMobile}
            onChangeContactMobile={this.onChangeContactMobile}
          />
        )}
      />
    );
  }
}

function ContactMobileValue({ contactMobile, onChangeContactMobile }) {
  return (
    <span>
      <ShowWhen additionalCondition={(user) => user.isTwoFactorSetupDone}>
        <span className="text-success m-r">
          <i class="i i-done-all" />
        </span>
      </ShowWhen>
      {contactMobile || null}
      <ShowWhen additionalCondition={(user) => user.isContactMobileChangeAllowed}>
        <Button.Transparent onClick={onChangeContactMobile}>
          {contactMobile ? <i class="i i-edit p-l" /> : 'Set Contact Number'}
        </Button.Transparent>
      </ShowWhen>
    </span>
  );
}
