import React, { useState } from 'react';
import { Box, Heading, Text, Link } from '@razorpay/blade/components';
import SettlementsGuideModal from '@FTUX/modals/SettlementsGuideModal';
import PaymentHandleActions from './PaymentHandleActions';
import { isMobileDevice } from '@libs/shared-utils';
import paymentHandleBannerImg from '@OnboardingExperienceAssets/PaymentHandleBanner.svg';
import paymentHandleBannerMobileImg from '@OnboardingExperienceAssets/PaymentHandleBannerMobile.svg';
/**
 * PaymentHandle component displays information about payment handle functionality
 * and provides actions for the user to interact with the payment handle feature.
 */
const PaymentHandle = () => {
  const [isSettlementsGuideModalOpen, setIsSettlementsGuideModalOpen] = useState(false);
  const isMobile = isMobileDevice();

  return (
    <Box data-analytics-name="payment-handle-card">
      {/* Mobile-only payment handle image */}
      <Box
        display={{ base: 'block', l: 'none' }}
        width="100%"
        minHeight="80px"
        marginBottom="spacing.4"
      >
        <img
          src={isMobile ? paymentHandleBannerMobileImg : paymentHandleBannerImg}
          alt="payment-handle"
          width="100%"
        />
      </Box>
      {/* Section heading */}
      <Box
        paddingLeft={{ base: 'spacing.5', m: 'spacing.7' }}
        marginBottom={{ base: 'spacing.5', m: 'spacing.7' }}
      >
        <Heading weight="semibold" size="medium">
          Use your Payment Handle
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
          <Text
            size={isMobile ? 'small' : 'medium'}
            weight="regular"
            color="surface.text.gray.normal"
          >
            Use this personalised link to accept payments instantly from your customers.
          </Text>
          {/* User actions related to payment handle - edit, copy and share */}
          <PaymentHandleActions />
          {/* Settlement information with modal trigger */}
          <Text color="surface.text.gray.muted" size={isMobile ? 'xsmall' : 'small'}>
            Your money will be credited to your account as per your{' '}
            <Link
              variant="button"
              size={isMobile ? 'xsmall' : 'small'}
              onClick={() => setIsSettlementsGuideModalOpen(true)}
              data-analytics-name="payment-handle-settlement-schedule"
            >
              settlement schedule
            </Link>
          </Text>
        </Box>
        {/* Desktop-only payment handle image */}
        <Box display={{ base: 'none', l: 'block' }}>
          <img src={paymentHandleBannerImg} alt="payment-handle" width="280px" />
        </Box>
      </Box>
      {isSettlementsGuideModalOpen && (
        <SettlementsGuideModal onDismiss={() => setIsSettlementsGuideModalOpen(false)} />
      )}
    </Box>
  );
};

export default PaymentHandle;
