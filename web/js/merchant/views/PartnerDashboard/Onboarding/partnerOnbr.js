import React from 'react';
import { withRouter } from 'react-router-dom';
import Slider from 'common/new-ui/Slider';
import S0 from './steps/S0';
import S1 from './steps/S1';
import S2 from './steps/S2';
import S3 from './steps/S3';
import User from 'merchant/models/User';
import { updateSession } from 'merchant/reducers/session';
import { merchantFetch } from 'merchant/utils/ajax';
import { connect } from 'react-redux';
import { showNotification } from 'merchant_common/reducers/notifications';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { fireAnalyticsEvents } from 'common/utils/googleAnalytics';
import { track } from './ga.js';
import RTracking from 'react-tracking';

@RTracking(() => window.rzpQ.component('partnerOnbr'))
@withRouter
@connect(
  (state) => ({
    session: state.session,
    user: state.session.user,
  }),
  {
    updateSession,
    showNotification,
    openModal,
    closeModal,
  },
)
export default class BaseScreen extends React.Component {
  state = { role: null };

  onRoleSelect = (role) => {
    this.setState({ role });
  };

  closeTransaction = (url, data) => {
    const userval = new User({
      ...this.props.user,
      partner_intent: false,
      partner_type: this.state.role,
      merchant_partner_intent: false,
    });
    return merchantFetch({
      url: url,
      method: 'PATCH',
      data,
    })
      .then((response) => {
        this.props.updateSession({ user: userval });
        this.props.history.push(`partners/submerchants`);
        this.props.closeModal();
      })
      .catch((err) => {
        this.props.closeModal();
        this.props.showNotification({
          type: 'error',
          message: 'Something went wrong, we could not service this request at the moment.',
        });
        this.props.closeModal();
      });
  };

  onCompleteClick = () => {
    triggerHotjarRecording('partner_onboarding_success');
    fireAnalyticsEvents({
      fbData: 'partner_activation_complete',
      liData: 1668324,
    });
    track({
      eventAction: 'T&C Page',
      eventLabel: 'Partner Onboarding | Accept T&C',
    });
    this.closeTransaction('merchant/partner_type', {
      partner_type: this.state.role,
    });

    this.props.tracking.trackEvent(
      window.rzpQ.onbr().clicked('partnerships.appstore.partner.signup', {
        merchantId: this.props.user.merchant.id,
        pagePath: window.location.pathname,
      }),
    );
  };

  onNotIntrestedClick = () => {
    this.closeTransaction('merchant/partner-intent', { partner_intent: false });
  };

  handleCloseClick = () => {
    triggerHotjarRecording('partner_onboarding_cancelled');
    this.props.closeModal();
  };

  handleNewUserGetStarted = () => {
    fireAnalyticsEvents({
      fbData: 'partner_activation_started',
      liData: 1668340,
    });
    track({
      eventAction: 'New User 1st Screen',
      eventLabel: 'Partner Onboarding | Next',
    });
  };

  render() {
    return (
      <div className="partner-onboarding-base-screen">
        <Slider>
          {!this.props.disableClose
            ? (sliderProps) => <S0 key={0} sliderProps={sliderProps} />
            : null}
          {(sliderProps) => (
            <S1 key={1} sliderProps={sliderProps} onNext={this.handleNewUserGetStarted} />
          )}
          {(sliderProps) => (
            <S2
              key={2}
              sliderProps={sliderProps}
              onRoleSelect={this.onRoleSelect}
              role={this.state.role}
              abort={this.handleCloseClick}
            />
          )}
          {(sliderProps) => <S3 key={3} sliderProps={sliderProps} onNext={this.onCompleteClick} />}
        </Slider>
        {!this.props.disableClose && (
          <button
            type="button"
            class="close"
            onClick={this.handleCloseClick}
            style={{ position: 'absolute', top: '20px', right: '20px' }}
          >
            <i class="i i-close" />
          </button>
        )}
      </div>
    );
  }
}

function triggerHotjarRecording(trigger) {
  if (window && typeof window.hj === 'function') {
    window.hj('trigger', trigger);
    window.hj('tagRecording', [trigger]);
  }
}
