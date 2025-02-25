import React from 'react';
import PropTypes from 'prop-types';
import captureException, { sentryFlows } from '../captureException';
import ErrorScreen from '../ErrorScreen';

class ErrorBoundary extends React.Component {
  state = {
    hasError: false,
  };

  static getDerivedStateFromError() {
    return { hasError: true };
  }

  componentDidCatch(error) {
    captureException(error.message, {
      flow: sentryFlows.ERROR_BOUNDARY,
    });
  }

  render() {
    const { hasError } = this.state;
    const { fallback, children } = this.props;
    return hasError ? fallback : children;
  }
}

ErrorBoundary.propTypes = {
  fallback: PropTypes.node,
  children: PropTypes.object.isRequired,
};

ErrorBoundary.defaultProps = {
  fallback: <ErrorScreen />,
};

export default ErrorBoundary;
