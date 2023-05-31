import { Box, Text, Amount, Button, RadioGroup, Radio } from '@razorpay/blade/components';
import React, { useState } from 'react';
import { onDemandModalTrackEvents } from 'merchant/views/Settlements/trackEvents';

const REASONS = {
  'low-amount': 'Amount is too low',
  'high-interest-rate': 'Interest rate is high',
  'no-requirement': "I don't require credit",
  'not-listed': 'My reason is not listed',
};

export const ISPlusPlusReasons = ({ fromWhere, onFinish }) => {
  const [reason, setReason] = useState('');
  return (
    <Box margin="spacing.6">
      <Text weight="bold">What is not working for you?</Text>
      <RadioGroup
        necessityIndicator="none"
        onChange={({ value }) => setReason(value)}
        value={reason}
        size="medium"
        marginY="spacing.4"
      >
        <Radio value="low-amount">Amount is too low</Radio>
        <Radio value="high-interest-rate">Interest rate is high</Radio>
        <Radio value="no-requirement">I don't require advance</Radio>
        <Radio value="not-listed">My reason is not listed</Radio>
      </RadioGroup>
      <Button
        isFullWidth
        onClick={() => {
          onFinish();
          onDemandModalTrackEvents.trackISFailure(fromWhere, REASONS[reason]);
        }}
        isDisabled={!reason}
      >
        Go to Instant Settlement
      </Button>
    </Box>
  );
};

export default function IsPlusPlusModal({
  onFinish,
  onGoBack,
  amount,
  instantFee,
  tax,
  fromWhere,
}) {
  const [state, setState] = useState('breakup');

  if (state === 'success') {
    return (
      <Box display="flex" flexDirection="column" marginY="spacing.4">
        <Text weight="bold" marginY="spacing.2">
          Thank you for your interest!
        </Text>
        <Text size="small" marginY="spacing.2">
          We’ve got your request and someone from our team will get in touch to enable this for you.
        </Text>
        <Text size="small">In the meanwhile, get your settlement balance now.</Text>
        <Button
          marginTop="spacing.4"
          onClick={() => {
            onDemandModalTrackEvents.trackISSuccess(fromWhere);
            onFinish();
          }}
        >
          Get Settlement Balance
        </Button>
      </Box>
    );
  }

  return (
    <Box marginY="spacing.8">
      <Box>
        <Text size="small">Amount to be provided</Text>
        <Amount size="heading-large-bold" value={amount} />
      </Box>
      <hr />
      <Box display="flex" flexDirection="row" flexWrap="wrap" marginY="spacing.5">
        <Box display="flex" justifyContent="space-between" width="100%">
          <Text size="small">Total amount</Text>
          <Amount size="body-small-bold" value={amount} />
        </Box>
        <Box display="flex" justifyContent="space-between" width="100%">
          <Text size="small">Instant fees</Text>
          <Amount size="body-small-bold" value={instantFee * -1} />
        </Box>
        <Box display="flex" justifyContent="space-between" width="100%">
          <Text size="small">Taxes</Text>
          <Amount size="body-small-bold" value={tax * -1} />
        </Box>
        <Box display="flex" justifyContent="space-between" width="100%" marginY="spacing.4">
          <Text size="small">Amount after deduction</Text>
          <Amount size="body-small-bold" value={amount - instantFee - tax} />
        </Box>
        <Text size="small">
          + Interest on advance amount <Text weight="bold">@0.05% /day</Text>
        </Text>
      </Box>
      <Box display="flex" justifyContent="space-between" width="100%">
        <Button variant="tertiary" onClick={onGoBack}>
          Go back
        </Button>
        <Button
          onClick={() => {
            onDemandModalTrackEvents.trackISSettleNowSecondConfirm(fromWhere);
            setState('success');
          }}
        >
          Yes, Get the amount
        </Button>
      </Box>
    </Box>
  );
}
