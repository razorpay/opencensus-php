import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import { setItem } from 'common/utils/localStorage';
import Banner from 'common/ui/Banner';
import { trackLinkClick } from './ga';
import RTracking from 'react-tracking';
import { analyticsTrack } from 'common/utils/analytics';
import { EASY_ONBOARDING } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';

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
    const needsClarificationOnEasyUrl = `${window.EASY_ONBOARDING_URL}/onboarding/needs-clarification`;
    window.open(needsClarificationOnEasyUrl, '_self', 'noopener');
  };

  render() {
    const { user, mode, tracking, isNcEligibile } = this.props;

    if (mode === 'live') {
      return null;
    }

    if (user.isOrgAxis) return null;

    const activationFormUrl = user.isActivationFormFullView ? '/kyc' : '/activation';
    const isNewNC = isNcEligibile && user.activation_status === 'needs_clarification';
    const isSignupWithEasyOnboarding = user?.user?.signup_campaign === EASY_ONBOARDING;

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
            <ShowWhen additionalCondition={(_user) => _user.isAllowedEdit('activation')}>
              <span>
                {' '}
                <Link
                  to={activationFormUrl}
                  onClick={() => {
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
                      window.open(window.EASY_ONBOARDING_URL, '_self', 'noopener');
                    }
                  }}
                >
                  Activate your account
                </Link>{' '}
                to start making live transactions.
              </span>
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

export default connect(mapStateToProps, null)(TestModeBanner);
