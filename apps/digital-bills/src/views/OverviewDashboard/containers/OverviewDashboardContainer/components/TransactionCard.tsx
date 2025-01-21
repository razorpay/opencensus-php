import React from 'react';
import { Amount, Box, Heading, Spinner } from '@razorpay/blade/components';

import RetryOnError from '@apps/digital-bills/src/common/components/RetryOnError';

type TransactionCardProps = {
  title: string;
  amount: number;
  isLoading?: boolean;
  hasError?: boolean;
  retryFn?: () => void;
};

const TransactionCard = ({
  title,
  amount,
  isLoading = false,
  hasError = false,
  retryFn,
}: TransactionCardProps): React.ReactElement => {
  const renderContent = () => {
    if (hasError) return <RetryOnError retryFn={retryFn} />;
    return isLoading ? (
      <Spinner
        alignSelf="flex-start"
        color="primary"
        label=""
        accessibilityLabel={`${title} spinner`}
      />
    ) : (
      <Amount type="heading" size="large" weight="semibold" suffix="humanize" value={amount} />
    );
  };

  return (
    <Box
      backgroundColor="surface.background.gray.intense"
      borderRadius="small"
      padding="spacing.4"
      paddingX="spacing.6"
      display="flex"
      flexDirection={{ base: 'row', m: 'column' }}
      justifyContent="space-between"
      width={{ base: '100%', m: 'auto' }}
    >
      <Heading weight="regular" size="small">
        {title}
      </Heading>
      {renderContent()}
    </Box>
  );
};

export default TransactionCard;
