import React from 'react';
import shallow from 'zustand/shallow';
import { withRouter, RouteComponentProps } from 'react-router-dom';
import OnboardingStepCard from '../../OnboardingStepCard';
import { useActivationFormState } from '../../context/store';

const GreylistedSteps: React.FC<RouteComponentProps> = ({ history }) => {
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
  const onClick = (step: string) => {
    setActiveTabId(step);
    history.push('/onboarding/form');
  };

  return (
    <OnboardingStepCard
      title="Enable Payments and Settlements"
      subtitle="Submit your bank details and documents to receive money in your bank account"
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
    />
  );
};

export default withRouter(GreylistedSteps);
