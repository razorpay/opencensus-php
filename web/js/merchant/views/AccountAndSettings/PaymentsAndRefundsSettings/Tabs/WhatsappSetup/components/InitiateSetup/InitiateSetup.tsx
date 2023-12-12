import React, { useState } from 'react';
import {
  Box,
  Heading,
  Text,
  Divider,
  Card,
  CardBody,
  Link,
  Button,
} from '@razorpay/blade/components';
import AccountLinkingModal from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/Modals/AccountLinking';
import StatusNotification from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/Modals/StatusNotification';
import { useMobile } from 'common/hooks/useMobile';
import { SERVICE_PROVIDER_SIGNUP_HREF } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/constants';
import { whatsappAccountSetupAnalyticsTrack } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/utils';

const modalViewMap = {
  linkAccount: AccountLinkingModal,
  statusNotification: StatusNotification,
  close: (_: any): JSX.Element => <React.Fragment />,
};

const InitiateSetup = (): JSX.Element => {
  const isMobile = useMobile();
  const [modalState, setModalState] = useState({
    activeView: 'close',
    isOpen: false,
    info: {},
  });

  const handleAction = ({ action, eventData }): void => {
    setModalState((prevState) => ({
      ...prevState,
      activeView: action,
      isOpen: !(action === 'close'),
      info:
        action === 'close'
          ? {}
          : {
              ...prevState.info,
              ...eventData,
            },
    }));
  };

  const ModalComponent = modalViewMap[modalState.activeView];

  return (
    <>
      <Card
        padding={isMobile ? 'spacing.5' : 'spacing.7'}
        surfaceLevel={isMobile ? 3 : 2}
        elevation="none"
      >
        <CardBody>
          <Box display="flex" flexDirection="column" gap={{ base: 'spacing.6', m: 'spacing.5' }}>
            <Box
              display="flex"
              justifyContent="space-between"
              alignItems={{ m: 'center' }}
              flexDirection={{ base: 'column', m: 'row' }}
              gap={{ base: '10px', m: 'spacing.0' }}
            >
              <Box display="flex" flexDirection="column" gap={{ base: '10px', m: 'spacing.2' }}>
                <Heading size="small" weight="bold">
                  Continue by linking your existing WABA account
                </Heading>
              </Box>
              <Button
                size="medium"
                isFullWidth={isMobile}
                onClick={() => {
                  whatsappAccountSetupAnalyticsTrack({
                    objectName: 'WA Link Account Proceed',
                    actionName: 'Clicked',
                  });
                  handleAction({ action: 'linkAccount', eventData: {} });
                }}
              >
                Proceed
              </Button>
            </Box>
            <Divider />
            <Box
              display="flex"
              flexDirection={{ base: 'column', m: 'row' }}
              gap={{ base: 'spacing.2', m: 'spacing.3' }}
            >
              <Text size="medium" weight="bold">
                Don’t have an account?
              </Text>
              <Link
                href={SERVICE_PROVIDER_SIGNUP_HREF}
                rel="noreferrer noopener"
                target="_blank"
                variant="anchor"
                onClick={() => {
                  whatsappAccountSetupAnalyticsTrack({
                    objectName: 'WA Create New Business Account',
                    actionName: 'Clicked',
                  });
                }}
              >
                Create Whatsapp Business Account
              </Link>
            </Box>
          </Box>
        </CardBody>
      </Card>
      <ModalComponent
        modalState={modalState}
        setModalState={setModalState}
        onClose={handleAction}
      />
    </>
  );
};

export default InitiateSetup;
