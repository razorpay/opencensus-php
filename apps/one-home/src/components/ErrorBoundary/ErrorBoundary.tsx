import React, { Component } from 'react';
import { ErrorBoundaryProps, ErrorBoundaryState } from './types';
import ErrorState from './ErrorState';

// TODO(post initial release): check if we can leverage shared-ui ErrorBoundary
class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
  constructor(props: ErrorBoundaryProps) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError() {
    return { hasError: true };
  }

  componentDidCatch(error: Error) {
    // TODO: log error to Sentry, send error to analytics
    console.error('Error caught by ErrorBoundary:', error);
  }

  render(): React.ReactNode {
    const { children, errorStateProps } = this.props;
    const { hasError } = this.state;

    if (hasError) {
      return (
        <ErrorState {...errorStateProps} />
      );
    }
    return children;
  }

};

export default ErrorBoundary;