import React from 'react';
import { Box, Button, Heading, Link, Text } from '@razorpay/blade/components';

import ModalFooter from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/ModalCommon/ModalFooter';
import { PARTNERSHIPS_WEBSITE_LINKS } from 'merchant/views/PartnerDashboard/constants';

import Ellipse from './icons/ellipse.svg';
import SuccessIcon from './icons/success-tick-rounded-green.svg';
import { SuccessBackground } from './styled';

type SuccessScreenOptInProps = {
  onDismiss: () => void;
};
const SuccessScreenOptIn = ({ onDismiss }: SuccessScreenOptInProps): JSX.Element => {
  return (
    <Box backgroundColor="surface.background.gray.intense" marginTop="spacing.4">
      <Box display="flex" flexDirection="column" gap="spacing.5" alignItems="start">
        <SuccessBackground height="190px" />
        <img src={SuccessIcon} height="48" alt="Invite Successful" />

        <Box display="flex" flexDirection="column" gap="spacing.3" justifyContent="center">
          <Heading size="small">Invite successfully sent</Heading>
          <Box display="flex" flexDirection="column" gap="spacing.2">
            <Text color="surface.text.gray.muted">
              A signup link has been sent to the invited contacts.
            </Text>
          </Box>
        </Box>
      </Box>
      <Text marginTop="spacing.10" marginBottom="spacing.5" size="large">
        What next?
      </Text>
      <Box display="flex" flexDirection="column" gap="spacing.7" marginBottom="spacing.11">
        <Box display="flex" gap="spacing.3" flex="1" alignItems="start">
          <img src={Ellipse} alt="ellipse" />
          <Box display="flex" flexDirection="column" gap="spacing.2">
            <Text color="surface.text.gray.subtle" weight="semibold">
              Sign up and approval from the client
            </Text>
            <Text size="small">
              While signing up, your client will be asked to approve your request for performing
              KYC.
            </Text>
          </Box>
        </Box>
        <Box display="flex" gap="spacing.3" flex="1" alignItems="start">
          <img src={Ellipse} alt="ellipse" />
          <Box display="flex" flexDirection="column" gap="spacing.2">
            <Text color="surface.text.gray.subtle" weight="semibold">
              Find the client in Affiliate Accounts
            </Text>
            <Text size="small">
              Once the client signs up, you can view the referred clients under Affiliate Accounts
              on your left navigation
            </Text>
          </Box>
        </Box>
        <Box display="flex" gap="spacing.3" flex="1" alignItems="start">
          <img src={Ellipse} alt="ellipse" />
          <Box display="flex" flexDirection="column" gap="spacing.2">
            <Text color="surface.text.gray.subtle" weight="semibold">
              Perform KYC for your client
            </Text>
            <Text size="small">
              You can perform KYC for your clients with the necessary details.{' '}
              <Link
                size="small"
                target="_blank"
                rel="noreferrer noopener"
                href={PARTNERSHIPS_WEBSITE_LINKS.PERFORM_KYC_DOCS_LINK}
              >
                Know more
              </Link>
            </Text>
          </Box>
        </Box>
      </Box>
      <ModalFooter>
        <Button onClick={onDismiss}>Got it</Button>
      </ModalFooter>
    </Box>
  );
};

export default SuccessScreenOptIn;
