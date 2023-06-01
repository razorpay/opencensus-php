import React, { useState } from 'react';
import { connect } from 'react-redux';
import Header from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/Header';
import {
  MainContentWrap,
  ApplicationFlowWrapper,
} from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Styled';
import {
  STEPS,
  STEP_COMPONENTS,
  trackingExperimentsProps,
} from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Constants';

interface PurePlatformSwitchApplicationProps {
  closeModal: () => void;
  setIsOpen: (val: boolean) => void;
  trackingExperiments: trackingExperimentsProps;
  user: any;
}

const PurePlatformSwitchApplication = ({
  closeModal,
  setIsOpen,
  trackingExperiments,
  user,
}: PurePlatformSwitchApplicationProps): JSX.Element => {
  const [step, setStep] = useState(STEPS.SERVICE_PROVIDED);

  const displayComponent = (): JSX.Element | null => {
    if (!step) {
      return null;
    }
    const Comp = STEP_COMPONENTS[step];
    return (
      <Comp
        setStep={setStep}
        setIsOpen={setIsOpen}
        trackingExperiments={trackingExperiments}
        user={user}
      />
    );
  };
  const mainContentWidth = step === STEPS.HAVE_ALL_CAPABILITIES ? '60%' : '90%';

  return (
    <ApplicationFlowWrapper>
      <Header
        closeModal={closeModal}
        step={step}
        setStep={setStep}
        trackingExperiments={trackingExperiments}
      />

      <MainContentWrap width={mainContentWidth}>{displayComponent()}</MainContentWrap>
    </ApplicationFlowWrapper>
  );
};

export default connect(
  (state) => ({ user: state.session.user }),
  null,
)(PurePlatformSwitchApplication);
