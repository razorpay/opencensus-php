import React from 'react';
import { TrackingProvider } from 'merchant/views/onboarding/mobile/context/TrackingContext';

const Wrapper: React.FC = () => {
  return (
    <TrackingProvider>
      <div>Sample Layout</div>
    </TrackingProvider>
  );
};

export default Wrapper;
