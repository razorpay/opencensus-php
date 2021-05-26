import React, { useState } from 'react';
import shallow from 'zustand/shallow';
import { withRouter, RouteComponentProps } from 'react-router-dom';
import View from '@razorpay/blade-old/src/atoms/View';
import OnboardingStepCard from '../../OnboardingStepCard';
import { useActivationFormState } from '../../context/store';
import useActivation from '../../hooks/useActivation';
import { getMerchantFlow } from '../../services/utils';
import { analyticsTrack } from '../../../../../services/tracking/segment';
import { SubmitForm as SubmitFormModal } from '../../ActivationModals';
import { useApp } from 'v2/context/App';

const GreylistedSteps: React.FC<RouteComponentProps> = ({ history }) => {
  const { data, postData } = useActivation();
  const { user } = useApp();
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
  const merchantFlow = getMerchantFlow(data.business_type, data.activation_flow);
  const [isSubmitFormModalOpen, setIsSubmitFormModalOpen] = useState(false);
  const onClick = (step: string) => {
    setActiveTabId(step);
    history.push('/onboarding/form');
  };
  const submitL2 = () => {
    postData({ submit: 1 }).then((res) => {
      if (res && res.submitted) {
        setIsSubmitFormModalOpen(true);
      }
      analyticsTrack({
        objectName: 'SignUp',
        actionName: 'submit and verify',
        screen: 'home page',
        eventAction: 'success',
        user,
      });
    });
  };
  const isStatusUnderReview =
    data.activation_status === 'under_review' ||
    data.activation_status === 'activated' ||
    data.activation_status === 'activated_mcc_pending';
  const underReviewInfo = isStatusUnderReview
    ? 'Your details are under review . We will get back to you in 7-8 working days'
    : '';
  const hasPoiStatus =
    data.poi_verification_status === 'incorrect_details' ||
    data.poi_verification_status === 'not_matched';

  const shouldShowPoiError = !data.submitted && hasPoiStatus;

  return (
    <View>
      <OnboardingStepCard
        title="Enable Payments and Settlements"
        subtitle="Submit these details to accept payments and recieve settlements in your account"
        info={underReviewInfo}
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
            isComplete: isBusinessDetailsCompleted && !shouldShowPoiError,
            hasErrorText: shouldShowPoiError ? 'Unable to verify your PAN. Please update' : '',
          },
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
        ]}
        showSettlement
        showCTA={!isStatusUnderReview}
        greyListFlowCanSubmit={data.can_submit}
        activationFlow={merchantFlow}
        onCTAClick={submitL2}
      />
      <SubmitFormModal
        isOpen={isSubmitFormModalOpen}
        hasClarificationReasons={data.kyc_clarification_reasons?.nc_count}
      />
    </View>
  );
};

export default withRouter(GreylistedSteps);
