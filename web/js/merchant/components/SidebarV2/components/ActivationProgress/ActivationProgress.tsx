import React from 'react';
import ShowWhen from 'merchant/components/ShowWhen';
import rTracking from 'react-tracking';
import { getActivationState } from 'merchant/components/Activation/ActivationUtils';
import { getActionStatus, getProgressStatus } from './helpers';
import {
  ActivationContainer,
  ActivationLink,
  ActivationStatus,
  Icon,
  Typo,
  ProgressBarContainer,
} from './styled';
import { ProgressBar } from 'common/ui/ProgressBar';
import { ActivationProgressInterface } from 'merchant/components/SidebarV2/typings';
import {
  ACTIVATION_STATE,
  ACTIVATED_MCC_PENDING,
} from 'merchant/components/SidebarV2/constants/constants';
import { isOrgFeatureExist } from 'merchant/models/User';
import { useI18Service } from 'common/i18';

const SecondaryText = ({ message }) => (
  <Typo size="11" weight="600" id="secondary-status">
    {message}
  </Typo>
);

const ActivationProgress = ({
  user,
  config,
  onSidebarActivationClick,
}: ActivationProgressInterface): JSX.Element | null => {
  const {
    showInstantActivation,
    instantActivation: { isL1Submitted, isBlacklistFlow },
  } = user;
  const { isConfigTagEnabled } = useI18Service();

  const activationState = getActivationState(user, user.isUnregisteredBusiness);
  const actionStatus = getActionStatus({ user, activationState, isL1Submitted });
  const isActivationmccPending =
    user.isActivationMccPendingProgressbarDisabled &&
    user.activation_progress === 90 &&
    user.activation_status === ACTIVATED_MCC_PENDING;

  const { secondaryStatus, isProgressView } = getProgressStatus({
    user,
    isActivationmccPending,
    activationState,
    showInstantActivation,
    isL1Submitted,
  });

  if (
    !isBlacklistFlow &&
    activationState !== ACTIVATION_STATE.L1_DEDUPE_BLOCKED &&
    activationState !== ACTIVATION_STATE.L2_DEDUPE_BLOCKED &&
    activationState !== ACTIVATION_STATE.REJECTED
  ) {
    return (
      <ShowWhen
        additionalCondition={() =>
          !isOrgFeatureExist('hide_activation_form') && !isConfigTagEnabled('onboarding.onboarding')
        }
      >
        <ShowWhen
          additionalCondition={(user) =>
            user.isAllowedEdit('activation') &&
            !user.isPartner() &&
            !user.isAssistedOnboardingMerchant &&
            (!user.isSubmitted || !config.hasPersonalised)
          }
        >
          <ActivationContainer onClick={onSidebarActivationClick}>
            <ActivationLink>
              <ActivationStatus>
                <Typo size="14" weight="600">
                  {actionStatus}
                </Typo>
                {isProgressView && (
                  <Typo size="11" weight="700">
                    ({user.activation_progress}%)
                  </Typo>
                )}
              </ActivationStatus>
              <Icon className="i i-visit-link" />
            </ActivationLink>
            {secondaryStatus && <SecondaryText message={secondaryStatus} />}
          </ActivationContainer>
          {isProgressView && (
            <ProgressBarContainer>
              <ProgressBar type="success" max={100} value={user.activation_progress} />
            </ProgressBarContainer>
          )}
        </ShowWhen>
      </ShowWhen>
    );
  }
  return null;
};

export default rTracking({ page: 'ActivationProgress' })(ActivationProgress);
