import React from 'react';
import { Spinner } from 'merchant_common/views/Reports/components';
import { SpinnerContainer } from './styled';
import { SuspenseType } from './types';

export const Suspense = ({ children, minWidth }: SuspenseType): JSX.Element => {
  return (
    <React.Suspense
      fallback={
        <SpinnerContainer minWidth={minWidth}>
          <Spinner
            size="large"
            contrast="low"
            accessibilityLabel="Loading, Please Wait."
            label="Loading Reports, Please wait..."
            labelPosition="bottom"
          />
        </SpinnerContainer>
      }
    >
      {children}
    </React.Suspense>
  );
};
