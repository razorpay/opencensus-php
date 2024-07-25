import React from 'react';
import { Alert, AlertProps } from '@razorpay/blade/components';

const PageError = ({
  title = 'Something went wrong!',
  description,
  color = 'negative',
  isFullWidth = false,
  isDismissible = false,
}: AlertProps): JSX.Element => {
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

export default PageError;
