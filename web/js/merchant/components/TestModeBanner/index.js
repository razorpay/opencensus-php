import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import RTracking from 'react-tracking';
import { withI18Service } from 'common/i18';

import Banner from 'common/ui/Banner';
import { analyticsTrack } from 'common/utils/analytics';
import { setItem } from 'common/utils/localStorage';
import { redirectToEasyAfter1sec } from 'merchant/components/Activation/ActivationUtils';
import ShowWhen from 'merchant/components/ShowWhen';
import { getNCUrlOnEasyOrPhantom } from 'merchant/utils/urls';

import { trackLinkClick } from './ga';
import { checkIfSignUpViaEasyOnboarding } from 'common/utils/activation';

@RTracking(() => window.rzpQ.component('TestModeBanner'))
class TestModeBanner extends Component {
  switchToLiveMode = () => {
    const { user } = this.props;

    trackLinkClick('Swith - Mode');

    setItem(`rzp_mode--${user.current}`, 'live');
    window.location.reload();
  };

  redirectToNewNC = () => {
    const { user } = this.props;
    analyticsTrack({
      objectName: 'NC Easy',
      actionName: 'Redirect',
      screen: 'home page',
      properties: {
        ctaLabel: 'Activate your account',
        ctaLocation: 'TestModeBanner',
        ncCount: `${user?.kyc_clarification_reasons?.nc_count}`,
      },
      includeScreenResolution: true,
    });
    const needsClarificationOnEasyUrl = getNCUrlOnEasyOrPhantom();
    window.open(needsClarificationOnEasyUrl, '_self', 'noopener');
  };

  handleActivationUrlClick = (isNewNC, isSignupWithEasyOnboarding, tracking) => {
    if (isNewNC) {
      this.redirectToNewNC();
    }

    trackLinkClick('Go To - Activation Form');
    tracking.trackEvent(window.rzpQ.onbr().initiated('kyc.form_fill'));

    if (isSignupWithEasyOnboarding) {
      analyticsTrack({
        objectName: 'redirect to easy-dashboard CTA',
        actionName: 'Redirect',
        screen: 'home page',
        properties: {
          'CTA Label': 'Activate your account',
        },
      });
      redirectToEasyAfter1sec();
    }
  };

  render() {
    const {
      user,
      mode,
      tracking,
      isNcEligibile,
      i18: { isConfigTagEnabled },
    } = this.props;

    if (mode === 'live') {
      return null;
    }

    if (user.isOrgAxis) return null;

    const activationFormUrl = user.isActivationFormFullView ? '/kyc' : '/activation';
    const isNewNC = isNcEligibile && user.activation_status === 'needs_clarification';
    const isSignupWithEasyOnboarding = checkIfSignUpViaEasyOnboarding(user);

    return (
      /* For not as we have a seperarte Test Mode banner for m-web which is prominent so hiding this from m-web */
      <div className="TestModeBanner hidden-xs">
        <Banner>
          You are in <b>Test Mode</b>, so only test data is shown.{' '}
          {user.isActivated ? (
            <span>
              Switch to <a onClick={this.switchToLiveMode}>Live mode</a> to see real transaction
              data.
            </span>
          ) : null}
          {!user.isActivated && (
            <ShowWhen
              additionalCondition={() =>
                Boolean(user?.isAllowedEdit) && user.isAllowedEdit('activation')
              }
            >
              {isConfigTagEnabled('onboarding.onboarding') ? (
                <span>Activate your account to start making live transactions.</span>
              ) : (
                <span>
                  <Link
                    to={activationFormUrl}
                    onClick={() => {
                      this.handleActivationUrlClick(isNewNC, isSignupWithEasyOnboarding, tracking);
                    }}
                  >
                    Activate your account
                  </Link>{' '}
                  to start making live transactions.
                </span>
              )}
            </ShowWhen>
          )}
        </Banner>
      </div>
    );
  }
}

const mapStateToProps = (state) => ({
  user: state.session.user,
  mode: state.session.mode,
  isNcEligibile: state.home.isNcEligibile,
});

export default connect(mapStateToProps, null)(withI18Service(TestModeBanner));
