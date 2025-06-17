import React from 'react';
import { BoxProps } from '@razorpay/blade/components';
import { ErrorState } from './ErrorState';

export const ErrorBoundaryFallBackComponent = (props: BoxProps) => (
  <ErrorState
    borderWidth="thinner"
    borderColor="surface.border.gray.muted"
    backgroundColor="surface.background.gray.intense"
    borderRadius="large"
    marginX="spacing.0"
    {...props}
  />
);
