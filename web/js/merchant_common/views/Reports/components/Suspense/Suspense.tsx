import React from 'react';
import { Spinner } from 'merchant_common/views/Reports/components';
import { SpinnerContainer } from './styled';

export const Suspense = ({ children }): JSX.Element => {
  return (
    <React.Suspense
      fallback={
        <SpinnerContainer>
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
