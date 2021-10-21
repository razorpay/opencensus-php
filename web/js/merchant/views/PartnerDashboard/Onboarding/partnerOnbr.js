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
import { track } from './ga';
import RTracking from 'react-tracking';
import { getCookie } from 'common/utils/cookies';

@RTracking(() => window.rzpQ.component('partnerOnbr'))
@withRouter
@connect(
  (state) => ({
    session: state.session,
    user: state.session.user,
    isMobileResolution: state.app.isMobileResolution,
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
    let landingPageVariantInfo = getCookie('partner-lp-experiment');
    if (landingPageVariantInfo) {
      landingPageVariantInfo = JSON.parse(atob(landingPageVariantInfo));
    }
    this.state = {
      role: null,
      lpVariant: landingPageVariantInfo ? landingPageVariantInfo.lpVariant : null,
      lpFold: landingPageVariantInfo ? landingPageVariantInfo.lpFold : null,
      businessTypeName: this.props.user.isUnregisteredBusiness ? 'Unregistered' : 'Registered',
      fbBusinessTypeSuffix: this.props.user.isUnregisteredBusiness ? 'unreg' : 'reg',
    };
  }

  componentDidMount() {
    this.props.tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.pure_platform_signup', {
        merchantId: this.props.user.merchant.id,
        lpVariant: this.state.lpVariant,
        lpFold: this.state.lpFold,
      }),
    );
    triggerHotjarRecording('pure_platform_experiment', ['pure_platform_experiment']);
    if (window.trackHubs) {
      window.trackHubs({
        name: 'update_property',
        data: {
          partner_signup_start: true,
        },
      });
    }
  }

  onRoleSelect = (role) => {
    this.setState({ role });
    this.props.tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner_type.selected', {
        merchantId: this.props.user.merchant.id,
        partnerType: role,
        lpVariant: this.state.lpVariant,
        lpFold: this.state.lpFold,
      }),
    );

    if (window.trackHubs) {
      window.trackHubs({
        name: 'update_property',
        data: {
          partner_type_selection: role,
        },
      });
    }

    fireAnalyticsEvents({
      fbData: `partner_partnertype_${role}_${this.state.fbBusinessTypeSuffix}`,
    });
    track({
      eventAction: 'Select - Type',
      eventLabel: `Partner Onboarding | ${role} | ${this.state.businessTypeName}`,
    });
  };

  trackSignupSuccessEvents = () => {
    this.props.tracking.trackEvent(
      window.rzpQ.onbr().clicked('partnerships.partner_signup.completed', {
        merchantId: this.props.user.merchant.id,
        partnerType: this.state.role,
        lpVariant: this.state.lpVariant,
        lpFold: this.state.lpFold,
      }),
    );
    this.props.tracking.trackEvent(
      window.rzpQ.onbr().clicked('partnerships.appstore.partner.signup', {
        merchantId: this.props.user.merchant.id,
        pagePath: window.location.pathname,
      }),
    );
    if (window.trackHubs) {
      window.trackHubs({
        name: 'update_property',
        data: {
          partner_signup_complete: true,
        },
      });
    }
  };

  closeTransaction = (url, data) => {
    const userval = new User({
      ...this.props.user,
      partner_intent: false,
      partner_type: this.state.role,
      merchant_partner_intent: false,
    });
    return merchantFetch({
      url,
      method: 'PATCH',
      data,
    })
      .then(() => {
        this.props.updateSession({ user: userval });
        this.props.history.push(`partners/submerchants`);
        this.props.closeModal();

        // fire tracking events after successful partner signup
        if (url === 'merchant/partner_type') {
          this.trackSignupSuccessEvents();
        }
      })
      .catch(() => {
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
      fbData: `partner_activation_complete_${this.state.fbBusinessTypeSuffix}`,
      liData: 1668324,
    });
    track({
      eventAction: 'T&C Page',
      eventLabel: `Partner Onboarding | Accept T&C | ${this.state.businessTypeName}`,
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
      fbData: `partner_activation_started_${this.state.fbBusinessTypeSuffix}`,
      liData: 1668340,
    });
    track({
      eventAction: 'New User 1st Screen',
      eventLabel: `Partner Onboarding | Next | ${this.state.businessTypeName}`,
    });
  };

  render() {
    return (
      <div className="partner-onboarding-base-screen new-screen">
        <Slider>
          {!this.props.disableClose
            ? (sliderProps) => (
                <S0
                  key={0}
                  sliderProps={sliderProps}
                  tracking={this.props.tracking}
                  merchantId={this.props.user.merchant.id}
                  lpVariant={this.state.lpVariant}
                  lpFold={this.state.lpFold}
                />
              )
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
              tracking={this.props.tracking}
              merchantId={this.props.user.merchant.id}
              isMobile={this.props.isMobileResolution}
              lpVariant={this.state.lpVariant}
              lpFold={this.state.lpFold}
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
