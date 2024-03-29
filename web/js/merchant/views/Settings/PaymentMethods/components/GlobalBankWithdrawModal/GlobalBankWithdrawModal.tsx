// Core
import React, { useCallback, useEffect, useState } from 'react';
///- Core

// Redux
import * as actions from 'merchant/reducers/b2bExports/actions';
import { showNotification as showNotificationAction } from 'merchant_common/reducers/notifications';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
///- Redux

// Blade Components
import {
  Button,
  CloseIcon,
  DollarIcon,
  Heading,
  IconButton,
  Spinner,
  Text,
  TextInput,
} from '@razorpay/blade/components';
///- Blade Components

// Styled components
import {
  AmountRow,
  FormRow,
  LoaderOverlay,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Wrapper,
} from './styles';
///- Styled components

// types
import type { BeneficiaryDetailType, GlobalBankWithdrawModalProps } from './types';
///- types

function GlobalBankWithdrawModal({
  isLoading,
  data,
  error,
  onClose,
  onSubmit,
  currency,
  fetchBeneficiaryDetailsPending,
  fetchBeneficiaryDetailsError,
  fetchBeneficiaryDetailsSuccess,
  showNotification,
}: GlobalBankWithdrawModalProps): JSX.Element {
  const [amount, setAmount] = useState(data?.amount || '');
  const [hasAmountError, setAmountError] = useState('');
  const [reason, setReason] = useState(data?.reason || '');
  const [hasReasonError, setReasonError] = useState(false);

  const handleSubmit = () => {
    let hasError = false;
    if (!amount) {
      hasError = true;
      setAmountError('This field is required');
    } else if (isNaN(parseInt(amount, 10))) {
      hasError = true;
      setAmountError('Please enter valid amount');
    } else if (parseInt(amount, 10) < 100) {
      hasError = true;
      setAmountError('Please enter an amount greater than or equal to $100');
    } else {
      setAmountError('');
    }

    if (!reason) {
      hasError = true;
      setReasonError(true);
    } else {
      setReasonError(false);
    }

    if (!hasError) {
      fetchBeneficiaryDetailsSuccess({
        ...data,
        amount,
        reason,
      });
      onSubmit({ ...data, amount, reason });
    }
  };

  const fetchDetails = useCallback(async () => {
    try {
      fetchBeneficiaryDetailsPending();
      const response = await actions.fetchBeneficiaryDetails();
      if (response.success && response.data.account_number) {
        const beneDetails = {
          currency,
          accountNumber: response.data.account_number,
          beneficiaryName: response.data.name,
          bankName: response.data.bank_name,
          bicSwift: response.data.bic_swift,
          fee: response.data.commission_fee,
        };
        fetchBeneficiaryDetailsSuccess(beneDetails);
      } else {
        throw Error('Failed to responsed');
      }
    } catch (err) {
      fetchBeneficiaryDetailsError();
      showNotification({
        type: 'error',
        message: 'No beneficiary account found. Please reach out to your account manager',
      });
    }
  }, [
    currency,
    fetchBeneficiaryDetailsError,
    fetchBeneficiaryDetailsPending,
    fetchBeneficiaryDetailsSuccess,
    showNotification,
  ]);

  useEffect(() => {
    if (!data?.accountNumber && !error) {
      fetchDetails();
    }
  }, [data, error, fetchDetails]);

  useEffect(() => {
    if (error) {
      onClose();
      fetchBeneficiaryDetailsSuccess(null);
    }
  }, [error, fetchBeneficiaryDetailsSuccess, onClose]);

  return (
    <Wrapper>
      <ModalHeader>
        <Heading size="small">Withdraw Money</Heading>
        <IconButton icon={CloseIcon} accessibilityLabel="Close" onClick={onClose} />
      </ModalHeader>
      <ModalBody>
        <FormRow>
          <TextInput
            labelPosition="left"
            label="Account Number"
            value={data?.accountNumber}
            isDisabled
          />
        </FormRow>
        <FormRow>
          <TextInput
            labelPosition="left"
            label="Beneficiary Name"
            value={data?.beneficiaryName}
            isDisabled
          />
        </FormRow>
        <FormRow>
          <TextInput labelPosition="left" label="Bank Name" value={data?.bankName} isDisabled />
        </FormRow>
        <FormRow>
          <TextInput
            labelPosition="left"
            label="BIC Swift Code"
            value={data?.bicSwift}
            isDisabled
          />
        </FormRow>
        <FormRow>
          <Text size="small" color="surface.text.gray.muted">
            For any concern with your above beneficiary account details, please reach out to your
            account manager.
          </Text>
        </FormRow>
        <AmountRow>
          <FormRow>
            <TextInput
              labelPosition="left"
              type="number"
              label="Amount"
              icon={DollarIcon}
              helpText="Please enter an amount greater than or equal to $100"
              isRequired
              necessityIndicator="required"
              value={amount}
              onChange={(e) => setAmount(e.value || '')}
              validationState={hasAmountError ? 'error' : 'none'}
              errorText={hasAmountError}
              autoFocus
              isDisabled={isLoading}
            />
          </FormRow>
          <FormRow>
            <TextInput
              labelPosition="left"
              label="Reason"
              helpText="Please mention the reason(s) for the money withdrawal"
              isRequired
              necessityIndicator="required"
              value={reason}
              onChange={(e) => setReason(e.value || '')}
              validationState={hasReasonError ? 'error' : 'none'}
              errorText={hasReasonError ? 'This field is required' : ''}
              isDisabled={isLoading}
              maxCharacters={250}
            />
          </FormRow>
        </AmountRow>
        {isLoading && (
          <LoaderOverlay>
            <Spinner accessibilityLabel="Loading Account Details" size="xlarge" />
          </LoaderOverlay>
        )}
      </ModalBody>
      <ModalFooter>
        <Button variant="primary" type="button" onClick={handleSubmit} isDisabled={isLoading}>
          Submit
        </Button>
      </ModalFooter>
    </Wrapper>
  );
}

function mapStatesToProps(state: {
  b2bExportsBeneficiary: { isLoading: boolean; data: BeneficiaryDetailType | null; error: boolean };
}) {
  return {
    ...state.b2bExportsBeneficiary,
  };
}

function mapDispatchToProps(dispatch) {
  return bindActionCreators(
    {
      fetchBeneficiaryDetails: actions.fetchBeneficiaryDetails,
      fetchBeneficiaryDetailsPending: actions.fetchBeneficiaryDetailsPending,
      fetchBeneficiaryDetailsSuccess: actions.fetchBeneficiaryDetailsSuccess,
      fetchBeneficiaryDetailsError: actions.fetchBeneficiaryDetailsError,
      showNotification: showNotificationAction,
    },
    dispatch,
  );
}

export default connect(mapStatesToProps, mapDispatchToProps)(GlobalBankWithdrawModal);
