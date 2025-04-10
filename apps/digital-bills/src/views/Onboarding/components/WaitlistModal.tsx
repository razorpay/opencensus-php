import React, { useState } from 'react';
import {
  Box,
  Modal,
  Button,
  ModalBody,
  ModalFooter,
  ModalHeader,
  ModalProps,
  ChipGroup,
  Chip,
} from '@razorpay/blade/components';

import {
  STORES_COUNT_OPTIONS,
  STORE_COUNT_OPTIONS_MAP,
} from '@apps/digital-bills/src/views/Onboarding/constants';

type SelectedStoresRangeType = keyof typeof STORE_COUNT_OPTIONS_MAP;

type WaitlistModalProps = {
  modalProps: Omit<ModalProps, 'children'>;
  onSubmit: (storesRange: string) => void;
  isLoading: boolean;
  isInWaitlist: boolean;
};

const WaitlistModal = ({
  modalProps,
  onSubmit,
  isLoading,
  isInWaitlist,
}: WaitlistModalProps): React.ReactElement => {
  const [selectedStoresRange, setSelectedStoresRange] = useState<null | SelectedStoresRangeType>(
    null,
  );

  const handleCancel = () => {
    setSelectedStoresRange(null);
    modalProps.onDismiss();
  };
  const handleSubmit = () => {
    if (selectedStoresRange) {
      onSubmit(selectedStoresRange);
    }
  };
  const { isOpen, onDismiss } = modalProps;

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss}>
      <ModalHeader
        title="Join The Waitlist!"
        subtitle="To better assist your needs, please tell us how many stores do you operate currently:"
      />
      <ModalBody>
        <Box>
          <ChipGroup
            accessibilityLabel="Select your stores count from the options below"
            onChange={(selectedChip) => {
              setSelectedStoresRange(selectedChip?.values[0] as SelectedStoresRangeType);
            }}
            selectionType="single"
            data-analytics-name="stores-count-options"
          >
            {STORES_COUNT_OPTIONS.map((option) => {
              const { label, value } = option;
              return (
                <Chip key={value} value={value} data-analytics-name={`${label}-stores`}>
                  {label}
                </Chip>
              );
            })}
          </ChipGroup>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.5" justifyContent="flex-end" width="100%">
          <Button variant="tertiary" onClick={handleCancel} isDisabled={isLoading} data-analytics-name="cancel">
            Cancel
          </Button>
          <Button
            isDisabled={!selectedStoresRange || isInWaitlist}
            onClick={handleSubmit}
            isLoading={isLoading}
            data-analytics-name="submit"
          >
            Submit
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};
export default WaitlistModal;
