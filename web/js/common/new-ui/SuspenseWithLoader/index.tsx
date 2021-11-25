import React, { Suspense } from 'react';
import Loader from 'common/components/Loader';

const SuspenseWithLoader = ({ children }): JSX.Element => {
  return <Suspense fallback={<Loader />}>{children}</Suspense>;
};

export default SuspenseWithLoader;
