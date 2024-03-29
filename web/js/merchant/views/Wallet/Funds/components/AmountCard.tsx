import React from 'react';
import { Text } from '@razorpay/blade/components';

import { Amount, AmountShimmer, Card } from './styled';

interface AmountCardProps {
  amount?: number;
  currency?: string;
  isLoading: boolean;
  label: string | JSX.Element;
}

const DefaultProps: AmountCardProps = {
  isLoading: true,
  label: '',
};

export const AmountCard = ({
  amount,
  currency,
  isLoading,
  label,
}: AmountCardProps = DefaultProps): JSX.Element => {
  return (
    <Card>
      {typeof label === 'string' ? (
        <Text size="medium" weight="regular" variant="body" color="surface.text.gray.muted">
          {label}
        </Text>
      ) : (
        label
      )}
      {isLoading ? (
        <AmountShimmer height="30px" width="140px" />
      ) : (
        <Amount
          data-testid="amount-card"
          className="Wallet__AmountCard"
          currency={currency}
          value={amount}
        />
      )}
    </Card>
  );
};

export default AmountCard;
