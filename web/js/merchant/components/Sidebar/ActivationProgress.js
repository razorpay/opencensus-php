import ShowWhen from 'merchant/components/ShowWhen';
import ProgressBar from 'common/ui/ProgressBar';
import { classList } from 'common/utils/rzp-utils';
import RTracking from 'react-tracking';

export default RTracking((state, props, args) => {
  return window.rzpQ.component('ActivationProgress');
})(function ActivationProgress(props) {
  const { user, config } = props;

  const {
    showInstantActivation,
    instantActivation: { isL1Submitted, isBlacklistFlow },
  } = user;

  let actionCopy;
  let actionContent = null;
  let trackingIntent = null;

  if (user.activation_status === 'under_review') {
    actionCopy = 'KYC Under Review';
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

  return !isBlacklistFlow ? (
    <ShowWhen
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
            user.isSubmitted && !config.hasPersonalised ? 'not-personalised' : '',
          )}
        >
          <div className="clearfix">
            <div className="pull-left">{actionCopy}</div>
            <div className="pull-right">
              <i className="i i-chevron-right" />
            </div>
          </div>
          {do {
            if (showInstantActivation && !isL1Submitted) {
              actionContent = <div className="activation-status-secondary">Form not Completed</div>;
            } else {
              actionContent = !user.isSubmitted ? (
                <div className="activation-bar-content activation-status-secondary">
                  {user.activation_progress < 100 &&
                  isL1Submitted &&
                  user.isActivated &&
                  !user.isUnregisteredBusiness ? (
                    <div className="activation-bar-text">Click here to know more</div>
                  ) : (
                    <>
                      <div className="activation-bar-text">
                        {user.activation_progress}% Complete
                      </div>
                      <div className="activation-bar">
                        <ProgressBar type="success" max={100} value={user.activation_progress} />
                      </div>
                    </>
                  )}
                </div>
              ) : (
                <div className="activation-status-secondary">Personalise your Account</div>
              );
            }
          }}
        </div>
      </div>
    </ShowWhen>
  ) : null;
});
