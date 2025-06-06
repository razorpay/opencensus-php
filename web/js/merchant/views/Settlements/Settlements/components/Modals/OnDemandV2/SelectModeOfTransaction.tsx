import React, { useState } from 'react';
import { Amount, Box, EditComposeIcon, IconButton, Text, Button } from '@razorpay/blade/components';
import {
  convertToMajorUnit,
  MODAL_HEADER_BG,
  MODAL_PADDING,
  SETTLEMENT_TYPES,
  SCREENS,
  SETTLEMENT_TYPE_SMART,
  SETTLEMENT_TYPE_INSTANT,
  type SettlementTransactionType,
  MIN_SMART_SETTLEMENT_AMOUNT,
  MAX_IMPS_AMOUNT,
} from './helpers';
import { useOdsMutation } from 'merchant/views/Settlements/InstantSettlements/query-hooks/useOdsMutation';

import {
  trackRender,
  trackButton,
} from 'merchant/views/Settlements/InstantSettlements/utils/analytics';
import SettlementTypeCard from './SettlementTypeCard';

interface SelectModeOfTransactionProps {
  currency: 'INR';
  amount: number;
  onSuccess: VoidFunction;
  onBack: VoidFunction;
  isSmartSettlementAvailable?: boolean;
  isOdsExpEnabled: boolean;
}

const SelectModeOfTransaction = ({
  amount,
  onSuccess,
  onBack,
  currency,
  isSmartSettlementAvailable,
  isOdsExpEnabled,
}: SelectModeOfTransactionProps) => {
  const [selectedSettlementTransactionType, setSelectedSettlementTransactionType] =
    useState<SettlementTransactionType>('');
  const odsMutation = useOdsMutation(isOdsExpEnabled);
  const isLoading = odsMutation.isLoading || odsMutation.isPaused;
  const isIMPSDisabled = amount > MAX_IMPS_AMOUNT;
  const shouldSmartSettlementBeVisible = amount > MIN_SMART_SETTLEMENT_AMOUNT;
  const isSmartSettlementDisabled =
    !isSmartSettlementAvailable || amount < MIN_SMART_SETTLEMENT_AMOUNT;

  const isSettleNowCTADisabled =
    selectedSettlementTransactionType === '' ||
    isLoading ||
    (selectedSettlementTransactionType === 'settlement_payout_type_smart' &&
      !isSmartSettlementAvailable);

  const onSubmit = () => {
    const settlementPayload = {
      type: SETTLEMENT_TYPES.ODS,
      amount,
      currency,
      settlement_payout_type: selectedSettlementTransactionType,
    };
    odsMutation.mutate(settlementPayload, {
      onSuccess: () => {
        onSuccess();
      },
      onError: () => {
        trackRender({
          screen: SCREENS.WITHDRAW,
          context: 'confirm-error',
        });
      },
    });
    trackButton({
      screen: SCREENS.WITHDRAW,
      name: 'Yes Settle',
    });
  };

  return (
    <Box borderRadius="large" overflow="hidden">
      {/* Header */}
      <Box
        paddingX={MODAL_PADDING}
        paddingBottom="spacing.8"
        paddingTop="spacing.10"
        backgroundColor={MODAL_HEADER_BG}
      >
        <Text color="surface.text.gray.normal" weight="regular" size="medium">
          Settlement Amount
        </Text>
        <Box display="flex" gap="spacing.3" alignItems="center">
          <Amount
            value={convertToMajorUnit(amount, { currency })}
            currency={currency}
            weight="semibold"
            size="medium"
            type="heading"
          />
          <IconButton
            isDisabled={isLoading}
            icon={EditComposeIcon}
            onClick={onBack}
            size="medium"
            accessibilityLabel="Edit Amount"
          />
        </Box>
      </Box>

      {/* Body */}
      <Box display="flex" flexDirection="column" margin={MODAL_PADDING} gap="spacing.5">
        <Text weight="semibold" size="medium">
          Select settlement method
        </Text>
        <SettlementTypeCard
          selectedSettlementTransactionType={selectedSettlementTransactionType}
          isDisabled={isIMPSDisabled}
          onClick={() => setSelectedSettlementTransactionType(SETTLEMENT_TYPE_INSTANT)}
          amount={amount}
          type={SETTLEMENT_TYPE_INSTANT}
        />

        {shouldSmartSettlementBeVisible && (
          <SettlementTypeCard
            isDisabled={isSmartSettlementDisabled}
            onClick={() => setSelectedSettlementTransactionType(SETTLEMENT_TYPE_SMART)}
            selectedSettlementTransactionType={selectedSettlementTransactionType}
            type={SETTLEMENT_TYPE_SMART}
          />
        )}
      </Box>
      {/* Footer */}

      <Box
        padding="spacing.6"
        paddingTop="spacing.5"
        borderWidth="none"
        borderTopWidth="thin"
        borderColor="surface.border.gray.muted"
      >
        <Text size="medium" weight="regular" color="surface.text.gray.subtle">
          Settlement will be initiated after this step.
        </Text>
        <Button
          isFullWidth
          isDisabled={isSettleNowCTADisabled}
          isLoading={isLoading}
          onClick={onSubmit}
          marginTop="spacing.5"
        >
          Settle Now
        </Button>

        <Button
          isFullWidth
          onClick={onBack}
          isLoading={isLoading}
          variant="tertiary"
          marginTop="spacing.5"
        >
          Back
        </Button>
      </Box>
    </Box>
  );
};

export default SelectModeOfTransaction;
