import React, { useState, useEffect, ReactElement } from 'react';
import {
  Button,
  Divider,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Box,
  Text,
  TextInput,
  Skeleton,
  Link,
} from '@razorpay/blade/components';
import { useDebounce } from '@libs/shared-utils';
import { removePaymentHandleSlugPrefix } from '@OnboardingExperienceCommons/utils/paymentHandle';
import { useStore } from '@federated/apps/shell/commonStore';

const EditPaymentHandleModal = ({
  onDismiss,
  getHandleSuggestions,
  getPaymentHandleAvailability,
  handleUpdatePaymentHandle,
  currentPaymentHandle = '',
}: {
  onDismiss: () => void;
  getHandleSuggestions: () => Promise<string[]>;
  getPaymentHandleAvailability: (handle: string) => Promise<boolean | null>;
  handleUpdatePaymentHandle: (paymentHandle: string) => Promise<void>;
  currentPaymentHandle?: string;
}): ReactElement => {
  const showNotification = useStore((state) => state.showNotification);
  const [paymentHandle, setPaymentHandle] = useState(() =>
    removePaymentHandleSlugPrefix(currentPaymentHandle),
  );
  const [isAvailable, setIsAvailable] = useState<boolean | null>(null);
  const [suggestions, setSuggestions] = useState<string[]>([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isCheckingAvailability, setIsCheckingAvailability] = useState(false);
  const [isLoadingSuggestions, setIsLoadingSuggestions] = useState(false);

  const handleSave = async () => {
    if (!paymentHandle || !isAvailable || isCheckingAvailability) return;

    setIsSubmitting(true);
    try {
      await handleUpdatePaymentHandle(paymentHandle);
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
      setIsAvailable(null);
      setIsCheckingAvailability(false);
      return;
    }

    try {
      const availability = await getPaymentHandleAvailability(handle);
      setIsAvailable(availability);
    } catch (error) {
      setIsAvailable(null);
    } finally {
      setIsCheckingAvailability(false);
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
    setIsCheckingAvailability(true);
    checkHandleAvailability(paymentHandle);
  }, [paymentHandle]);

  return (
    <Modal isOpen={true} onDismiss={onDismiss}>
      <ModalHeader
        title="Edit your Razorpay.me link"
        subtitle="Change your handle to whatever you like"
      />
      <ModalBody>
        <Box display="flex" flexDirection="column" gap="spacing.4" width="100%">
          <TextInput
            label="Your handle"
            prefix="https://razorpay.me/@"
            value={paymentHandle}
            onChange={({ value }) => setPaymentHandle(value || '')}
            helpText="We will let you know whats available"
            successText="This handle is available!"
            errorText={
              paymentHandle.length < 3
                ? 'Handle should have atleast 3 characters!'
                : 'This handle is not available'
            }
            validationState={
              isAvailable === false || paymentHandle.length < 3
                ? 'error'
                : isAvailable
                ? 'success'
                : 'none'
            }
            isLoading={isCheckingAvailability}
          />

          {isLoadingSuggestions ? (
            <Skeleton height="34px" width="100%" />
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
                        onClick={() => setPaymentHandle(removePaymentHandleSlugPrefix(suggestion))}
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
        <Box display="flex" gap="spacing.3" justifyContent="flex-end">
          <Button variant="tertiary" onClick={onDismiss}>
            Cancel
          </Button>
          <Button
            variant="primary"
            onClick={handleSave}
            isLoading={isSubmitting}
            isDisabled={
              !paymentHandle || !isAvailable || isCheckingAvailability || paymentHandle.length < 3
            }
          >
            Save Changes
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default EditPaymentHandleModal;
