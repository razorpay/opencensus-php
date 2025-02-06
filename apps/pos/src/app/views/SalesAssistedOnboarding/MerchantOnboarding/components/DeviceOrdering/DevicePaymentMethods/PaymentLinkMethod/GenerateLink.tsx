import React from 'react';
import {
  Amount,
  Box,
  SendIcon,
  StepGroup,
  StepItem,
  StepItemIcon,
  Text,
} from '@razorpay/blade/components';

const MerchantDetails = ({
  label,
  value,
}: {
  label: string;
  value: React.ReactNode;
}): JSX.Element => {
  return (
    <Box marginBottom="spacing.4" display="flex" alignItems="center" gap="spacing.5">
      <Text size="small" color="surface.text.gray.muted">
        {label}
      </Text>
      <Text size="small" color="surface.text.gray.muted" weight="semibold">
        {value}
      </Text>
    </Box>
  );
};

export interface GenerateLinkProps {
  merchantContactDetails: {
    name: string;
    phone: string;
  };
  amount: number;
}
export const GenerateLink = ({
  merchantContactDetails,
  amount,
}: GenerateLinkProps): JSX.Element => {
  return (
    <Box>
      <StepGroup orientation="vertical" size="medium">
        <StepItem
          title="Generate payment link"
          description="Link to be received by merchant via Whatsapp and SMS"
          marker={<StepItemIcon icon={SendIcon} color="neutral" />}
          stepProgress="start"
        />
      </StepGroup>
      <Box paddingLeft={'spacing.9'}>
        <MerchantDetails label={'Merchant name'} value={merchantContactDetails.name} />
        <MerchantDetails label={'Contact number'} value={merchantContactDetails.phone} />
        <MerchantDetails
          label={'Amount'}
          value={
            <Amount
              weight="semibold"
              size="small"
              color="surface.text.gray.muted"
              currency="INR"
              value={Number(amount) || 0}
            />
          }
        />
      </Box>
    </Box>
  );
};
