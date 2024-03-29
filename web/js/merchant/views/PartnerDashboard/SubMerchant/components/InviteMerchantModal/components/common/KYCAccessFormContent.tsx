import React from 'react';
import { Badge, Box, Divider, Radio, RadioGroup, Text } from '@razorpay/blade/components';

import FAQContent from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/common/FAQModal/FAQContent';

// eslint-disable-next-line no-duplicate-imports
import type { RadioGroupProps } from '@razorpay/blade/components';

type KYCAccessFormContentProps = Required<
  Pick<RadioGroupProps, 'onChange' | 'value' | 'errorText'>
> & {
  validationState: 'error' | 'none';
  productType: string;
  inviteFlow: string;
};

const KYCAccessFormContent = ({
  onChange,
  value,
  errorText,
  validationState,
  inviteFlow,
  productType,
}: KYCAccessFormContentProps): JSX.Element => {
  return (
    <>
      <Box display="flex" gap="spacing.3" alignItems="center">
        <Badge display="inline" emphasis="intense" size="large" color="information">
          New feature
        </Badge>
        <Divider />
      </Box>
      <Box display="flex" flexDirection="column" gap="spacing.3" marginTop="spacing.5">
        <Box display="flex" flexDirection="column" gap="spacing.0">
          <Text weight="semibold">Assist the client with their KYC</Text>
          <Text size="small">to provide them with a quick and seamless onboarding experience.</Text>
        </Box>
        <RadioGroup
          label=""
          onChange={onChange}
          value={value}
          errorText={errorText}
          validationState={validationState}
          size="small"
        >
          <Radio value="true" size="small">
            Yes, I will assist my client with their KYC
          </Radio>
          <Radio value="false" size="small">
            No, my client will perform KYC on their own
          </Radio>
        </RadioGroup>
      </Box>
      <FAQContent inviteFlow={inviteFlow} productType={productType} />
    </>
  );
};
export default KYCAccessFormContent;
