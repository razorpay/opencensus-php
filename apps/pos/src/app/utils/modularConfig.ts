import {
  MerchantModularOnboardingDetailsSuccessResponse,
  ModularComponent,
  ModularOnboardingStep,
  ModularOnboardingStepWithModularComponents,
} from 'apps/pos/src/app/types/modular';
import { StepProgressTypes } from 'apps/pos/src/app/types/SalesAssistedOnboarding';
import {
  COMPLETED,
  getAgreementComponentStatus,
  getAgreementMode,
  getAgreementSatusValue,
  OFFLINE,
  ONLINE,
  PENDING,
} from 'apps/pos/src/app/utils/agreementSigning';
import { MODULAR_ADDITIONAL_DETAILS_FIELDS } from 'apps/pos/src/app/types/MerchantAdditionalDetails';

interface GetStepsFromModularConfigProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse;
}

export const getStepsFromModularConfig = ({ modularConfig }: GetStepsFromModularConfigProps) => {
  //NOTE: Partner assisted onboarding contains 2 milestones and the FE uses both of these milestones to render in a single page. Hence, all steps are combined. This will not affect existing flows. 1st milestone is used for triggering the form_submission_action in BE
  const mergedSteps = modularConfig.workflowData.milestones.reduce((acc, currentMilestone) => {
    if (currentMilestone && currentMilestone.steps) {
      return [...acc, ...currentMilestone.steps];
    }
    return acc;
  }, [] as ModularOnboardingStep[]);

  return mergedSteps;
};

interface GetComponentFromStepProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
  step: string;
  component: string;
}

function isModularOnboardingStepWithModularComponents(
  step: ModularOnboardingStep,
): step is ModularOnboardingStepWithModularComponents {
  return (step as ModularOnboardingStepWithModularComponents).modularComponents !== undefined;
}

export const getComponentFromStep = ({
  modularConfig,
  step,
  component,
}: GetComponentFromStepProps): ModularComponent | null => {
  if (!modularConfig) return null;
  const steps = getStepsFromModularConfig({ modularConfig });
  const _targetStep = steps.find((_step) => _step?.name === step);

  if (_targetStep && isModularOnboardingStepWithModularComponents(_targetStep)) {
    const _targetComponent = _targetStep?.modularComponents.find(
      (_component) => _component?.name === component,
    );

    return _targetComponent || null;
  }
  return null;
};

interface GetFieldFromComponentProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse;
  step: string;
  component: string;
  fieldName: string;
}

export const getFieldFromComponent = ({
  modularConfig,
  step,
  component,
  fieldName,
}: GetFieldFromComponentProps) => {
  const _component = getComponentFromStep({ modularConfig, step, component });
  if (!_component) return null;
  return _component.fields?.find((field) => field?.name === fieldName) ?? null;
};

export const processFormDataForModularSubmit = (
  formData: Record<string, unknown>,
): Record<string, unknown> => {
  return Object.fromEntries(
    Object.entries(formData)
      .filter(([, value]) => value !== undefined || value !== null)
      .map(([key, value]) => {
        // Check if the value is a string and can be converted to a number
        if (typeof value === 'string' && !isNaN(Number(value))) {
          return [key, Number(value)];
        }
        return [key, value];
      }),
  );
};

interface GetProgressFromModularStepProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
  step: string;
}

export const getProgressFromModularStep = ({
  modularConfig,
  step,
}: GetProgressFromModularStepProps): StepProgressTypes => {
  if (!modularConfig) return 'pending';
  const steps = getStepsFromModularConfig({ modularConfig });
  const _targetStep = steps.find((_step) => _step?.name === step);
  if (_targetStep?.status === 'executed') {
    return 'completed';
  }

  return 'pending';
};

interface IsDevicePricingAdditionalDetailsCompleted {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
}
export const isDevicePricingAdditionalDetailsCompleted = ({
  modularConfig,
}: IsDevicePricingAdditionalDetailsCompleted) => {
  if (!modularConfig) return false;
  const isDeviceOrderingCompleted =
    getProgressFromModularStep({ modularConfig, step: 'device_selection_step' }) === COMPLETED;
  const isPricingCompleted =
    getProgressFromModularStep({ modularConfig, step: 'pricing_step' }) === COMPLETED;
  const isAdditionalDetailsCompleted =
    getProgressFromModularStep({
      modularConfig,
      step: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_STEP,
    }) === COMPLETED;

  if (isDeviceOrderingCompleted && isPricingCompleted && isAdditionalDetailsCompleted) return true;
  return false;
};

export const isDevicePricingAdditionalDetailsCompletedForPosEkyc = ({
  modularConfig,
}: IsDevicePricingAdditionalDetailsCompleted) => {
  if (!modularConfig) return false;
  const isDeviceOrderingCompleted =
    getProgressFromModularStep({ modularConfig, step: 'device_selection_step' }) === COMPLETED;
  const isAdditionalDetailsCompleted =
    getProgressFromModularStep({
      modularConfig,
      step: MODULAR_ADDITIONAL_DETAILS_FIELDS.ADDITIONAL_DETAILS_STEP,
    }) === COMPLETED;

  if (isDeviceOrderingCompleted && isAdditionalDetailsCompleted) return true;
  return false;
};
interface GetAgreementSigningStatus {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
}
export const getAgreementSigningStatus = ({
  modularConfig,
}: GetAgreementSigningStatus): 'completed' | 'pending' => {
  const isOnlineAgreementExecuted =
    getAgreementMode(modularConfig) === ONLINE &&
    getAgreementSatusValue(modularConfig) === COMPLETED;
  const isOfflineAgreementExecuted =
    getAgreementMode(modularConfig) === OFFLINE && getAgreementComponentStatus(modularConfig);
  return isOnlineAgreementExecuted || isOfflineAgreementExecuted ? COMPLETED : PENDING;
};

interface isPosEnabledArgs {
  states: {
    merchantDetails: {
      business: {
        type: {
          value: string;
        };
      };
      activation: {
        posActivationFlow: string | undefined | null;
        isPgosMerchant: boolean;
      };
    };
  };
}
export const isPosEnabledForMerchant = ({ states }: isPosEnabledArgs) => {
  if (!states) return false;
  const businessType = states.merchantDetails?.business.type.value;
  const posActivationFlow = states.merchantDetails?.activation.posActivationFlow;
  const isPgosMerchant = states.merchantDetails?.activation.isPgosMerchant;
  const isRegisteredMerchant =
    businessType !== 'UNREGISTERED' && businessType !== 'UNREGISTERED_OLD'; //same condition in self-serve, just that id is 11 & 2 respectively
  const allowedFlows = ['WHITELIST', 'GREYLIST'];
  const isWhitelistedForPos =
    posActivationFlow !== null && typeof posActivationFlow !== 'undefined'
      ? allowedFlows.includes(posActivationFlow ?? '')
      : true;
  return isRegisteredMerchant && isWhitelistedForPos && isPgosMerchant;
};
