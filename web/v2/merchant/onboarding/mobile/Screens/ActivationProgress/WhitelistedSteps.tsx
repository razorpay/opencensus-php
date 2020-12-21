import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade/src/atoms/View';
import Size from '@razorpay/blade/src/atoms/Size';
import shallow from 'zustand/shallow';
import { withRouter, RouteComponentProps } from 'react-router-dom';
import useActivation from '../../hooks/useActivation';
import { useActivationFormState } from '../../context/store';
import OnboardingStepCard from '../../OnboardingStepCard';
import { StepPropsT } from '../../Step';
const Screen = styled(View)`
  background-color: #f9fbfe;
`;

const WhitelistedSteps: React.FC<RouteComponentProps> = ({ history }) => {
  const { data } = useActivation();
  const {
    isContactDetailsCompleted,
    isBusinessOverviewCompleted,
    isBusinessDetailsCompleted,
    isBankAndCompanyDetailsCompleted,
    isDocumentsUploadCompleted,
    setActiveTabId,
  } = useActivationFormState(
    (state) => ({
      isContactDetailsCompleted: state.isContactDetailsCompleted,
      isBusinessOverviewCompleted: state.isBusinessOverviewCompleted,
      isBusinessDetailsCompleted: state.isBusinessDetailsCompleted,
      isBankAndCompanyDetailsCompleted: state.isBankAndCompanyDetailsCompleted,
      isDocumentsUploadCompleted: state.isDocumentsUploadCompleted,
      setActiveTabId: state.setActiveTabId,
    }),
    shallow,
  );
  const onClick = (step) => {
    setActiveTabId(step);
    history.push('/onboarding/form');
  };
  const onEnableSettlementClick = (step) => {
    console.log('clicked', step);
  };
  const onCTAClick = () => {
    console.log('onCTAClick');
  };
  const enableSettlementInfo = data.submitted
    ? 'Your documents are under review. We will get back to you in 3 working days'
    : '';
  let enableSettlementSteps: StepPropsT[] = [
    {
      name: 'Complete step 1 to unlock this',
      id: 'enable_settlements',
      onClick: onEnableSettlementClick,
      isLocked: true,
    },
  ];
  if (data.onboarding_milestone === 'L1') {
    enableSettlementSteps = [
      {
        name: 'Bank and Business Details',
        id: 'bank_details',
        onClick,
        isComplete: isBankAndCompanyDetailsCompleted,
      },
      {
        name: 'Documents Upload',
        id: 'documents',
        onClick,
        isComplete: isDocumentsUploadCompleted,
      },
    ];
  }
  return (
    <Screen>
      <OnboardingStepCard
        title="1. Enable Payments"
        subtitle="Submit these details and start accepting payments"
        steps={[
          {
            name: 'Contact Details',
            id: 'contact_details',
            onClick,
            isComplete: isContactDetailsCompleted,
          },
          {
            name: 'Business Overview',
            id: 'business_overview',
            onClick,
            isComplete: isBusinessOverviewCompleted,
          },
          {
            name: 'Business Details',
            id: 'business_details',
            onClick,
            isComplete: isBusinessDetailsCompleted,
          },
        ]}
        onCTAClick={onCTAClick}
        showCTA
      />
      <Size height="20px">
        <View />
      </Size>
      <OnboardingStepCard
        title="2. Enable Settlements"
        subtitle="Submit your bank details and documents to receive money in your bank account"
        info={enableSettlementInfo}
        steps={enableSettlementSteps}
      />
    </Screen>
  );
};

export default withRouter(WhitelistedSteps);
