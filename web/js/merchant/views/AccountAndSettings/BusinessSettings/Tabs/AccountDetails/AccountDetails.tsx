import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { Link } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import DetailRow from 'merchant/components/DetailRow';
import Time from 'common/ui/Time';
import { ProgressBar } from 'common/ui/ProgressBar';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { ActivationStatusLabel } from 'merchant/components/StatusLabel';
import { isMobileDevice } from 'merchant/components/Home/data';
import { analyticsTrack } from 'common/utils/analytics';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import { isOrgFeatureExist } from 'merchant/models/User';
import { fetchIsAdminAsMerchant } from 'merchant/reducers/profile';
import LoaderDots from 'common/ui/LoaderDots';
import { accountAccessHoverDescription } from 'merchant/views/AccountAndSettings/utils/conditionUtils';

const AccountDetails = (props): JSX.Element => {
  const {
    user,
    fetchIsAdminAsMerchant,
    isAdminAsMerchant,
    isFlowRevamped = true,
    isNcEligibile,
  } = props;

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
    window.open(`${window.EASY_ONBOARDING_URL}/onboarding/needs-clarification`);
  };

  return (
    <div
      data-testid="account-details-section"
      className={`${isFlowRevamped ? 'list-group details-row-container' : ''}`}
    >
      {!isAdminAsMerchant.loading ? (
        <ShowWhen
          additionalCondition={(_user) =>
            isAccountActivation && !_user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Onboarding)
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
                      isNcEligibile &&
                      user.isFeEasyDashboardNCEnabled &&
                      user.activation_status === 'needs_clarification'
                    ) {
                      redirectToEasyNc();
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
        <LoaderDots />
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
                  <ProgressBar type="success" max={100} value={user.activation_progress} />
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

export default connect(mapStateToProps, mapDispatchToProps)(AccountDetails);
