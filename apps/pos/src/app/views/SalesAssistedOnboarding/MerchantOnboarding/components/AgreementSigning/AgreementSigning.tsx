import React from 'react';
import { PosAgreementMode } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/AgreementSigning/PosAgreementMode';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';

const AgreementSigning = () => {
  const { states, handlers } = useOnboardingContext();

  const { modularConfig, isModularLoading, isUpdateModularLoading } = states;
  const { updateModularConfig } = handlers;

  if (!modularConfig) return null;
  return (
    <PosAgreementMode
      modularConfig={modularConfig}
      isModularLoading={isModularLoading}
      isUpdateModularLoading={isUpdateModularLoading}
      updateModularConfig={updateModularConfig}
    />
  );
};
export default AgreementSigning;
