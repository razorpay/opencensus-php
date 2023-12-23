import ShowWhen from 'merchant/components/ShowWhen';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { ProgressBar } from 'common/ui/ProgressBar';
import { classList } from 'common/utils/rzp-utils';
import rTracking from 'react-tracking';
import { getActivationState } from 'merchant/components/Activation/ActivationUtils';
import { useEffect } from 'react';
import * as EventsActions from 'merchant/reducers/trackEvents';
import { checkEligibilityForFeeBasedGating } from 'merchant/utils/feeBasedGatingUtils';

function ActivationProgress(props) {
  const { user, config, trackEvents, isNcEligibile } = props;

  const {
    showInstantActivation,
    instantActivation: { isL1Submitted, isBlacklistFlow },
  } = user;

  let actionCopy;
  let trackingIntent = null;

  const isEligibleForFeeBasedGating = checkEligibilityForFeeBasedGating(user);

  if (
    user.activation_status === 'under_review' ||
    user.activation_status === 'kyc_qualified_unactivated'
  ) {
    actionCopy = 'KYC Under Review';
  } else if (isEligibleForFeeBasedGating) {
    actionCopy = 'Get KYC Verified';
  } else if (user.activation_progress < 100) {
    // If user form is still unfilled
    actionCopy = 'Activate your account';
    trackingIntent = 'act.form_fill';
    if (isL1Submitted) {
      actionCopy = 'Submit KYC';
      if (user.isActivated) {
        actionCopy = user.isUnregisteredBusiness ? 'Submit KYC' : 'Accept Payments';
        trackingIntent = 'dash.accept_payments';
      }
    }
  } else if (user.isAccepted) {
    actionCopy = 'Settlements Enabled';
  } else if (user.isActivated) {
    actionCopy = 'Account Activated';
  } else if (user.isSubmitted) {
    actionCopy = 'Form submitted';
  } else if (user.activation_progress == 100) {
    // Form is unfilled and Not submitted
    actionCopy = 'Submit Form';
  }

  const activationState = getActivationState(user, user.isUnregisteredBusiness, isNcEligibile);

  const isActivationmccPending =
    user.isActivationMccPendingProgressbarDisabled &&
    user.activation_progress === 90 &&
    user.activation_status === 'activated_mcc_pending';

  if (!isEligibleForFeeBasedGating && user.isInstantActivationEnabled) {
    if (activationState === 'account_activated') {
      actionCopy = 'Account Activated';
    } else {
      actionCopy = 'Account Activation';
    }
  }

  useEffect(() => {
    if (isActivationmccPending) {
      trackEvents({
        objectName: 'Onboarding progress bar',
        actionName: 'hidden',
        screen: 'home page',
        properties: {
          merchantStatus: user.activation_status,
        },
      });
    }
  }, []);

  const progressComponent = () => {
    if (user.isInstantActivationEnabled) {
      if (!isActivationmccPending && activationState === 'poi_initiated') {
        return <div className="activation-status-secondary">KYC under review</div>;
      } else if (activationState === 'account_activated') {
        return <div className="activation-status-secondary">Personalise your Account</div>;
      } else if (!isActivationmccPending) {
        return (
          <div className="activation-bar-content activation-status-secondary">
            <div className="activation-bar-text">{user.activation_progress}% Complete</div>
            <div className="activation-bar">
              <ProgressBar type="success" max={100} value={user.activation_progress} />
            </div>
          </div>
        );
      }
    } else if (!isActivationmccPending) {
      if (showInstantActivation && !isL1Submitted && user.activation_form_milestone !== 'L2') {
        return <div className="activation-status-secondary">KYC not completed</div>;
      } else if (!user.isSubmitted || user.activation_progress < 100) {
        return (
          <div className="activation-bar-content activation-status-secondary">
            <div className="activation-bar-text">{user.activation_progress}% Complete</div>
            <div className="activation-bar">
              <ProgressBar type="success" max={100} value={user.activation_progress} />
            </div>
          </div>
        );
      } else {
        return <div className="activation-status-secondary">Personalise your Account</div>;
      }
    }
    return null;
  };

  return !isBlacklistFlow &&
    activationState !== 'L1_dedupe_blocked' &&
    activationState !== 'L2_dedupe_blocked' &&
    activationState !== 'rejected' ? (
    <ShowWhen
      // eslint-disable-next-line no-shadow
      additionalCondition={(user) =>
        user.isAllowedEdit('activation') &&
        !user.isPartner() &&
        (!user.isSubmitted || !config.hasPersonalised)
      }
    >
      <div
        className="activation-status-link"
        onClick={() => {
          trackingIntent &&
            props.tracking.trackEvent(
              window.rzpQ.onbr().initiated(trackingIntent, {
                clickSource: 'LHS_Nav_Bar',
              }),
            );
          props.onSidebarBannerClick();
        }}
      >
        <div
          className={classList(
            'activation-status',
            user.isSubmitted && user.activation_progress === 100 && !config.hasPersonalised
              ? 'not-personalised'
              : '',
          )}
        >
          <div className="clearfix">
            <div className="pull-left">{actionCopy}</div>
            <div className="pull-right">
              <i className="i i-chevron-right" />
            </div>
          </div>
          {progressComponent()}
        </div>
      </div>
    </ShowWhen>
  ) : null;
}

export default compose(
  rTracking({
    page: 'ActivationProgress',
  }),
  connect(
    (state) => ({
      isNcEligibile: state.home.isNcEligibile,
    }),
    { ...EventsActions },
  ),
)(ActivationProgress);
