import {
  MerchantModularOnboardingDetailsSuccessResponse,
  ModularComponent,
  ModularOnboardingStep,
  ModularOnboardingStepWithModularComponents,
} from 'apps/pos/src/app/types/modular';
import { StepProgressTypes } from 'apps/pos/src/app/types/SalesAssistedOnboarding';

interface GetStepsFromModularConfigProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse;
}

export const getStepsFromModularConfig = ({ modularConfig }: GetStepsFromModularConfigProps) => {
  const steps = modularConfig.workflowData.milestones?.[0]?.steps;
  if (!steps) return [];
  return steps;
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
      .filter(([, value]) => value !== undefined)
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
