import React, { useState, useEffect, ReactElement, useMemo } from 'react';
import { Button, Divider, Box, Text, TextInput, Skeleton, Link } from '@razorpay/blade/components';
import { useDebounce } from '@libs/shared-utils';
import {
  addPaymentHandleSlugPrefix,
  removePaymentHandleSlugPrefix,
} from '@OnboardingExperienceCommons/utils/paymentHandle';
import { useStore } from '@federated/apps/shell/commonStore';
import { isMobileDevice } from '@libs/shared-utils';
import { useModalComponents } from '@libs/shared-ui';
import { zIndicesMap } from '@OnboardingExperienceCommons/constants/config';

const HANDLE_PREFIX = 'https://razorpay.me/@';

const EditPaymentHandleModal = ({
  onDismiss,
  getHandleSuggestions,
  getPaymentHandleAvailability,
  handleUpdatePaymentHandle,
  currentPaymentHandle = '',
}: {
  onDismiss: () => void;
  getHandleSuggestions: () => Promise<string[]>;
  getPaymentHandleAvailability: (
    handle: string,
  ) => Promise<{ message?: string; isAvailable?: boolean }>;
  handleUpdatePaymentHandle: (paymentHandle: string) => Promise<void>;
  currentPaymentHandle?: string;
}): ReactElement => {
  const showNotification = useStore((state) => state.showNotification);
  const isMobile = isMobileDevice();
  const { Modal, ModalHeader, ModalBody, ModalFooter } = useModalComponents(isMobile);

  const [paymentHandleUrl, setPaymentHandleUrl] = useState(
    () => `${HANDLE_PREFIX}${removePaymentHandleSlugPrefix(currentPaymentHandle)}`,
  );
  const [suggestions, setSuggestions] = useState<string[]>([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isLoadingSuggestions, setIsLoadingSuggestions] = useState(false);
  const [availabilityStatus, setAvailabilityStatus] = useState<{
    message?: string | undefined;
    loading?: boolean;
    isAvailable?: boolean | undefined;
  }>({
    message: undefined,
    loading: false,
    isAvailable: undefined,
  });

  // Extract actual handle value without prefix
  const paymentHandleValue = useMemo(() => {
    return paymentHandleUrl.replace(HANDLE_PREFIX, '');
  }, [paymentHandleUrl]);

  // Handle input change while preserving the prefix
  const handleInputChange = (event: { value?: string }) => {
    const value = event.value || '';

    // Ensure the prefix is always there
    if (!value.startsWith(HANDLE_PREFIX)) {
      setPaymentHandleUrl(HANDLE_PREFIX);
    } else {
      setPaymentHandleUrl(value);
    }
  };

  const handleSave = async () => {
    if (!paymentHandleValue || !availabilityStatus.isAvailable || availabilityStatus.loading)
      return;

    setIsSubmitting(true);
    try {
      await handleUpdatePaymentHandle(paymentHandleValue);
      showNotification({ type: 'success', message: 'Payment handle updated successfully!' });
      onDismiss();
    } catch (error) {
      showNotification({
        type: 'error',
        message:
          error instanceof Error && !!error.message
            ? error.message
            : 'An error occured while updating the payment handle!',
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  // Debounced function to check handle availability
  const checkHandleAvailability = useDebounce(async (handle: string) => {
    if (!handle) {
      setAvailabilityStatus({
        loading: false,
      });
      return;
    }

    try {
      const availability = await getPaymentHandleAvailability(handle);
      setAvailabilityStatus({
        loading: false,
        isAvailable: availability.isAvailable,
        message: availability.message,
      });
    } catch (error) {
      setAvailabilityStatus({
        loading: false,
        isAvailable: false,
        message:
          error instanceof Error
            ? error.message
            : 'Error occured while checking handle availability!',
      });
    }
  }, 500);

  // Load payment handle suggestions on component mount
  useEffect(() => {
    const loadSuggestions = async () => {
      setIsLoadingSuggestions(true);
      try {
        const result = await getHandleSuggestions();
        if (result?.length > 0) setSuggestions(result);
      } catch (error) {
        console.error('Error fetching suggestions:', error);
      } finally {
        setIsLoadingSuggestions(false);
      }
    };

    loadSuggestions();
  }, []);

  // Check handle availability when payment handle changes
  useEffect(() => {
    const handleWithSuffix = addPaymentHandleSlugPrefix(paymentHandleValue);

    // Do not check availability for existing handle
    if (currentPaymentHandle === handleWithSuffix) {
      setAvailabilityStatus({
        loading: false,
      });
      return;
    }

    // Mark suggestion as available directly if it is in the suggestions list
    if (suggestions.includes(handleWithSuffix)) {
      setAvailabilityStatus({
        isAvailable: true,
        message: `${handleWithSuffix} is available`,
      });
      return;
    }

    setAvailabilityStatus((status) => ({
      ...status,
      loading: true,
    }));
    checkHandleAvailability(paymentHandleValue);
  }, [paymentHandleUrl, suggestions]);

  return (
    <Modal
      isOpen={true}
      onDismiss={onDismiss}
      snapPoints={[1, 1, 1]}
      zIndex={zIndicesMap.modal}
      data-analytics-name="edit-payment-handle-modal"
    >
      <ModalHeader
        title="Edit your Razorpay.me link"
        subtitle="Change your handle to whatever you like"
      />
      <ModalBody>
        <Box display="flex" flexDirection="column" gap="spacing.4" width="100%">
          <TextInput
            label="Your handle"
            value={paymentHandleUrl}
            onChange={handleInputChange}
            helpText="We will let you know whats available"
            successText={availabilityStatus.message || 'This handle is available'}
            errorText={availabilityStatus.message || 'This handle is not available'}
            validationState={
              availabilityStatus.isAvailable === false
                ? 'error'
                : availabilityStatus.isAvailable
                ? 'success'
                : 'none'
            }
            isLoading={availabilityStatus.loading}
          />

          {isLoadingSuggestions ? (
            <Skeleton height="45px" width="100%" />
          ) : (
            suggestions.length > 0 && (
              <Box display="flex" flexDirection="column" width="100%" gap="spacing.4">
                <Divider />
                <Text size="small" color="surface.text.gray.subtle">
                  Available suggestions are{' '}
                  {suggestions.map((suggestion, index) => (
                    <React.Fragment key={suggestion}>
                      <Link
                        variant="button"
                        onClick={() =>
                          setPaymentHandleUrl(
                            `${HANDLE_PREFIX}${removePaymentHandleSlugPrefix(suggestion)}`,
                          )
                        }
                        data-analytics-name="payment-handle-suggestion"
                      >
                        {suggestion}
                      </Link>
                      {index < suggestions.length - 1 && ', '}
                    </React.Fragment>
                  ))}
                </Text>
              </Box>
            )
          )}
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" flexDirection="row" gap="spacing.3" justifyContent="flex-end">
          <Button
            variant="tertiary"
            onClick={onDismiss}
            isFullWidth={isMobile ? true : false}
            data-analytics-name="edit-payment-handle-cancel"
          >
            Cancel
          </Button>
          <Button
            variant="primary"
            onClick={handleSave}
            isLoading={isSubmitting}
            isDisabled={
              !paymentHandleValue ||
              !availabilityStatus.isAvailable ||
              availabilityStatus.loading ||
              paymentHandleValue.length < 3
            }
            isFullWidth={isMobile ? true : false}
            data-analytics-name="edit-payment-handle-save"
          >
            Save Changes
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default EditPaymentHandleModal;
