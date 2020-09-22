import React from 'react';
import OnboardingStepCard from '../../OnboardingStepCard';

const GreylistedSteps = () => {
  const onClick = (step) => {
    console.log('clicked', step);
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
        },
        {
          name: 'Business Overview',
          id: 'business_overview',
          onClick,
        },
        {
          name: 'Business Details',
          id: 'business_details',
          onClick,
        },
        {
          name: 'Bank and Business Details',
          id: 'bank_details',
          onClick,
        },
        {
          name: 'Documents Upload',
          id: 'document_upload',
          onClick,
        },
      ]}
    />
  );
};

export default GreylistedSteps;
