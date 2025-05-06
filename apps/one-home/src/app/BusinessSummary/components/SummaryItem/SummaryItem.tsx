import React from 'react';
import {
  Box,
  Amount,
  Link,
  ChevronRightIcon,
  Text,
  IconComponent,
} from '@razorpay/blade/components';
import { CurrencyCodeType } from '@razorpay/i18nify-js';

interface SummaryItemProps {
  title: string;
  amount: number;
  currency: CurrencyCodeType;
  icon: IconComponent;
  isEnabled: boolean;
  onClick: () => void;
}

interface SummaryBreakupItemProps {
  title: string;
  amount: number;
  currency: CurrencyCodeType;
}

const SummaryBreakupItem: React.FC<SummaryBreakupItemProps> = ({ title, amount, currency }) => {
  return (
    <Box
      display="flex"
      flex={1}
      justifyContent="space-between"
      alignItems="center"
      marginLeft="spacing.5"
      columnGap="spacing.5"
    >
      <Text color="surface.text.gray.muted" size="small" weight="regular">
        {title}
      </Text>

      <Amount
        size="small"
        type="body"
        color="surface.text.gray.muted"
        weight="semibold"
        isAffixSubtle
        suffix="humanize"
        currencyIndicator="currency-symbol"
        value={amount}
        currency={currency}
      />
    </Box>
  );
};

const SummaryItem: React.FC<SummaryItemProps> = ({
  title,
  amount,
  currency,
  icon: IconComponent,
  isEnabled,
  onClick,
}) => {
  return (
    <Box display="flex" alignItems="center" gap="4px">
      <IconComponent
        size="small"
        color={isEnabled ? 'surface.icon.gray.normal' : 'interactive.icon.primary.normal'}
      />
      {isEnabled ? (
        <Box
          display="flex"
          flex={1}
          justifyContent="space-between"
          alignItems="center"
          columnGap="spacing.5"
        >
          <Text variant="body" size="medium" weight="medium" color="surface.text.gray.normal">
            {title}
          </Text>

          <Amount
            type="body"
            size="medium"
            weight="semibold"
            isAffixSubtle
            suffix="humanize"
            currencyIndicator="currency-symbol"
            value={amount}
            currency={currency}
          />
        </Box>
      ) : (
        <Link
          icon={ChevronRightIcon}
          variant="anchor"
          color="primary"
          size="medium"
          iconPosition="right"
          onClick={onClick}
        >
          {title}
        </Link>
      )}
    </Box>
  );
};

export { SummaryBreakupItem };
export default SummaryItem;
