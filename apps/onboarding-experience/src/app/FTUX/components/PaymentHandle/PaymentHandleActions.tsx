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
import { copyToClipboard, isMobileDevice, shareContent } from '@libs/shared-utils';
import useMerchantPaymentHandle from '@OnboardingExperienceCommons/hooks/useMerchantPaymentHandle';
import SharePaymentHandleModal from '@OnboardingExperienceCommons/components/SharePaymentHandleModal';
import { removePaymentHandleSlugPrefix } from '@OnboardingExperienceCommons/utils/paymentHandle';
import EditPaymentHandleModal from '@OnboardingExperienceCommons/components/EditPaymentHandleModal';
import { useStore } from '@federated/apps/shell/commonStore';

/**
 * PaymentHandleActions component displays the merchant's payment handle with copy, edit, and share actions
 * Allows merchants to interact with their payment handle directly from the dashboard
 */
function PaymentHandleActions() {
  const isMobile = isMobileDevice();
  // Custom hook to fetch merchant payment handle data
  const {
    paymentHandleData,
    isPaymentHandleLoading,
    fetchPaymentHandle,
    mutateEncryptedAmount,
    updatePaymentHandle,
    fetchHandleSuggestions,
    fetchHandleAvailability,
  } = useMerchantPaymentHandle();
  const showNotification = useStore((state) => state.showNotification);
  const [isCopied, setIsCopied] = useState(false);
  const [isShareModalOpen, setIsShareModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);

  // Extract active payment handle and URL from the response data
  const activeHandle = paymentHandleData?.merchantPaymentHandle?.paymentHandle?.paymentHandleSlug;
  const activeHandleUrl = paymentHandleData?.merchantPaymentHandle?.paymentHandle?.url;

  /**
   * Handles sharing of payment handle URL with optional amount parameter
   * - Mobile: Uses native share functionality
   * - Desktop: Copies to clipboard with copy feedback
   */
  const handlePaymentShare = async ({ amount }: { amount?: string }): Promise<void> => {
    if (!activeHandleUrl) {
      throw new Error('No Payment handle present!');
    }
    let shareLink = activeHandleUrl;
    /**
     * If amount is provided,it is encrypted using mutateEncryptedAmount
     *    - On success: Creates a new URL with encrypted amount
     *    - Else Throws error
     */
    if (amount) {
      try {
        const resp = await mutateEncryptedAmount({ amount });
        if (!resp?.merchantPaymentHandleEncryptedAmount?.success) {
          throw new Error(
            'An error occured while generating payment share link - Please try again later!',
          );
        }
        shareLink = `${activeHandleUrl}?amount=${resp.merchantPaymentHandleEncryptedAmount.encryptedAmount}`;
      } catch (error) {
        throw new Error(
          'An error occured while generating payment share link - Please try again later!',
        );
      }
    }
    /**
     * Copies/Shares the payment handle URL based on the device type
     */
    if (isMobile) {
      await shareContent({ url: shareLink, title: 'Accept payments with your payment handle' });
      copyToClipboard(shareLink);
    } else {
      copyToClipboard(shareLink);
    }
  };

  /**
   * Copies payment handle URL to clipboard and updates UI state
   */
  const handleCopy = () => {
    copyToClipboard(activeHandleUrl || '');
    setIsCopied(true);
  };

  const getHandleSuggestions = async () => {
    try {
      const resp = await fetchHandleSuggestions();
      return resp?.merchantPaymentHandleSuggestions?.suggestions || [];
    } catch (_) {
      return [];
    }
  };

  const getPaymentHandleAvailability = async (handle: string) => {
    if (handle === removePaymentHandleSlugPrefix(activeHandle || '')) return null;
    try {
      const resp = await fetchHandleAvailability(handle);
      const isAvailable = resp?.merchantPaymentHandleAvailability?.isPaymentHandleAvailable;
      return typeof isAvailable === 'boolean' ? isAvailable : null;
    } catch (err) {
      return null;
    }
  };

  const handleUpdatePaymentHandle = async (handle: string) => {
    try {
      const resp = await updatePaymentHandle({ handle });
      if (!resp?.merchantPaymentHandleUpdate?.success) {
        throw new Error('Unable to update payment handle!');
      }
    } catch (err) {
      throw new Error('Unexpected error while updating payment handle! - Please try again later!');
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
        width="350px"
      >
        {isPaymentHandleLoading ? (
          <Spinner accessibilityLabel="payment-url" color="primary" />
        ) : !activeHandleUrl ? (
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
            {activeHandleUrl?.split('https://')?.[1]}
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
          variant="button"
          onClick={() => setIsEditModalOpen(true)}
        />
        <Link
          isDisabled={!activeHandle || isPaymentHandleLoading}
          onClick={() => setIsShareModalOpen(true)}
          size="large"
          children={isMobile ? 'Share' : ''}
          icon={ShareIcon}
          color="neutral"
        />
      </Box>
      {isShareModalOpen && (
        <SharePaymentHandleModal
          paymentUrl={activeHandleUrl as string}
          onDismiss={() => setIsShareModalOpen(false)}
          onPaymentShare={handlePaymentShare}
        />
      )}
      {isEditModalOpen && (
        <EditPaymentHandleModal
          onDismiss={() => setIsEditModalOpen(false)}
          getHandleSuggestions={getHandleSuggestions}
          getPaymentHandleAvailability={getPaymentHandleAvailability}
          handleUpdatePaymentHandle={handleUpdatePaymentHandle}
          currentPaymentHandle={activeHandle}
        />
      )}
    </Box>
  );
}

export default PaymentHandleActions;
