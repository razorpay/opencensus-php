import React from 'react';
import { Text } from '@razorpay/blade/components';
import { getCurrencySymbol } from '@razorpay/i18nify-js/currency';

import { CoverageIcon } from './CoverageIcon';

export const TestingResult = ({ type, currency, amount, success, webhookFailure = false }) => {
  const PAYMENT_TEXT = `Payment of ${getCurrencySymbol(currency)}${amount} via UPI intent`;
  const REFUND_TEXT = `Refund of ${getCurrencySymbol(currency)}${amount} via UPI intent`;

  let text = '';
  let resultText = '';
  let suffixText = '';
  if (type === 'payment') {
    if (success) {
      text = `${PAYMENT_TEXT} was `;
      resultText = 'successful';
    } else if (webhookFailure) {
      text = 'Webhook issue detected';
    } else {
      text = `${PAYMENT_TEXT} has `;
      resultText = 'failed';
    }
  } else if (type === 'refund') {
    if (success) {
      text = `${REFUND_TEXT} was `;
      resultText = 'successfully ';
      suffixText = 'initiated';
    } else {
      text = `${REFUND_TEXT} `;
      resultText = 'failed';
    }
  }

  return (
    <Text as="p">
      <CoverageIcon status={success ? 'positive' : 'negative'} />
      <Text as="span" marginLeft="spacing.3" weight="semibold" size="large">
        {text}
      </Text>
      {resultText !== '' && (
        <Text
          as="span"
          weight="semibold"
          size="large"
          color={
            success
              ? 'feedback.background.positive.intense'
              : 'feedback.background.negative.intense'
          }
        >
          {resultText}
        </Text>
      )}
      {suffixText !== '' && (
        <Text as="span" weight="semibold" size="large">
          {suffixText}
        </Text>
      )}
    </Text>
  );
};
