import React from 'react';
import { Box, Alert, AlertProps } from '@razorpay/blade/components';

type ErrorPageProps = Pick<
  AlertProps,
  'color' | 'isFullWidth' | 'title' | 'description' | 'isDismissible'
>;

const ErrorPage = ({
  title = 'Something went wrong!',
  description = 'We are facing some issues. Please try again later.',
  color = 'negative',
  isFullWidth = false,
  isDismissible = false,
}: ErrorPageProps): JSX.Element => {
  return (
    <Box marginTop="spacing.8">
      <Alert
        title={title}
        description={description}
        color={color}
        margin="spacing.5"
        isDismissible={isDismissible}
        isFullWidth={isFullWidth}
      />
    </Box>
  );
};

export default ErrorPage;
