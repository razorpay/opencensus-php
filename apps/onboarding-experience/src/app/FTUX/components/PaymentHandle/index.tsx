import React, { useState } from 'react';
import { Box, Heading, Text, Link } from '@razorpay/blade/components';
import SettlementsGuideModal from '@FTUX/modals/SettlementsGuideModal';
import PaymentHandleActions from './PaymentHandleActions';
import PaymentHandleBannerImg from 'apps/onboarding-experience/src/assets/PaymentHandleBanner.svg';

/**
 * PaymentHandle component displays information about payment handle functionality
 * and provides actions for the user to interact with the payment handle feature.
 */
const PaymentHandle = () => {
  const [isSettlementsGuideModalOpen, setIsSettlementsGuideModalOpen] = useState(false);

  return (
    <Box>
      {/* Mobile-only payment handle image */}
      <Box
        display={{ base: 'block', l: 'none' }}
        width="100%"
        minHeight="80px"
        marginBottom="spacing.4"
      >
        <img src={PaymentHandleBannerImg} alt="payment-handle" width="100%" />
      </Box>
      {/* Section heading */}
      <Box
        paddingLeft={{ base: 'spacing.5', m: 'spacing.7' }}
        marginBottom={{ base: 'spacing.5', m: 'spacing.7' }}
      >
        <Heading weight="semibold" size="medium">
          Accept payments with payment handle
        </Heading>
      </Box>
      {/* Main content container */}
      <Box
        display="flex"
        alignItems="center"
        justifyContent="space-between"
        padding={{
          base: 'spacing.5',
          m: 'spacing.7',
        }}
        flexDirection={{
          base: 'column',
          m: 'row',
        }}
        gap="spacing.5"
        borderRadius="large"
        borderWidth="thin"
        borderStyle="solid"
        borderColor="surface.border.gray.muted"
        backgroundColor="surface.background.gray.intense"
      >
        {/* Left side with text and actions */}
        <Box
          display="flex"
          flexDirection="column"
          alignItems="flex-start"
          gap="spacing.5"
          width="100%"
        >
          <Text weight="medium">
            Use this personalised link to accept payments instantly from your customers.
          </Text>
          {/* User actions related to payment handle - edit, copy and share */}
          <PaymentHandleActions />
          {/* Settlement information with modal trigger */}
          <Text color="surface.text.gray.muted" size="small">
            Your money will be credited to your account as per your{' '}
            <Link
              variant="button"
              size="small"
              onClick={() => setIsSettlementsGuideModalOpen(true)}
            >
              settlement schedule
            </Link>
          </Text>
        </Box>
        {/* Desktop-only payment handle image */}
        <Box display={{ base: 'none', l: 'block' }}>
          <img src={PaymentHandleBannerImg} alt="payment-handle" width="280px" />
        </Box>
      </Box>
      {isSettlementsGuideModalOpen && (
        <SettlementsGuideModal onDismiss={() => setIsSettlementsGuideModalOpen(false)} />
      )}
    </Box>
  );
};

export default PaymentHandle;
