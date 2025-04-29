import React, { Suspense } from 'react';

const PartnerOnbr = React.lazy(
  () => import('merchant/views/PartnerDashboard/Onboarding/partnerOnbr'),
);

interface PartnerOnboardingModalForShellProps {
  closeModal: () => void;
  disableClose?: boolean;
}

const PartnerOnboardingModalForShell = ({
  closeModal,
  disableClose = false,
}: PartnerOnboardingModalForShellProps) => {
  return (
    <Suspense fallback={null}>
      <PartnerOnbr closeModal={closeModal} disableClose={disableClose} />
    </Suspense>
  );
};

export default PartnerOnboardingModalForShell;
