import React, { useState } from 'react';
import { RadioGroup, Radio, Box } from '@razorpay/blade/components';

import SocialShareGroup from 'merchant/views/PartnerDashboard/SubMerchant/components/ShareReferralLink/SocialShareGroup';
type ClientAssistOptionsType = {
  referralUrl: string;
  easyAccessUrl: string;
  initialValue?: string;
  inviteFlow: string;
  productType: string;
  showAlert?: (args: boolean) => void;
};
const ClientAssistOptions = ({
  referralUrl,
  easyAccessUrl,
  inviteFlow,
  productType,
  initialValue = '',
  showAlert,
}: ClientAssistOptionsType): JSX.Element => {
  const [isAssistClient, setIsAssistClient] = useState(initialValue);

  const handleAssistClientResponse = (response: string) => {
    setIsAssistClient(response);

    // we want to show the alert for the public invite flow
    showAlert?.(response !== initialValue);
  };

  return (
    <RadioGroup
      onChange={({ value }) => handleAssistClientResponse(value)}
      value={isAssistClient}
      label=""
    >
      <Box display="flex" flexDirection="column" gap="spacing.3">
        <Box
          display="flex"
          flexDirection="column"
          gap="spacing.5"
          padding={['spacing.5', 'spacing.5', 'spacing.7']}
          backgroundColor="surface.background.gray.moderate"
        >
          <Radio value="yes">Yes, I will assist my client with their KYC</Radio>
          {isAssistClient === 'yes' ? (
            <SocialShareGroup
              isKycAssistedSelected
              inviteFlow={inviteFlow}
              productType={productType}
              referralUrl={easyAccessUrl}
            />
          ) : null}
        </Box>
        <Box
          display="flex"
          flexDirection="column"
          gap="spacing.5"
          padding="spacing.5"
          backgroundColor="surface.background.gray.moderate"
        >
          <Radio value="no">No, my client will perform KYC on their own</Radio>
          {isAssistClient === 'no' ? (
            <SocialShareGroup
              isKycAssistedSelected={false}
              inviteFlow={inviteFlow}
              productType={productType}
              referralUrl={referralUrl}
            />
          ) : null}
        </Box>
      </Box>
    </RadioGroup>
  );
};

export default ClientAssistOptions;
