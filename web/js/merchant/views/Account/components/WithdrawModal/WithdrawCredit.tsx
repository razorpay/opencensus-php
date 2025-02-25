import React, { useEffect, useState } from 'react';
import { bindActionCreators, compose } from 'redux';
import { connect } from 'react-redux';
import SuccessIcon from 'assets/success-star.svg';
import {
  Box,
  Button,
  Chip,
  ChipGroup,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Text,
  TextInput,
  Amount,
  BankIcon,
  TextInputProps,
  ChipGroupProps,
} from '@razorpay/blade/components';
import BankAccountDisplay from './BankAccountDisplay';
import { fetchBankAccount as fetchBankAccountReducer } from 'merchant/reducers/profile';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  i18nifyConvertToMajorUnit,
  i18nifyConvertToMinorUnit,
} from 'merchant/views/Transactions/v2/common/utils';
import currencies from 'merchant/constants/currency';
import { DASHBOARD_ZINDEX_MAP } from '@libs/shared-utils';
import { useStore } from '@federated/apps/shell/commonStore';
import { useMutation } from '@tanstack/react-query';
import { CurrencyCodeType } from '@razorpay/i18nify-js/currency';

const errorType = {
  balanceExceeded: 'Withdrawal must be within your current balance',
};

interface WithdrawCreditsModalProps {
  title: string;
  credits: number;
  open: boolean;
  type: string;
  profile: any;
  submitHandler: () => void | (() => Promise<any>);
  fetchBankAccount: () => Promise<any>;
  closeModal: () => void;
}

