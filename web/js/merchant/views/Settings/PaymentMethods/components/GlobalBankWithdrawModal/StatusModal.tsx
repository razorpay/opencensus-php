// Core
import React from 'react';
///- Core

// Redux
import { connect } from 'react-redux';
///- Redux

// Blade Components
import { Button, Heading, Text } from '@razorpay/blade/components';
///- Blade Components

// Styled Components
import { FormRow, ModalBody } from './styles';
///- Styled Components

// Types
import type { StatusModalProps } from './types';
///- Types

// Assets
import ErrorIcon from 'assets/error-icon.svg';
import SuccessIcon from 'assets/success-tick-green.svg';
///- Assets

function StatusModal({ type, onClose, onTryAgain }: StatusModalProps): JSX.Element {
  return (
    <ModalBody small>
      {type === 'error' ? (
        <>
          <FormRow textCenter mt>
            <img width="50" height="50" src={ErrorIcon} alt="Withdrawal failed" />
          </FormRow>
          <FormRow textCenter>
            <Heading size="medium">Oh, snap!</Heading>
          </FormRow>
          <FormRow textCenter>
            <Text>An error has occurred in the process of money withdrawal from your account.</Text>
          </FormRow>
          <FormRow>
            <Button isFullWidth type="button" variant="primary" onClick={onTryAgain}>
              Try Again
            </Button>
          </FormRow>
          <FormRow>
            <Button isFullWidth type="button" variant="secondary" onClick={onClose}>
              Close
            </Button>
          </FormRow>
        </>
      ) : (
        <>
          <FormRow textCenter mt="2rem" mb="2rem">
            <img width="45" src={SuccessIcon} alt="Withdrawal Success" />
          </FormRow>
          <FormRow textCenter>
            <Heading size="medium">Withdrawal successful</Heading>
          </FormRow>
          <FormRow>
            <Button isFullWidth type="button" variant="primary" onClick={onClose}>
              Done
            </Button>
          </FormRow>
        </>
      )}
    </ModalBody>
  );
}

export default connect((state) => ({
  amount: state.b2bExportsBeneficiary.data?.amount,
  fee: state.b2bExportsBeneficiary.data?.fee,
}))(StatusModal);
