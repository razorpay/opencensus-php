import React, { useEffect, useState, useRef } from 'react';
import errorService from '@razorpay/universe-cli/errorService';
import {
  Box,
  Text,
  CopyIcon,
  Link,
  Spinner,
  Tooltip,
  EditIcon,
  ShareIcon,
  CheckCircleIcon,
} from '@razorpay/blade/components';
import { copyToClipboard, isMobileDevice, shareContent } from '@libs/shared-utils';
import useMerchantPaymentHandle from '@OnboardingExperienceCommons/hooks/useMerchantPaymentHandle';
import SharePaymentHandleModal from '@OnboardingExperienceCommons/components/SharePaymentHandleModal';
import { removePaymentHandleSlugPrefix } from '@OnboardingExperienceCommons/utils/paymentHandle';
import EditPaymentHandleModal from '@OnboardingExperienceCommons/components/EditPaymentHandleModal';

// Cache structure for storing encrypted amounts to prevent redundant API calls
interface EncryptedAmountCache {
  [amount: string]: string;
}

/**
 * PaymentHandleActions component displays the merchant's payment handle with copy, edit, and share actions.
 *
 * Features:
 * - Displays the merchant's payment handle URL
 * - Provides copy to clipboard functionality
 * - Allows editing the payment handle through a modal
 * - Enables sharing the payment handle with optional amount parameter
 * - Handles different device types (mobile/desktop) with appropriate sharing methods
 */
