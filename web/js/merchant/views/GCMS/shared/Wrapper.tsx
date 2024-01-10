import React, { ReactNode } from 'react';
import { Navigate } from 'react-router-dom';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { useSplitzService } from 'common/splitz';

import { isGCMSExperimentEnabled } from './utils';

interface Props {
  children: ReactNode;
}
const GCMSWrapper: React.FC<Props> = ({ children }) => {
  const splitzService = useSplitzService();

  if (!isGCMSExperimentEnabled(splitzService)) {
    return <Navigate to="/dashboard" replace />;
  }

  return <ErrorBoundary>{children}</ErrorBoundary>;
};

export default GCMSWrapper;
