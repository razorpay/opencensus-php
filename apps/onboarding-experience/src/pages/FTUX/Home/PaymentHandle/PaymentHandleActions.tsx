import React, { useEffect, useState } from 'react';
import {
  Box,
  Text,
  CopyIcon,
  Link,
  Spinner,
  Tooltip,
  EditIcon,
  ShareIcon,
  CheckIcon,
} from '@razorpay/blade/components';
import { copyToClipboard, isMobileDevice } from '@libs/shared-utils';
import useMerchantPaymentHandle from 'apps/onboarding-experience/src/common/hooks/useMerchantPaymentHandle';

/**
 * PaymentHandleActions component displays the merchant's payment handle with copy, edit, and share actions
 * Allows merchants to interact with their payment handle directly from the dashboard
 */
function PaymentHandleActions() {
  const isMobile = isMobileDevice();
  // Custom hook to fetch merchant payment handle data
  const { paymentHandleData, isPaymentHandleLoading, fetchPaymentHandle } =
    useMerchantPaymentHandle();
  const [isCopied, setIsCopied] = useState(false);

  // Extract active payment handle and URL from the response data
  const activeHandle = paymentHandleData?.merchantPaymentHandle?.paymentHandle?.paymentHandleSlug;
  const activeHandleUrl = paymentHandleData?.merchantPaymentHandle?.paymentHandle?.url;

  /**
   * Copies payment handle URL to clipboard and updates UI state
   */
  const handleCopy = async () => {
    try {
      copyToClipboard(activeHandleUrl || '');
      setIsCopied(true);
    } catch (error: unknown) {
      // Handle the error if the copy fails
      console.error('Failed to copy text:', error);
    }
  };

  useEffect(() => {
    fetchPaymentHandle();
  }, []);

  return (
    <Box
      display="flex"
      flexDirection={{
        base: 'column',
        m: 'row',
      }}
      alignItems="center"
      gap="spacing.5"
      alignSelf="stretch"
    >
      <Box
        display="flex"
        padding="spacing.5"
        justifyContent="space-between"
        alignItems="center"
        gap="spacing.4"
        borderRadius="medium"
        backgroundColor="surface.background.gray.subtle"
        alignSelf="stretch"
        width={{ base: '100%', m: '350px' }}
      >
        {isPaymentHandleLoading ? (
          <Spinner accessibilityLabel="payment-url" color="primary" />
        ) : !activeHandle ? (
          <Text weight="semibold" color="surface.text.primary.normal">
            No payment handle found
          </Text>
        ) : (
          <Text
            truncateAfterLines={1}
            size="medium"
            weight="semibold"
            color="surface.text.primary.normal"
          >
            {activeHandle}
          </Text>
        )}
        <Tooltip content={isCopied ? 'Copied!' : 'Click to copy'}>
          <Link
            isDisabled={!activeHandleUrl || isPaymentHandleLoading}
            size="large"
            icon={isCopied ? CheckIcon : CopyIcon}
            onClick={handleCopy}
          />
        </Tooltip>
      </Box>

      {/* Action buttons for edit and share functionality */}
      <Box display="flex" gap="spacing.5" alignItems="flex-start">
        <Link
          isDisabled={!activeHandle || isPaymentHandleLoading}
          size="large"
          children={isMobile ? 'Edit' : ''}
          icon={EditIcon}
          color="neutral"
        />
        <Link
          isDisabled={!activeHandle || isPaymentHandleLoading}
          size="large"
          children={isMobile ? 'Share' : ''}
          icon={ShareIcon}
          color="neutral"
        />
      </Box>
    </Box>
  );
}

export default PaymentHandleActions;
