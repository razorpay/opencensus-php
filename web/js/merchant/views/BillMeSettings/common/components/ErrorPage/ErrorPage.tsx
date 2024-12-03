import React from 'react';
import { Alert } from '@razorpay/blade/components';

type ErrorPageProps = {
  title?: string;
  description?: string;
  color?: 'information' | 'negative' | 'neutral' | 'notice' | 'positive';
  isFullWidth?: boolean;
  isDismissible?: boolean;
};

const ErrorPage = ({
  title = 'Something went wrong!',
  description = 'We are facing some issues. Please try again later.',
  color = 'negative',
  isFullWidth = false,
  isDismissible = false,
}: ErrorPageProps): JSX.Element => {
  return (
    <Alert
      title={title}
      description={description}
      color={color}
      margin="spacing.5"
      isDismissible={isDismissible}
      isFullWidth={isFullWidth}
    />
  );
};

export default ErrorPage;
