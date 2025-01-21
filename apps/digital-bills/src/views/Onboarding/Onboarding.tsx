import React, { useState } from 'react';

import Features from '@apps/digital-bills/src/views/Onboarding/components/Features';
import Landing from '@apps/digital-bills/src/views/Onboarding/components/Landing';

type OnBoardingProps = {
  isInWaitlist: boolean;
};

type ActiveScreenType = 'landing' | 'features';

const Onboarding = ({ isInWaitlist = false }: OnBoardingProps): React.ReactElement => {
  const [activeScreen, setActiveScreen] = useState<ActiveScreenType>('landing');

  if (isInWaitlist) return <Features isInWaitlist />;

  return (
    <>
      {activeScreen === 'landing' ? (
        <Landing updateActiveScreen={(): void => setActiveScreen('features')} />
      ) : (
        <Features
          isInWaitlist={false}
          updateActiveScreen={(): void => setActiveScreen('landing')}
        />
      )}
    </>
  );
};

export default Onboarding;
