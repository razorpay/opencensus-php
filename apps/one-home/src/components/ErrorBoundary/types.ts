import { ReactNode } from 'react';
import { BoxProps } from '@razorpay/blade/components';

export interface ErrorBoundaryProps {
  children: ReactNode;
  errorStateProps?: ErrorStateProps;
}

export interface ErrorBoundaryState {
  hasError: boolean;
}

export interface ErrorStateProps {
  title?: string;
  padding?: BoxProps['padding'];
  onRetry?: () => void;
  size?: 'medium' | 'large';
  borderRadius?: BoxProps['borderRadius'];
  withBorder?: boolean;
}
