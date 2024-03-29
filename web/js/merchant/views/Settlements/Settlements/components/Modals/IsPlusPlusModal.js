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
      <Text weight="semibold">What is not working for you?</Text>
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
  settlementAmount,
  advanceAmount,
  instantFee,
  tax,
  fromWhere,
}) {
  const [state, setState] = useState('breakup');

  console.log({ settlementAmount, advanceAmount });
  if (state === 'success') {
    return (
      <Box display="flex" flexDirection="column" marginY="spacing.4">
        <Text weight="semibold" marginY="spacing.2">
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
        <Amount
          value={settlementAmount + advanceAmount}
          type="heading"
          size="medium"
          weight="semibold"
        />
      </Box>
      <hr />
      <Box display="flex" flexDirection="row" flexWrap="wrap" marginY="spacing.5">
        <Box display="flex" justifyContent="space-between" width="100%">
          <Text size="small">Settlement amount</Text>
          <Amount value={settlementAmount} type="body" size="small" weight="semibold" />
        </Box>
        <Box display="flex" justifyContent="space-between" width="100%">
          <Text size="small">Advance amount</Text>
          <Amount value={advanceAmount} type="body" size="small" weight="semibold" />
        </Box>
        <Box display="flex" justifyContent="space-between" width="100%">
          <Text size="small">Instant fees</Text>
          <Amount value={instantFee * -1} type="body" size="small" weight="semibold" />
        </Box>
        <Box display="flex" justifyContent="space-between" width="100%">
          <Text size="small">Taxes</Text>
          <Amount value={tax * -1} type="body" size="small" weight="semibold" />
        </Box>
        <Box display="flex" justifyContent="space-between" width="100%" marginY="spacing.4">
          <Text size="small">Amount after deduction</Text>
          <Amount
            value={settlementAmount + advanceAmount - instantFee - tax}
            type="body"
            size="small"
            weight="semibold"
          />
        </Box>
        <Box display="flex" justifyContent="space-between" width="100%" marginY="spacing.2">
          <Text size="small">
            + Interest on Advance amount <br /> at 0.05% / day
          </Text>
          <Box display="flex" alignItems="center">
            <Amount value={0.0005 * advanceAmount} type="body" size="small" weight="semibold" />
            <Text size="small">/ day</Text>
          </Box>
        </Box>
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
