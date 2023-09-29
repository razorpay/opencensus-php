import { checkEligibilityForFeeBasedGating } from 'merchant/utils/feeBasedGatingUtils';

type ActionStatusProp = {
  user: Record<string, any>;
  activationState: string;
  isL1Submitted: any;
};

type ProgressStatusProps = ActionStatusProp & {
  isActivationmccPending: boolean;
  showInstantActivation: boolean;
};

type ProgressStatusResponse = {
  secondaryStatus: string;
  isProgressView: boolean;
};

export const getActionStatus = ({
  user,
  activationState,
  isL1Submitted,
}: ActionStatusProp): string => {
  const isEligibleForFeeBasedGating = checkEligibilityForFeeBasedGating(user);
  let status;
  if (
    user.activation_status === 'under_review' ||
    user.activation_status === 'kyc_qualified_unactivated'
  ) {
    status = 'KYC Under Review';
  } else if (isEligibleForFeeBasedGating) {
    status = 'Get KYC Verified';
  } else if (user.activation_progress < 100) {
    status = 'Activate your account';
    if (isL1Submitted) {
      status = 'Submit KYC';
      if (user.isActivated) {
        status = user.isUnregisteredBusiness ? 'Submit KYC' : 'Accept Payments';
      }
    }
  } else if (user.isAccepted) {
    status = 'Settlements Enabled';
  } else if (user.isActivated) {
    status = 'Account Activated';
  } else if (user.isSubmitted) {
    status = 'Form submitted';
  } else if (user.activation_progress == 100) {
    // Form is unfilled and Not submitted
    status = 'Submit Form';
  }
  if (user.isInstantActivationEnabled) {
    if (activationState === 'account_activated') {
      status = 'Account Activated';
    } else {
      status = 'Account Activation';
    }
  }
  return status;
};

export const getProgressStatus = ({
  user,
  isActivationmccPending,
  activationState,
  showInstantActivation,
  isL1Submitted,
}: ProgressStatusProps): ProgressStatusResponse => {
  const response = {
    secondaryStatus: '',
    isProgressView: false,
  };

  if (user.isInstantActivationEnabled) {
    if (!isActivationmccPending && activationState === 'poi_initiated') {
      response.secondaryStatus = 'KYC under review';
    } else if (activationState === 'account_activated') {
      response.secondaryStatus = 'Personalise your Account';
    } else if (!isActivationmccPending) {
      response.isProgressView = true;
    }
  } else if (!isActivationmccPending) {
    if (showInstantActivation && !isL1Submitted && user.activation_form_milestone !== 'L2') {
      response.secondaryStatus = 'KYC not completed';
    } else if (!user.isSubmitted || user.activation_progress < 100) {
      response.isProgressView = true;
    } else {
      response.secondaryStatus = 'Personalise your Account';
    }
  }
  return response;
};