function WithdrawCredits({
  title,
  credits,
  open,
  profile,
  type,
  fetchBankAccount,
  closeModal,
  submitHandler,
}: WithdrawCreditsModalProps) {
  const {
    showNotification,
    session: {
      user: { merchant },
    },
  } = useStore();
  const [error, setError] = useState('');
  const [fieldValue, setValue] = useState('');
  const [success, setSuccess] = useState(false);
  const [fraction, setFraction] = useState('');
  const { currency } = merchant!;

  useEffect(() => {
    if (!profile.bankAccount?.account_number) {
      fetchBankAccount();
    }
  }, [fetchBankAccount, profile.bankAccount]);

  const withdrawalMutation = useMutation(
    (amount: number) =>
      merchantFetch({
        method: 'post',
        url: `merchants/pre_fund/withdraw`,
        data: {
          amount: i18nifyConvertToMinorUnit(amount, currency as CurrencyCodeType),
          type: type,
        },
      }),
    {
      onSuccess: () => {
        setSuccess(true);
      },
      onError: (error: any) => {
        showNotification({ type: 'error', message: error.errors[0] });
        setSuccess(false);
      },
    },
  );

  const handleWithdrawal = () => {
    const amount = parseFloat(fieldValue);
    withdrawalMutation.mutate(amount);
  };

  const fieldChange: TextInputProps['onChange'] = (field) => {
    const { value } = field;

    if (fraction !== '') {
      setFraction('');
    }

    /**
     * _ = whitespace
     *
     * Sample Input/Output 1: 1234..6 => 1234.6
     * Sample Input/Output 2: 1234___ => 1234
     */

    let sanitizedValue = value!
      .trim()
      .replace(/[^0-9.]/g, '')
      .replace(/(\..*)\./g, '$1');
    setValue(sanitizedValue);
    if (sanitizedValue === '') {
      setError('');
      return;
    }
    const finalValue = parseFloat(sanitizedValue);
    if (finalValue > i18nifyConvertToMajorUnit(credits, currency as CurrencyCodeType)) {
      setError(errorType.balanceExceeded);
    } else {
      setError('');
    }
  };

  const handleWithrawalFractionChange: ChipGroupProps['onChange'] = (field) => {
    setError('');
    const { values } = field;
    const value = values![0];
    const creds = i18nifyConvertToMajorUnit(credits, currency as CurrencyCodeType);
    const finalVal = (creds * (parseInt(value) / 100)).toFixed(2);
    setFraction(value);
    setValue(finalVal);
  };

  const handleDismiss = () => {
    if (success) {
      submitHandler();
    }
    if (!withdrawalMutation.isLoading) {
      closeModal();
    }
  };

  const isWithdrawButtonDisabled =
    fieldValue === '' ||
    parseFloat(fieldValue) <= 0 ||
    error !== '' ||
    isNaN(parseFloat(fieldValue)) ||
    withdrawalMutation.isLoading;

  return (
    <>
      {!success ? (
        <Modal
          zIndex={DASHBOARD_ZINDEX_MAP.modal}
          onDismiss={handleDismiss}
          isOpen={open}
          size="small"
        >
          <ModalHeader
            title="Withdraw Funds"
            subtitle="This money will transferred to you in 24 hours"
          />
          <ModalBody>
            <Box display="flex" flexDirection="column" gap="spacing.7">
              <Box display="flex" justifyContent="space-between" width="100%">
                <Box paddingY="spacing.3" display="flex" gap="spacing.3" alignItems="center">
                  <BankIcon />
                  <Text size="medium">{title}</Text>
                </Box>
                <Box paddingY="spacing.3" display="flex" gap="spacing.3" alignItems="center">
                  <Amount
                    size="large"
                    type="body"
                    weight="semibold"
                    value={i18nifyConvertToMajorUnit(credits, currency as CurrencyCodeType)}
                    currency={currency as CurrencyCodeType}
                  />
                </Box>
              </Box>
              <Box width="100%" display="flex" flexDirection="column" gap="spacing.8">
                <Box width="100%" display="flex" flexDirection="column" gap="spacing.3">
                  <TextInput
                    onChange={fieldChange}
                    isDisabled={withdrawalMutation.isLoading}
                    value={fieldValue}
                    validationState={error ? 'error' : 'none'}
                    errorText={error || ''}
                    name="credit-withdraw"
                    size="large"
                    label="Enter Amount to Withdraw"
                    prefix={currencies[currency]?.symbol}
                    placeholder="00000"
                  />
                  <ChipGroup
                    label=""
                    value={fraction}
                    onChange={handleWithrawalFractionChange}
                    isDisabled={withdrawalMutation.isLoading}
                  >
                    <Chip value="100">100%</Chip>
                    <Chip value="50">50%</Chip>
                    <Chip value="25">25%</Chip>
                  </ChipGroup>
                </Box>
                <Box width="100%" display="flex" flexDirection="column" gap="spacing.3">
                  <Text size="medium" weight="semibold" color="surface.text.gray.subtle">
                    Funds will be transferred in this bank account
                  </Text>
                  <BankAccountDisplay />
                </Box>
              </Box>
            </Box>
          </ModalBody>
          <ModalFooter>
            <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
              <Button variant="tertiary" onClick={handleDismiss}>
                Cancel
              </Button>
              <Button
                isDisabled={isWithdrawButtonDisabled}
                isLoading={withdrawalMutation.isLoading}
                onClick={handleWithdrawal}
                variant="primary"
              >
                Withdraw
              </Button>
            </Box>
          </ModalFooter>
        </Modal>
      ) : (
        <Modal
          zIndex={DASHBOARD_ZINDEX_MAP.modal}
          isOpen={open}
          onDismiss={handleDismiss}
          size="small"
        >
          <ModalHeader
            title="Withdrawal in Progress"
            subtitle={`${currencies[currency]?.symbol}${fieldValue} will be transferred from your ${title} to your account within 24 hours.`}
            leading={<img width={20} height={20} src={SuccessIcon} />}
          />
          <ModalFooter>
            <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
              <Button isFullWidth onClick={handleDismiss} variant="primary">
                Okay, Got It!
              </Button>
            </Box>
          </ModalFooter>
        </Modal>
      )}
    </>
  );
}

const mapDispatchToProps = (dispatch: any) =>
  bindActionCreators({ fetchBankAccount: fetchBankAccountReducer }, dispatch);

const mapStateToProps = (state: any) => ({
  profile: state.profile,
});

export default compose(connect(mapStateToProps, mapDispatchToProps))(WithdrawCredits);
