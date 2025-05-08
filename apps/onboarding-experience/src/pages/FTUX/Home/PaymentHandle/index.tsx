import React, { useState } from 'react';
import { Box, Heading, Text, Link } from '@razorpay/blade/components';
import SettlementsGuideModal from '@FTUX/modals/SettlementsGuideModal';
import PaymentHandleActions from './PaymentHandleActions';

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
        borderRadius="medium"
        backgroundColor="surface.background.primary.subtle"
        marginBottom="spacing.4"
      >
        <img src="" alt="payment-handle" />
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
        alignItems="flex-start"
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
            By default, settlement cycles are 2 days{' '}
            <Link
              variant="button"
              size="small"
              color="neutral"
              onClick={() => setIsSettlementsGuideModalOpen(true)}
            >
              here.
            </Link>
          </Text>
        </Box>
        {/* Desktop-only payment handle image */}
        <Box
          display={{ base: 'none', l: 'block' }}
          width="280px"
          height="126px"
          borderRadius="medium"
          backgroundColor="surface.background.primary.subtle"
        >
          <img src="" alt="payment-handle" />
        </Box>
      </Box>
      {isSettlementsGuideModalOpen && (
        <SettlementsGuideModal onDismiss={() => setIsSettlementsGuideModalOpen(false)} />
      )}
    </Box>
  );
};

export default PaymentHandle;
