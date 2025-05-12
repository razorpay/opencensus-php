import React, { lazy, useEffect, useState } from 'react';
import {
  Box,
  Heading,
  Text,
  ChevronRightIcon,
  Link,
  CardBody,
  Card,
} from '@razorpay/blade/components';

import { useMultiAccount } from 'merchant/views/CompanyRegistration/hooks/SwitchAccountHook';
import { useMobile } from '@libs/shared-utils';
import {
  trackEventOnIncorporationCompletePageView,
  trackEventOnIncorporationCompleteLinkClick,
} from '../analytics';

const AccountModal = lazy(() => import('./AccountModal'));
const AccountBottomSheet = lazy(() => import('./AccountBottomSheet'));

const SwitchAccount = () => {
  const [isOpen, setIsOpen] = useState(false);
  const isMobile = useMobile();
  const { selected, setSelected, isLoading, handleUserAction } = useMultiAccount();
  const closeModal = () => {
    setIsOpen(false);
  };
  const props = {
    isOpen,
    closeModal,
    selected,
    setSelected,
    isLoading,
    handleUserAction,
  };
  const openModal = () => {
    setIsOpen(true);
    trackEventOnIncorporationCompleteLinkClick();
  };
  useEffect(() => {
    trackEventOnIncorporationCompletePageView();
  }, []);
  return (
    <>
      <Card
        borderRadius="large"
        marginY="spacing.7"
        marginX={{ base: 'spacing.0', m: 'spacing.7' }}
        elevation="highRaised"
      >
        <CardBody>
          <Box
            display="flex"
            flexDirection="column"
            justifyContent="flex-start"
            backgroundColor="surface.background.gray.intense"
          >
            <Heading marginBottom="spacing.7" color="surface.text.gray.subtle">
              You’re now eligible to upgrade to a Registered Razorpay Business Account!
            </Heading>
            <Text color="surface.text.gray.subtle" marginBottom="spacing.7">
              As a registered entity, you can now create a Registered Business Account to enjoy
              advanced features & exclusive benefits.
            </Text>
            <Link onClick={openModal} icon={ChevronRightIcon} iconPosition="right" variant="button">
              Create Account
            </Link>
          </Box>
        </CardBody>
      </Card>
      {isOpen ? isMobile ? <AccountBottomSheet {...props} /> : <AccountModal {...props} /> : null}
    </>
  );
};

export default SwitchAccount;
