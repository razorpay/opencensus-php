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
import { triggerHotjarRecording } from 'common/utils/hotjar';
import { track } from './ga.js';
import RTracking from 'react-tracking';

@RTracking(() => window.rzpQ.component('partnerOnbr'))
@withRouter
@connect(
  (state) => ({
    session: state.session,
    user: state.session.user,
    isMobileResolution: state.app.isMobileResolution
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
  constructor(props) {
    super(props);
    const defaultVariant = 'not_in_exp';
    const purePlatformExperimentVariant = this.props.user.getPurePlatformExperimentVariant;
    this.state = {
      role: null,
      isPurePlatformSignupEnabled: purePlatformExperimentVariant === 'exposed',
      experimentVariant: purePlatformExperimentVariant || defaultVariant,
    };
  }

  componentDidMount() {
    this.props.tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.pure_platform_signup', {
        merchantId: this.props.user.merchant.id,
        variant: this.state.experimentVariant,
      }),
    );
    const hotjarTag = `pure_platform_experiment_${this.state.experimentVariant}`;
    triggerHotjarRecording('pure_platform_experiment', ['pure_platform_experiment', hotjarTag]);
    window.trackHubs({
      name: 'update_property',
      data: {
        partner_signup_start: true,
      },
    });
  }

  onRoleSelect = (role) => {
    this.setState({ role });
    this.props.tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner_type.selected', {
        merchantId: this.props.user.merchant.id,
        partnerType: role,
        variant: this.state.experimentVariant,
      }),
    );

    window.trackHubs({
      name: 'update_property',
      data: {
        partner_type_selection: role,
      },
    });
  };

  trackSignupSuccessEvents = () => {
    this.props.tracking.trackEvent(
      window.rzpQ.onbr().clicked('partnerships.partner_signup.completed', {
        merchantId: this.props.user.merchant.id,
        variant: this.state.experimentVariant,
        partnerType: this.state.role,
      }),
    );
    this.props.tracking.trackEvent(
      window.rzpQ.onbr().clicked('partnerships.appstore.partner.signup', {
        merchantId: this.props.user.merchant.id,
        pagePath: window.location.pathname,
      }),
    );
    window.trackHubs({
      name: 'update_property',
      data: {
        partner_signup_complete: true,
      },
    });
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

        // fire tracking events after successful partner signup
        if (url === 'merchant/partner_type') {
          this.trackSignupSuccessEvents();
        }
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
  };

  onNotIntrestedClick = () => {
    this.closeTransaction('merchant/partner-intent', { partner_intent: false });
  };

  handleCloseClick = () => {
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
      <div
        className={`partner-onboarding-base-screen ${
          this.state.isPurePlatformSignupEnabled ? 'new-screen' : ''
        }`}
      >
        <Slider>
          {!this.props.disableClose
            ? (sliderProps) => (
                <S0
                  key={0}
                  sliderProps={sliderProps}
                  tracking={this.props.tracking}
                  merchantId={this.props.user.merchant.id}
                  experimentVariant={this.state.experimentVariant}
                />
              )
            : null}
          {(sliderProps) => (
            <S1
              key={1}
              sliderProps={sliderProps}
              onNext={this.handleNewUserGetStarted}
              experimentVariant={this.state.experimentVariant}
            />
          )}
          {(sliderProps) => (
            <S2
              key={2}
              sliderProps={sliderProps}
              onRoleSelect={this.onRoleSelect}
              role={this.state.role}
              abort={this.handleCloseClick}
              tracking={this.props.tracking}
              merchantId={this.props.user.merchant.id}
              experimentVariant={this.state.experimentVariant}
              isMobile={this.props.isMobileResolution}
            />
          )}
          {(sliderProps) => (
            <S3
              key={3}
              sliderProps={sliderProps}
              onNext={this.onCompleteClick}
              experimentVariant={this.state.experimentVariant}
            />
          )}
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