function PaymentHandleActions() {
  const isMobile = isMobileDevice();
  // Custom hook to manage all payment handle related operations and data
  const {
    paymentHandleData,
    isPaymentHandleLoading,
    fetchPaymentHandle,
    mutateEncryptedAmount,
    updatePaymentHandle,
    fetchHandleSuggestions,
    fetchHandleAvailability,
  } = useMerchantPaymentHandle();
  const [isCopied, setIsCopied] = useState(false);
  const [isShareModalOpen, setIsShareModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  // Cache encrypted amounts to avoid redundant API calls for the same amount
  const encryptedAmountCache = useRef<EncryptedAmountCache>({});

  // Extract relevant data from the API response
  const activeHandle = paymentHandleData?.merchantPaymentHandle?.paymentHandle?.paymentHandleSlug;
  const activeHandleUrl = paymentHandleData?.merchantPaymentHandle?.paymentHandle?.url;
  const isDisabled = isPaymentHandleLoading || !activeHandleUrl || !activeHandle;

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

    // Process amount parameter if provided
    if (amount && encryptedAmountCache.current[amount]) {
      // Use cached encrypted amount if available
      shareLink = `${activeHandleUrl}?amount=${encryptedAmountCache.current[amount]}`;
    } else if (amount) {
      try {
        // Get encrypted amount from API
        const resp = await mutateEncryptedAmount({ amount });
        if (
          !resp?.merchantPaymentHandleEncryptedAmount?.success ||
          !resp?.merchantPaymentHandleEncryptedAmount?.encryptedAmount
        ) {
          throw new Error(resp?.merchantPaymentHandleEncryptedAmount?.message);
        }

        const encryptedAmount = resp.merchantPaymentHandleEncryptedAmount.encryptedAmount;
        // Cache the encrypted amount for future use
        encryptedAmountCache.current[amount] = encryptedAmount;
        shareLink = `${activeHandleUrl}?amount=${encryptedAmount}`;
      } catch (error) {
        errorService.captureError(error, {
          tags: { module: 'FTUX_PAYMENT_HANDLE' },
          rank: errorService.ErrorRank.P0,
          extra: {
            info: error,
          },
        });

        throw new Error(
          'An error occured while generating payment share link - Please try again later!',
        );
      }
    }
    /**
     * Copies/Shares the payment handle URL based on the device type
     */
    if (isMobile) {
      // On mobile, use native sharing and fallback to clipboard
      await shareContent({ url: shareLink, title: 'Accept payments with your payment handle' });
      copyToClipboard(shareLink);
    } else {
      // On desktop, just copy to clipboard
      copyToClipboard(shareLink);
    }
  };

  /**
   * Copies payment handle URL to clipboard and updates UI state to show feedback
   */
  const handleCopy = () => {
    copyToClipboard(activeHandleUrl || '');
    setIsCopied(true);
  };

  /**
   * Fetches payment handle suggestions from the API
   * @returns {Promise<string[]>} Array of suggested payment handles
   */
  const getHandleSuggestions = async () => {
    try {
      const resp = await fetchHandleSuggestions();
      return resp?.merchantPaymentHandleSuggestions?.suggestions || [];
    } catch (error) {
      errorService.captureError(error, {
        tags: { module: 'FTUX_PAYMENT_HANDLE' },
        rank: errorService.ErrorRank.P0,
        extra: {
          info: error,
        },
      });

      return [];
    }
  };

  /**
   * Checks if a payment handle is available for use
   * @param {string} handle - The payment handle to check (without prefix)
   * @returns {Promise<{isAvailable: boolean, message?: string}>} Availability status and optional message
   * @throws {Error} If availability check fails
   */
  const getPaymentHandleAvailability = async (handle: string) => {
    // Skip API call if checking the current handle
    if (handle === removePaymentHandleSlugPrefix(activeHandle || '')) return { isAvailable: true };
    try {
      const resp = await fetchHandleAvailability(handle);
      const handleAvailability = resp?.merchantPaymentHandleAvailability;
      return {
        message: handleAvailability?.message,
        isAvailable: handleAvailability.success
          ? handleAvailability?.isPaymentHandleAvailable
          : false,
      };
    } catch (err) {
      errorService.captureError(err, {
        tags: { module: 'FTUX_PAYMENT_HANDLE' },
        rank: errorService.ErrorRank.P0,
        extra: {
          info: err,
        },
      });

      throw new Error('Error occured while checking handle availability!');
    }
  };

  /**
   * Updates the merchant's payment handle
   * @param {string} handle - The new payment handle to set
   * @returns {Promise<void>}
   * @throws {Error} If update operation fails
   */
  const handleUpdatePaymentHandle = async (handle: string) => {
    try {
      const resp = await updatePaymentHandle({ handle });
      if (!resp?.merchantPaymentHandleUpdate?.success) {
        throw new Error('Unable to update payment handle!');
      }
    } catch (err) {
      errorService.captureError(err, {
        tags: { module: 'FTUX_PAYMENT_HANDLE' },
        rank: errorService.ErrorRank.P0,
        extra: {
          info: err,
        },
      });
      throw new Error('Unexpected error while updating payment handle! - Please try again later!');
    }
  };

  // Reset copy feedback after 3 seconds
  useEffect(() => {
    let timeoutId: NodeJS.Timeout;
    if (isCopied) {
      timeoutId = setTimeout(() => {
        setIsCopied(false);
      }, 3000);
    }
    return () => {
      if (timeoutId) clearTimeout(timeoutId);
    };
  }, [isCopied]);

  // Fetch payment handle data on component mount
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
      data-analytics-name="payment-handle-actions"
    >
      {/* Payment handle display with copy action */}
      <Box
        display="flex"
        padding="spacing.5"
        justifyContent="space-between"
        alignItems="center"
        gap="spacing.4"
        borderRadius="medium"
        backgroundColor="surface.background.gray.subtle"
        alignSelf="stretch"
        width={{
          base: '100%',
          m: '350px',
        }}
      >
        {isPaymentHandleLoading ? (
          <Spinner accessibilityLabel="payment-url" color="primary" />
        ) : !activeHandleUrl ? (
          <Text
            weight="semibold"
            size={isMobile ? 'small' : 'medium'}
            color="surface.text.gray.muted"
          >
            No payment handle found
          </Text>
        ) : (
          <Text
            truncateAfterLines={1}
            size={isMobile ? 'small' : 'medium'}
            weight="semibold"
            color="surface.text.primary.normal"
          >
            {activeHandleUrl?.split('https://')?.[1]}
          </Text>
        )}
        <Tooltip content={isCopied ? 'Copied!' : 'Click to copy'}>
          <Link
            isDisabled={isDisabled}
            size="large"
            variant="button"
            icon={isCopied ? CheckCircleIcon : CopyIcon}
            onClick={handleCopy}
            color={isCopied ? 'positive' : 'primary'}
            data-analytics-name="copy-payment-handle-url"
          />
        </Tooltip>
      </Box>

      {/* Action buttons for edit and share functionality */}
      <Box display="flex" gap="spacing.5" alignItems="flex-start">
        <Tooltip content="Edit payment handle">
          <Link
            isDisabled={isDisabled}
            size={isMobile ? 'medium' : 'large'}
            children={isMobile ? 'Edit' : ''}
            icon={EditIcon}
            color="neutral"
            variant="button"
            onClick={() => setIsEditModalOpen(true)}
            data-analytics-name="edit-payment-handle-url"
          />
        </Tooltip>
        <Tooltip content="Share payment handle">
          <Link
            isDisabled={isDisabled}
            onClick={() => setIsShareModalOpen(true)}
            size={isMobile ? 'medium' : 'large'}
            children={isMobile ? 'Share' : ''}
            icon={ShareIcon}
            variant="button"
            color="neutral"
            data-analytics-name="share-payment-handle-url"
          />
        </Tooltip>
      </Box>
      {/* Modals for share and edit functionality */}
      {isShareModalOpen && (
        <SharePaymentHandleModal
          paymentUrl={activeHandle as string}
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
