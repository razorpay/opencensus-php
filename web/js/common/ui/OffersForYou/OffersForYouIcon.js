import React, { useState } from 'react';
import OffersForYouIconJson from 'merchant/helpers/lottieConfigs/NewOfferForYouIconJson.json';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

const CustomLottie = lazy(() =>
  import(/* webpackChunkName: 'CustomLottie' */ 'common/new-ui/Lottie'),
);

const OffersForYouIcon = ({ isStopped, setIsStopped }) => {
  const [animationStart, setAnimationStart] = useState(false);

  const eventListeners = [
    {
      eventName: 'complete',
      callback: () => {
        setIsStopped(true);
      },
    },
    {
      eventName: 'DOMLoaded',
      callback: () => {
        setTimeout(() => {
          setAnimationStart(!isStopped);
        }, 1000);
      },
    },
  ];

  return (
    <SuspenseWithLoader>
      <CustomLottie
        animationData={OffersForYouIconJson}
        autoplay={animationStart}
        loop={2}
        eventListeners={eventListeners}
        isStopped={isStopped}
      />
    </SuspenseWithLoader>
  );
};

export default OffersForYouIcon;
