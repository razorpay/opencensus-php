import React from 'react';
import {
  SETTLEMENT_TYPE_SMART,
  SETTLEMENT_TYPE_INSTANT,
  type SettlementTransactionType,
  MIN_SMART_SETTLEMENT_AMOUNT,
} from './helpers';
import {
  Badge,
  Box,
  CornerDownRightIcon,
  ListIcon,
  SettlementsIcon,
  Text,
  ZapIcon,
  Theme,
} from '@razorpay/blade/components';
import styled from 'styled-components';

const ClickableBox = styled.button(
  ({ theme }: { theme: Theme }) => `
    border:  ${theme.border.width.none};
    background: none;
    padding: 0px;
    outline: ${theme.border.width.none};
`,
);

const Icon = ({ type, isDisabled }: { type: SettlementTransactionType; isDisabled: boolean }) => {
  return type === SETTLEMENT_TYPE_SMART ? (
    <SettlementsIcon
      size="large"
      color={isDisabled ? 'surface.icon.gray.muted' : 'surface.icon.onSea.onSubtle'}
    />
  ) : (
    <ZapIcon
      size="large"
      color={isDisabled ? 'surface.icon.gray.muted' : 'surface.icon.onCloud.onSubtle'}
    />
  );
};

const Heading = ({
  type,
  isDisabled,
}: {
  type: SettlementTransactionType;
  isDisabled: boolean;
}) => {
  return (
    <Box display="flex" gap="spacing.2" alignItems="center">
      <Text
        color={
          isDisabled
            ? 'surface.text.gray.muted'
            : type === SETTLEMENT_TYPE_SMART
            ? 'surface.text.onSea.onSubtle'
            : 'surface.text.onCloud.onSubtle'
        }
        size="medium"
        weight="medium"
      >
        {type === SETTLEMENT_TYPE_SMART ? 'Smart Settlements' : 'Instant Settlement'}
      </Text>
      {isDisabled || type === SETTLEMENT_TYPE_INSTANT ? null : (
        <Badge color="positive" size="small" emphasis="subtle">
          Recommended
        </Badge>
      )}
    </Box>
  );
};

const Badges = ({
  type,
  isDisabled,
  amount,
}: {
  type: SettlementTransactionType;
  isDisabled: boolean;
  amount?: number;
}) => {
  return isDisabled ? (
    <Text color="feedback.text.information.intense" variant="caption" size="small" weight="regular">
      {type === SETTLEMENT_TYPE_SMART
        ? 'Currently unavailable as the bank window is closed.'
        : 'To choose this option, enter an amount less than 5Cr.'}
    </Text>
  ) : (
    <Box display="flex" gap="spacing.2">
      {type === SETTLEMENT_TYPE_SMART ? (
        <>
          <Badge color="neutral" size="small" emphasis="subtle">
            60 Mins{' '}
          </Badge>
          <Badge icon={CornerDownRightIcon} color="neutral" size="small" emphasis="subtle">
            Single Bank Statement Entry
          </Badge>{' '}
        </>
      ) : (
        <>
          <Badge color="neutral" size="small" emphasis="subtle">
            Instant
          </Badge>
          {amount && amount > MIN_SMART_SETTLEMENT_AMOUNT && (
            <Badge icon={ListIcon} color="neutral" size="small" emphasis="subtle">
              {' '}
              Multiple Bank Statement Entries{' '}
            </Badge>
          )}
        </>
      )}
    </Box>
  );
};

export default function SettlementTypeCard({
  isDisabled = false,
  onClick,
  selectedSettlementTransactionType,
  amount,
  type,
}: {
  isDisabled?: boolean;
  onClick: VoidFunction;
  selectedSettlementTransactionType: SettlementTransactionType;
  amount?: number;
  type: SettlementTransactionType;
}) {
  return (
    <ClickableBox onClick={onClick} disabled={isDisabled}>
      <Box
        display="flex"
        gap="spacing.2"
        padding="spacing.4"
        borderWidth="thinner"
        borderRadius="medium"
        borderColor={
          isDisabled
            ? 'surface.border.gray.muted'
            : selectedSettlementTransactionType === type
            ? 'surface.border.primary.normal'
            : 'surface.border.gray.subtle'
        }
        backgroundColor={
          isDisabled || selectedSettlementTransactionType === type
            ? 'surface.background.gray.moderate'
            : 'surface.background.gray.intense'
        }
      >
        <Icon type={type} isDisabled={isDisabled} />

        <Box display="flex" flexDirection="column" gap="spacing.3" alignItems="start">
          <Heading type={type} isDisabled={isDisabled} />
          <Badges type={type} isDisabled={isDisabled} amount={amount} />
        </Box>
      </Box>
    </ClickableBox>
  );
}
