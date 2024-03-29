// Core
import React, { useMemo } from 'react';
///- Core

// Redux
import { connect } from 'react-redux';
///- Redux

// Blade Components
import { Button, CloseIcon, Heading, IconButton, Text } from '@razorpay/blade/components';
///- Blade Components

// Styled Components
import { FormRow, ModalBody, ModalHeader } from './styles';
///- Styled Components

// Types
import type { ConfirmWithdrawalModalProps } from './types';
///- Types

function ConfirmWithdrawalModal({
  amount,
  fee,
  isLoading,
  onClose,
  onSubmit,
}: ConfirmWithdrawalModalProps): JSX.Element {
  const totalAmount = useMemo(() => parseInt(amount, 10) + parseInt(fee, 10) || 0, [amount, fee]);

  return (
    <>
      <ModalHeader>
        <Heading size="small">Confirm Withdrawal</Heading>
        <IconButton icon={CloseIcon} accessibilityLabel="Close" onClick={onClose} />
      </ModalHeader>
      <ModalBody>
        <FormRow>
          <Text>
            You are initiating a withdrawal of <b>${amount}</b> and will be charged <b>${fee}</b> as
            transaction charges.
          </Text>
        </FormRow>
        <FormRow mt="0.5rem">
          <Text>
            <b>${totalAmount}</b> will be debited from your account.
          </Text>
        </FormRow>
        <FormRow flex>
          <Button
            isFullWidth
            type="button"
            variant="secondary"
            onClick={onClose}
            isDisabled={isLoading}
          >
            No, Cancel
          </Button>
          <Button
            isFullWidth
            isLoading={isLoading}
            type="button"
            variant="primary"
            onClick={onSubmit}
          >
            Yes, Proceed
          </Button>
        </FormRow>
      </ModalBody>
    </>
  );
}

export default connect((state) => ({
  amount: state.b2bExportsBeneficiary.data?.amount,
  fee: state.b2bExportsBeneficiary.data?.fee,
  isLoading: state.b2bExportsBeneficiary.isPayoutSubmitting,
}))(ConfirmWithdrawalModal);
