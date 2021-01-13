import React, { useState } from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade/src/atoms/View';
import Size from '@razorpay/blade/src/atoms/Size';
import shallow from 'zustand/shallow';
import { withRouter, RouteComponentProps } from 'react-router-dom';
import useActivation from '../../hooks/useActivation';
import { useActivationFormState } from '../../context/store';
import OnboardingStepCard from '../../OnboardingStepCard';
import { StepPropsT } from '../../Step';
import { getMerchantFlow, isL1Submitted, getPoiVerificationStatus } from '../../services/utils';
import {
  EnableSettlements as EnableSettlementModal,
  SubmitForm as SubmitFormModal,
} from '../../ActivationModals';
const Screen = styled(View)`
  background-color: #f9fbfe;
`;

const WhitelistedSteps: React.FC<RouteComponentProps> = ({ history }) => {
  const { data, postData } = useActivation();
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

  const isL1Complete = [
    isContactDetailsCompleted,
    isBusinessOverviewCompleted,
    isBusinessDetailsCompleted,
  ].every((isComplete) => isComplete);

  const isL2Complete = [isBankAndCompanyDetailsCompleted, isDocumentsUploadCompleted].every(
    (isComplete) => isComplete,
  );

  const [isEnableSettlementModalOpen, setIsEnableSettlementModalOpen] = useState(false);
  const [isSubmitFormModalOpen, setIsSubmitFormModalOpen] = useState(false);
  const merchantFlow = getMerchantFlow(data.business_type, data.activation_flow);

  const isUnregPoiStatus = getPoiVerificationStatus(data);

  const onClick = (step) => {
    setActiveTabId(step);
    history.push('/onboarding/form');
  };
  const onEnableSettlementClick = () => {
    history.push('/onboarding/form');
  };
  const submitL1 = () => {
    postData({ onboarding_milestone: 'L1' }).then((res) => {
      if (res && res.onboarding_milestone === 'L1') {
        setIsEnableSettlementModalOpen(true);
      }
    });
  };
  const onCTAClick = () => {
    if (!isL1Submitted(data.onboarding_milestone) || isUnregPoiStatus) {
      submitL1();
    }
  };
  const submitL2 = () => {
    postData({ submit: 1 }).then((res) => {
      if (res && res.submitted) {
        setIsSubmitFormModalOpen(true);
      }
    });
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
  if (data.onboarding_milestone === 'L1' && !isUnregPoiStatus) {
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
  const canL1Submit =
    !isL1Submitted(data.onboarding_milestone) && isL1Complete && !isUnregPoiStatus;
  const canL2Submit = isL1Submitted(data.onboarding_milestone) && isL2Complete && data.can_submit;
  return (
    <Screen>
      <OnboardingStepCard
        title="1. Enable Payments"
        subtitle={
          !isL1Submitted(data.onboarding_milestone)
            ? 'Submit these details and start accepting payments'
            : 'Live payments have been enabled start accepting payments from your customers'
        }
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
            isComplete: isBusinessDetailsCompleted && !isUnregPoiStatus,
            hasErrorText: isUnregPoiStatus ? 'Unable to verify your PAN. Please update' : '',
          },
        ]}
        onCTAClick={onCTAClick}
        showCTA={!isL1Submitted(data.onboarding_milestone) || isUnregPoiStatus}
        activationFlow={merchantFlow}
        whiteListFlowCanSubmit={canL1Submit}
      />
      <Size height="20px">
        <View />
      </Size>
      <OnboardingStepCard
        title="2. Enable Settlements"
        subtitle="Submit your bank details and documents to receive money in your bank account"
        info={enableSettlementInfo}
        steps={enableSettlementSteps}
        showCTA={
          data.activation_status === 'under_review' ||
          data.activation_status === 'activated' ||
          data.activation_status === 'activated_mcc_pending'
            ? false
            : isL1Submitted(data.onboarding_milestone) && !isUnregPoiStatus
        }
        showSettlement
        activationFlow={merchantFlow}
        whiteListFlowCanSubmit={canL2Submit}
        onCTAClick={submitL2}
      />
      <EnableSettlementModal isOpen={isEnableSettlementModalOpen} />
      <SubmitFormModal isOpen={isSubmitFormModalOpen} />
    </Screen>
  );
};

export default withRouter(WhitelistedSteps);
