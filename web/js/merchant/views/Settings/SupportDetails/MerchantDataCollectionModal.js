import React, { Component } from 'react';
import { compose } from 'redux';
import RTracking from 'react-tracking';
import { connect } from 'react-redux';
import { reduxForm, Field } from 'redux-form';
import AsyncButton from 'react-async-button';
import { createSupportDetail } from 'merchant/reducers/support_detail';
import {
  trackSupportDetailSubmitAction,
  trackSupportDetailPopupClose,
} from 'merchant/containers/Home/ga';
import { showNotification } from 'merchant_common/reducers/notifications';
import { required, isMobile, isEmail, isUrlLenient, isPhone } from 'common/utils/validators';
import InputField from 'common/ui/Forms/InputField';
import { autoPrefixUrls, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import VerifyOTP from './VerifyOTPScreen';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';

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
    createSupportDetail({ email, url: newurl, phone })
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
    const { closeModal, handleSubmit, supportDetail } = this.props;
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
          <div class="form-group">
            <label>Support Phone number</label>
            <Field
              component={InputField}
              type="tel"
              name="phone"
              class="form-control"
              pattern="[789][0-9]{9}"
            />
          </div>
          <div class="form-group">
            <label class="label-required">Support Email id</label>
            <Field
              component={InputField}
              type="email"
              name="email"
              class="form-control"
              validate={required()}
            />
          </div>
          <div class="form-group">
            <label>Support URL</label>
            <Field component={InputField} type="text" name="url" class="form-control" />
          </div>
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
