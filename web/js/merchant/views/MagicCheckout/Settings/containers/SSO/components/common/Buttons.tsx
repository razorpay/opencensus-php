import React from 'react';
import { Box, Button } from '@razorpay/blade/components';

type SSOButtonsProps = {
  onReset: () => void;
  onSaveChanges: () => void;
  onNext: () => void;
  isLoading?: boolean;
}

type ResetButtonProps = {
  onReset: () => void;
  isLoading?: boolean;
}

type SaveButtonProps = {
  onSaveChanges: () => void;
  isLoading?: boolean;
}

type NextButtonProps = {
  onNext: () => void;
  isLoading?: boolean;
}

// Reset Button
const ResetButton: React.FC<ResetButtonProps> = React.memo(({ onReset, isLoading }) => (
  <Button onClick={onReset} marginRight="spacing.4" variant="tertiary" isDisabled={isLoading}>
    Reset
  </Button>
));

// Save Button
const SaveButton: React.FC<SaveButtonProps> = React.memo(({ onSaveChanges, isLoading }) => (
  <Button
    onClick={onSaveChanges}
    variant="secondary"
    color="primary"
    marginRight="spacing.4"
    isDisabled={isLoading}
  >
    Save Changes
  </Button>
));

// Next Button
const NextButton: React.FC<NextButtonProps> = React.memo(({ onNext, isLoading }) => (
  <Button onClick={onNext} variant="primary" isDisabled={isLoading} isLoading={isLoading}>
    Next
  </Button>
));

const SSOButtons: React.FC<SSOButtonsProps> = React.memo(
  ({ onReset, onSaveChanges, onNext, isLoading = false }) => {
    return (
      <Box
        display="flex"
        justifyContent="flex-end"
        marginRight="spacing.6"
        marginTop="spacing.10"
        marginBottom="spacing.6"
      >
        <ResetButton
          onReset={onReset}
          isLoading={isLoading}
        />
        <SaveButton
          onSaveChanges={onSaveChanges}
          isLoading={isLoading}
        />
        <NextButton
          onNext={onNext}
          isLoading={isLoading}
        />
      </Box>
    );
  },
);

export default SSOButtons;
