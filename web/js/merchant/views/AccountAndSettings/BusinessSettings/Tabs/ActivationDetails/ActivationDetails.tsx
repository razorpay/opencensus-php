import React from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { bindActionCreators } from 'redux';

import { useI18Service } from 'common/i18';
import LoaderDots from 'common/ui/LoaderDots';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { ProgressBar } from 'common/ui/ProgressBar';
import Time from 'common/ui/Time';
import { analyticsTrack } from 'common/utils/analytics';
import { redirectToEasyAfter1sec } from 'merchant/components/Activation/ActivationUtils';
import DetailRow from 'merchant/components/DetailRow';
import { isMobileDevice } from 'merchant/components/Home/data';
import ShowWhen from 'merchant/components/ShowWhen';
import { ActivationStatusLabel } from 'merchant/components/StatusLabel';
import { isOrgFeatureExist } from 'merchant/models/User';
import { fetchIsAdminAsMerchant } from 'merchant/reducers/profile';
import { getNCUrlOnEasyOrPhantom } from 'merchant/utils/urls';
import { accountAccessHoverDescription } from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import { checkIfSignUpViaEasyOnboarding } from 'common/utils/activation';

const ActivationDetails = (props): JSX.Element => {
  const {
    user,
    fetchIsAdminAsMerchant,
    isAdminAsMerchant,
    isFlowRevamped = true,
    isNcEligibile,
  } = props;
  const { isConfigTagEnabled } = useI18Service();

  React.useEffect(() => {
    const { loading, error } = isAdminAsMerchant;
    // already fetched perviously
    // TODO: Refactor based on loading state
    /* istanbul ignore else */
    if (loading && error === null) fetchIsAdminAsMerchant();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const isAccountActivation = isAdminAsMerchant.data || !isOrgFeatureExist('hide_activation_form');
  const activationUrl = user.isActivationFormFullView ? '/kyc' : '/activation';
  const isSignupWithEasyOnboarding = checkIfSignUpViaEasyOnboarding(user);

  let activationName = 'KYC';
  /* istanbul ignore else */
  if (!user.showInstantActivation || !user.instantActivation.isL1Submitted)
    activationName = 'Activation';

  const getActivationFormLabel = () => {
    return `${
      user.activated || user.locked || user.submitted
        ? 'View'
        : user.activation_progress == 100 && !user.submitted
        ? 'Submit'
        : 'Fill'
    } ${activationName} Form`;
  };

  const redirectToEasyNc = () => {
    analyticsTrack({
      objectName: 'NC Easy',
      actionName: 'Redirect',
      screen: 'my account',
      properties: {
        ctaLabel: getActivationFormLabel(),
        ctaLocation: 'account details',
        ncCount: user?.kyc_clarification_reasons?.nc_count,
      },
      includeScreenResolution: true,
    });
    const needsClarificationOnEasyUrl = getNCUrlOnEasyOrPhantom();
    window.open(needsClarificationOnEasyUrl);
  };

  return (
    <div
      data-testid="account-details-section"
      className={`${isFlowRevamped ? 'list-group details-row-container' : ''}`}
    >
      {!isAdminAsMerchant.loading ? (
        <ShowWhen
          additionalCondition={(_user) =>
            isAccountActivation && !isConfigTagEnabled('onboarding.onboarding')
          }
        >
          <DetailRow
            label={() => <b>Account Activation</b>}
            value={() => (
              <span>
                <Link
                  to={isMobileDevice() ? '/onboarding/steps' : activationUrl}
                  onClick={() => {
                    analyticsTrack({
                      objectName: 'view KYC form',
                      actionName: 'clicked',
                      screen: 'my account',
                      properties: {
                        status: window.rzp_user.verification.status,
                      },
                    });
                    if (
                      user.isOrgRZP &&
                      isAdminAsMerchant?.data &&
                      user.activation_status === 'activated'
                    )
                      return;
                    if (
                      isNcEligibile &&
                      user.isFeEasyDashboardNCEnabled &&
                      user.activation_status === 'needs_clarification'
                    ) {
                      redirectToEasyNc();
                    } else if (isSignupWithEasyOnboarding) {
                      analyticsTrack({
                        objectName: 'redirect to easy-dashboard CTA',
                        actionName: 'Redirect',
                        screen: 'my account',
                        properties: {
                          'CTA Label': getActivationFormLabel() ?? '',
                        },
                      });
                      redirectToEasyAfter1sec();
                    }
                  }}
                >
                  {getActivationFormLabel()}
                </Link>
              </span>
            )}
          />
        </ShowWhen>
      ) : (
        <LoaderDots customClass="" />
      )}

      {!!user.activated && (
        <DetailRow
          label="Account Activated On"
          value={() => <Time value={user.activated_at} format="MMM DD YYYY, hh:mm a" />}
        />
      )}

      {!user.showInstantActivation || user.instantActivation.isL1Submitted ? (
        <DetailRow
          label={`${activationName} Form Status`}
          value={() =>
            user.activation_status ? (
              <ActivationStatusLabel status={user.activation_status} />
            ) : (
              <div className="activation-bar-content activation-status-secondary">
                <div className="activation-bar-text">{user.activation_progress}% Completed</div>
                <div className="activation-bar">
                  <ProgressBar type="success" max={100} value={user.activation_progress} color="" />
                </div>
              </div>
            )
          }
        />
      ) : null}

      {user.isActivated && (
        <DetailRow
          label="Account Access"
          value={() => (
            <div className="account-access">
              {user.has_key_access ? 'Complete' : 'Limited'}
              <small className="help-content">
                <i className="i i-help" />
                <Popover align="right" theme="dark">
                  <PopoverBody>
                    <div>{accountAccessHoverDescription(user)}</div>
                  </PopoverBody>
                </Popover>
              </small>
            </div>
          )}
        />
      )}
    </div>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    isAdminAsMerchant: state.profile.isAdminAsMerchant,
    isNcEligibile: state.home.isNcEligibile,
  };
};

const mapDispatchToProps = (dispatch) => bindActionCreators({ fetchIsAdminAsMerchant }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(ActivationDetails);
