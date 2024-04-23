import React, { useState, useEffect } from 'react';
import {
  Box,
  Heading,
  Text,
  SelectInput,
  TextInput,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  Spinner,
} from '@razorpay/blade/components';
import { getCurrencySymbol } from '@razorpay/i18nify-js/currency';

import { merchantFetch } from 'merchant/utils/ajax';
import { loadCheckoutScript } from 'merchant/views/Capital/utils';

import { PaymentFailureAlert, PaymentWebhookFailureAlert } from './FailureAlerts';
import { TestingResult } from './TestingResult';

export const PaymentTesting = ({
  currency,
  amount,
  setAmount,
  setMerchantKey,
  isPaymentDone,
  paymentId,
  isPaymentSuccessfull,
  setIsPaymentSuccessfull,
  isWebhookFailure,
  setIsWebhookFailure,
  paymentError,
  setPaymentError,
  gateway,
  businessName,
}) => {
  const [isPaymentDetailsFetched, setIsPaymentDetailsFetched] = useState(false);
  useEffect(() => {
    loadCheckoutScript();
    merchantFetch({
      url: 'keys',
    }).then((res) => {
      if (res?.success) {
        const key = res.data?.items?.[0]?.id;
        setMerchantKey(key);
      }
    });
  }, []);

  useEffect(() => {
    if (isPaymentDone && paymentId) {
      merchantFetch({
        url: `payments/${paymentId}`,
        method: 'GET',
      }).then((res) => {
        if (res?.success) {
          setIsPaymentSuccessfull(res.data?.status === 'captured');
          setIsWebhookFailure(res.data?.late_authorized);
          setPaymentError(res.data?.error_description);
        } else {
          setIsPaymentSuccessfull(false);
          setPaymentError(res?.errors?.[0]);
        }
        setIsPaymentDetailsFetched(true);
      });
    }
  }, [isPaymentDone, paymentId]);

  return (
    <Box display="flex" flexDirection="column" padding="spacing.8">
      <Heading size="medium">Payment testing</Heading>
      <Text as="p" marginTop="spacing.5" size="small" color="surface.text.gray.normal">
        You would now be prompted to make a test transaction on the {businessName} Checkout, which
        would then be refunded to your account in the next step
      </Text>
      {isWebhookFailure ? (
        <>
          <Box display="flex" flexDirection="column" marginTop="spacing.7">
            <TestingResult
              type="payment"
              currency={currency}
              amount={amount}
              success={false}
              webhookFailure={true}
            />
          </Box>
          <PaymentWebhookFailureAlert gateway={gateway} />
        </>
      ) : isPaymentSuccessfull ? (
        <Box display="flex" flexDirection="column" marginTop="spacing.7">
          <TestingResult type="payment" currency={currency} amount={amount} success={true} />
        </Box>
      ) : isPaymentDone && isPaymentDetailsFetched && !isPaymentSuccessfull ? (
        <>
          <Box display="flex" flexDirection="column" marginTop="spacing.7">
            <TestingResult type="payment" currency={currency} amount={amount} success={false} />
          </Box>
          <PaymentFailureAlert paymentError={paymentError} />
        </>
      ) : isPaymentDone && !isPaymentDetailsFetched ? (
        <Box display="flex" alignItems="center" marginTop="spacing.10">
          <Spinner />
        </Box>
      ) : (
        <Box width="68%">
          <Dropdown selectionType="single" marginTop="spacing.7">
            <SelectInput
              labelPosition="left"
              label="Payment type"
              name="payment_type"
              isDisabled={true}
              defaultValue="test"
            />
            <DropdownOverlay>
              <ActionList>
                <ActionListItem title="Test payment" value="test" />
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
          <Dropdown selectionType="single" marginTop="spacing.7">
            <SelectInput
              labelPosition="left"
              label="Payment method"
              name="payment_method"
              isDisabled={true}
              defaultValue="upi_intent"
            />
            <DropdownOverlay>
              <ActionList>
                <ActionListItem title="UPI (Intent)" value="upi_intent" />
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
          <TextInput
            labelPosition="left"
            label="Amount"
            helpText="This amount would be refunded to your account in the next step"
            prefix={getCurrencySymbol(currency)}
            value={amount}
            onChange={({ value }) => setAmount(String(value))}
            marginTop="spacing.7"
          />
        </Box>
      )}
    </Box>
  );
};
