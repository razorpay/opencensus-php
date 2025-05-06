import React from 'react';
import ErrorBoundary from './ErrorBoundary';
import { ErrorStateProps } from './types';

const withErrorBoundary = (Component: React.ComponentType, errorStateProps: ErrorStateProps) => (props: object) => (
	<ErrorBoundary errorStateProps={errorStateProps}>
		<Component {...props} />
	</ErrorBoundary>
);
export default withErrorBoundary;