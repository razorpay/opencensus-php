import React from 'react';
import {
  Card,
  CardBody,
  Box,
  Text,
  ChevronRightIcon,
  ProgressBar,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { useStore } from '@federated/apps/shell/commonStore';

import { getActivationState } from 'merchant/components/Activation/ActivationUtils';
import {
  getActionStatus,
  getProgressStatus,
} from 'merchant/components/SidebarV2/components/ActivationProgress/helpers';
import {
  ACTIVATION_STATE,
  ACTIVATED_MCC_PENDING,
  ONBOARDING_STEPS_URL,
  KYC_URL,
  ACTIVATION_URL,
} from 'merchant/components/SidebarV2/constants/constants';
import {
  checkEligibilityForFeeBasedGating,
  handleFeeBasedGatingNavigation,
} from 'merchant/utils/feeBasedGatingUtils';
import { getNCUrlOnEasyOrPhantom } from 'merchant/utils/urls';
import { checkIfSignUpViaEasyOnboarding } from 'common/utils/activation';
import { User } from 'common/typings';

type UserExtendedProps = {
  isOnboardingV2Enabled?: boolean;
  isActivationFormFullView?: boolean;
  isAssistedOnboardingMerchant?: boolean;
  isAllowedEdit?: (args) => boolean;
  isSubmitted?: boolean;
};

export const useActivationProgress = (user) => {
  const {
    showInstantActivation,
    instantActivation: { isL1Submitted, isBlacklistFlow },
  } = user;

  const activationState = getActivationState(user, user.isUnregisteredBusiness) || '';
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

  const shouldShow =
    !isBlacklistFlow &&
    activationState !== ACTIVATION_STATE.L1_DEDUPE_BLOCKED &&
    activationState !== ACTIVATION_STATE.L2_DEDUPE_BLOCKED &&
    activationState !== ACTIVATION_STATE.REJECTED;

  return {
    shouldShow,
    actionStatus,
    isProgressView,
    secondaryStatus,
    progressValue: user.activation_progress,
  };
};

const ActivationCard = ({ config, isNcEligibile, isMobile }): React.ReactElement => {
  const user = useStore<User & UserExtendedProps>((state) => state.session.user);

  const navigate = useNavigate();

  const { actionStatus, progressValue } = useActivationProgress(user);

  const handleActivationClick = () => {
    if (isNcEligibile && user.activation_status === 'needs_clarification') {
      // trackEvents({
      //   objectName: 'NC Easy',
      //   actionName: 'Redirect',
      //   screen: 'home page',
      //   properties: {
      //     ctaLabel: 'Account Activation',
      //     ctaLocation: 'LHS_Nav_Bar_v2',
      //     ncCount: user?.kyc_clarification_reasons?.nc_count,
      //   },
      //   includeScreenResolution: true,
      // });
      const needsClarificationOnEasyUrl = getNCUrlOnEasyOrPhantom();
      window.open(needsClarificationOnEasyUrl, '_self', 'noopener');
    } else if (checkEligibilityForFeeBasedGating(user)) {
      handleFeeBasedGatingNavigation({ ctaLocation: 'Sidebar' });
    } else if (checkIfSignUpViaEasyOnboarding(user)) {
      // analyticsTrack({
      //   objectName: 'redirect to easy-dashboard CTA',
      //   actionName: 'Redirect',
      //   screen: 'home page',
      //   properties: {
      //     'CTA Label': 'Account Activation',
      //   },
      // });

      window.open(window.EASY_ONBOARDING_URL, '_self', 'noopener');
    } else if (user.isOnboardingV2Enabled && isMobile) {
      //isMobileCondition was added here
      navigate(ONBOARDING_STEPS_URL);
    } else if (user.isActivationFormFullView) {
      navigate(KYC_URL);
    } else {
      navigate(ACTIVATION_URL);
    }
  };

  const isActivationProgressVisible =
    user.isAllowedEdit &&
    user.isAllowedEdit('activation') &&
    !user.isPartner() &&
    !user.isAssistedOnboardingMerchant &&
    (!user.isSubmitted || !config.hasPersonalised);

  return isActivationProgressVisible ? (
    <Card padding={'spacing.4'} elevation="none" onClick={handleActivationClick}>
      <CardBody>
        <Box display="flex" justifyContent="space-between" marginBottom="spacing.2">
          <Text size="medium" weight="semibold">
            {actionStatus}
          </Text>
          <Box>
            <ChevronRightIcon />
          </Box>
        </Box>
        <ProgressBar label="Progress" showPercentage={true} value={progressValue} />
      </CardBody>
    </Card>
  ) : (
    <></>
  );
};

const mapStateToProps = (state) => ({
  config: state.config.config,
  isNcEligibile: state.home.isNcEligibile,
  isMobile: state.app.isMobileResolution,
});

export default connect(mapStateToProps)(ActivationCard);
