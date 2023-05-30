import InputField from 'common/ui/Forms/InputField';
import { analyticsTrack } from 'common/utils/analytics';
import { autoPrefixUrls, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { isEmail, isMobile, isPhone, isUrlLenient, required } from 'common/utils/validators';
import {
  trackSupportDetailPopupClose,
  trackSupportDetailSubmitAction,
} from 'merchant/containers/Home/ga';
import { createSupportDetail } from 'merchant/reducers/support_detail';
import { showNotification } from 'merchant_common/reducers/notifications';
import React, { Component } from 'react';
import AsyncButton from 'react-async-button';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { compose } from 'redux';
import { Field, reduxForm } from 'redux-form';
import VerifyOTP from './VerifyOTPScreen';

const PhoneField = () => (
  <>
    <label>Support Phone number</label>
    <Field
      component={InputField}
      type="tel"
      name="phone"
      class="form-control"
      pattern="[789][0-9]{9}"
    />
  </>
);
const EmailField = () => (
  <>
    <label class="label-required">Support Email id</label>
    <Field
      component={InputField}
      type="email"
      name="email"
      class="form-control"
      validate={required()}
    />
  </>
);
const SupportUrlField = () => (
  <>
    <label>Support URL</label>
    <Field component={InputField} type="text" name="url" class="form-control" />
  </>
);

const Fields = {
  phone_number: PhoneField,
  email: EmailField,
  website: SupportUrlField,
};

const getSupportDetailsPayload = (data) => {
  return Object.keys(data).reduce((acc, key) => {
    if (data[key]) {
      acc[key] = data[key];
    }
    return acc;
  }, {});
};

class MerchantDataCollectionModal extends Component {
  state = { isVerifying: false, newEmail: '', newUrl: '' };

  constructor(props) {
    super(props);
    const { supportDetail, initialize } = props;
    if (supportDetail) {
      initialize({
        phone: supportDetail.data.phone,
        email: supportDetail.data.email,
        url: supportDetail.data.url,
      });
    }
  }

  resetState = () => {
    this.setState({
      isVerifying: false,
      newEmail: '',
      newPhone: '',
      newUrl: '',
    });
  };

  onSubmit = (props) => {
    const {
      tracking,
      // eslint-disable-next-line no-shadow
      showNotification,
      closeModal,
      supportModal,
      // eslint-disable-next-line no-shadow
      createSupportDetail,
      supportDetail,
      user,
    } = this.props;

    const { email, url, phone } = props;
    if (url && !isUrlLenient(url)) {
      showNotification({
        type: 'error',
        message: 'Invalid url',
      });
      return;
    }
    if (phone && !(isMobile(phone) || isPhone(phone))) {
      showNotification({
        type: 'error',
        message: 'Invalid number',
      });
      return;
    }
    if (email && !isEmail(email)) {
      showNotification({
        type: 'error',
        message: 'Invalid Email id',
      });
      return;
    }
    const newurl = autoPrefixUrls(url);

    if (
      user.isSupportDetails2FAEnabled &&
      phone &&
      isMobile(phone) &&
      phone.substr(phone.length - 10) !==
        supportDetail.data.phone /* || email !== supportDetail.data.email */
    ) {
      const validNumber = phone.substr(phone.length - 10);

      this.setState({
        newEmail: email,
        newPhone: validNumber,
        newUrl: newurl,
        isVerifying: true,
      });
      return;
    }
    const supportDetailsPayload = getSupportDetailsPayload({ email, url: newurl, phone });

    createSupportDetail(supportDetailsPayload)
      .then((res) => {
        if (res.success && supportModal) {
          selfServeTrackSuccess({
            selfServeAction: 'Merchant Support Details Updated',
            page: 'Profile',
            screen: 'My Account',
          });
          showNotification({
            type: 'success',
            message: 'Support detail successfully added',
          });
        }
        trackSupportDetailSubmitAction({
          email: `${!!email}`,
          url: `${!!url}`,
          phone: `${!!phone}`,
        });

        tracking.trackEvent(
          window.rzpQ.onbr().success('support_details.popup_submit', {
            action: 'add_support_details',
          }),
        );
        tracking.trackEvent(
          window.rzpQ.onbr().initiated('action_popup', {
            clickSource: 'Submit',
          }),
        );
        trackSupportDetailPopupClose();
        closeModal();
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors[0],
        });
      });
  };

  componentDidMount() {
    selfServeTrackInitiate({
      selfServeAction: 'Merchant Support Details Updated',
      page: 'Profile',
      screen: 'My Account',
    });
    analyticsTrack({
      objectName: 'support details popup',
      actionName: 'displayed',
      screen: 'my account',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }

  render() {
    const { closeModal, handleSubmit, supportDetail, isIndividual, editField } = this.props;
    const { isVerifying, newEmail, newPhone, newUrl } = this.state;
    return isVerifying ? (
      <VerifyOTP
        closeModal={closeModal}
        phone={newPhone}
        email={newEmail}
        url={newUrl}
        supportDetail={supportDetail}
        reset={this.resetState}
      />
    ) : (
      <div className="support-modal-content">
        <div className="merchant-heading">
          Support details
          <button
            type="button"
            className="close"
            onClick={(...e) => {
              analyticsTrack({
                objectName: 'support details popup',
                actionName: 'clicked',
                screen: 'my account',
                properties: {
                  action: 'cancel',
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
              closeModal(...e);
            }}
          >
            <i className="i i-close" />
          </button>
        </div>
        <p className="merchant-subtitle">
          Let your customers know how to reach you for any queries.
        </p>
        <form
          onSubmit={(...e) => {
            analyticsTrack({
              objectName: 'support details popup',
              actionName: 'clicked',
              screen: 'my account',
              properties: {
                action: 'submit',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            return handleSubmit(this.onSubmit)(...e);
          }}
        >
          {Object.keys(Fields).map((each) => {
            const Component = Fields[each];
            if (isIndividual && editField !== each) {
              return null;
            }
            return (
              <div class="form-group" key={each}>
                <Component />
              </div>
            );
          })}
          <div className="merchant-note">
            <strong>Note:</strong> These details will be shared with customer in transaction emails.
          </div>
          <AsyncButton
            type="submit"
            className="btn btn-primary btn-block"
            onClick={handleSubmit(this.onSubmit)}
            text="Submit"
          />
        </form>
      </div>
    );
  }
}

export default compose(
  connect(
    (state) => {
      return {
        user: state.session.user,
      };
    },
    { showNotification, createSupportDetail },
  ),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('MerchantDataCollectionModal')),
  reduxForm({
    form: 'addSupportDetails',
  }),
)(MerchantDataCollectionModal);
