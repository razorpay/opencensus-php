import React from 'react';
import { Box, Link, Text, RefreshIcon } from '@razorpay/blade/components';

type RetryOnErrorProps = {
  errorText?: string;
  retryFn?: () => void;
  justifyContent?:
    | 'center'
    | 'flex-start'
    | 'flex-end'
    | 'space-between'
    | 'space-around'
    | 'space-evenly';
};

const RetryOnError = ({ errorText, retryFn, justifyContent = 'center' }: RetryOnErrorProps) => {
  const getErrorText = () => {
    if (retryFn) {
      return errorText || 'Error';
    }
    return 'Access Denied';
  };

  return (
    <Box display="flex" justifyContent={justifyContent} gap="spacing.3" marginY="spacing.8">
      <Text size="medium" color="interactive.text.negative.normal">
        {getErrorText()}
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
