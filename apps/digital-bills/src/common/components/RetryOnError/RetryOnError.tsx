import React from 'react';
import { Box, Link, Text, RefreshIcon } from '@razorpay/blade/components';

type RetryOnErrorProps = {
  errorText?: string;
  retryFn?: () => void;
};

const RetryOnError = ({ errorText, retryFn }: RetryOnErrorProps) => {
  return (
    <Box display="flex" justifyContent="center" gap="spacing.3">
      <Text size="medium" color="interactive.text.negative.normal">
        {errorText || 'Error'}
      </Text>
      {retryFn ? (
        <Link
          size="medium"
          variant="button"
          icon={RefreshIcon}
          iconPosition="right"
          onClick={retryFn}
        >
          Retry
        </Link>
      ) : null}
    </Box>
  );
};

export default RetryOnError;
