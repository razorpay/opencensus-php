import React, { useState } from 'react';
import {
  Box,
  Button,
  Heading,
  Radio,
  RadioGroup,
  Text,
  TextArea,
} from '@razorpay/blade/components';

import ModalFooter from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/ModalCommon/ModalFooter';
import { trackInviteFlowOptOutForm } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';

import SuccessIcon from './icons/success-tick-rounded-green.svg';
import { SuccessBackground } from './styled';

type SuccessScreenOptOutProps = {
  onDismiss: () => void;
  productType: string;
  inviteFlow: string;
};

const SuccessScreenOptOut = ({
  onDismiss,
  productType,
  inviteFlow,
}: SuccessScreenOptOutProps): JSX.Element => {
  // State for local form
  const [radioValue, setRadioValue] = useState('');
  const [customReason, setCustomReason] = useState('');

  const handleFormSubmit = () => {
    trackInviteFlowOptOutForm({
      productType,
      inviteFlow,
      radioValue,
      customReason,
    });
    onDismiss();
  };
  return (
    <Box backgroundColor="surface.background.level2.lowContrast">
      <Box display="flex" flexDirection="column" gap="spacing.5" alignItems="start">
        <SuccessBackground height="220px" />
        <img src={SuccessIcon} height="48" alt="Invite Successful" />

        <Box display="flex" flexDirection="column" gap="spacing.3" justifyContent="center">
          <Heading size="medium">Invite successfully sent</Heading>
          <Box display="flex" flexDirection="column" gap="spacing.2">
            <Text color="surface.text.subdued.lowContrast">
              A signup link has been sent to the invited clients
            </Text>
            <Text color="surface.text.subdued.lowContrast">
              Once the client signs up, you can view the referred clients under{' '}
              <Text color="surface.text.subdued.lowContrast" weight="bold" display="inline">
                Affiliate Accounts
              </Text>
            </Text>
          </Box>
        </Box>
      </Box>
      <Heading marginTop="spacing.10" marginBottom="spacing.5">
        Before you finish, we have something to ask
      </Heading>
      <Text color="surface.text.subdued.lowContrast" weight="bold">
        Why did you opt to not handle the client's KYC?
      </Text>
      <Box marginBottom="spacing.11">
        <RadioGroup
          size="medium"
          label=""
          value={radioValue}
          onChange={({ value }) => setRadioValue(value)}
        >
          <Radio value="no_additional_effort">I cannot take the additional effort</Radio>
          <Radio value="no_necessary_details">I do not have the necessary details</Radio>
          <Radio value="client_did_not_want">Clients did not want me to perform KYC for them</Radio>
          <Radio value="something_else">Something else</Radio>
        </RadioGroup>
        {radioValue === 'something_else' ? (
          <Box marginLeft="spacing.7" marginTop="spacing.3">
            <TextArea
              label=""
              value={customReason}
              onChange={({ value }): void => {
                setCustomReason(value ?? '');
              }}
              placeholder="Elaborate on your reasons"
            />
          </Box>
        ) : null}
      </Box>

      <ModalFooter>
        <Button onClick={handleFormSubmit}>Submit & Close</Button>
      </ModalFooter>
    </Box>
  );
};

export default SuccessScreenOptOut;
