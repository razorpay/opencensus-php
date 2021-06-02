import React, { useState } from 'react';
import OffersForYouIconJson from 'merchant/helpers/lottieConfigs/OffersForYouIconJson.json';
import CustomLottie from 'common/new-ui/Lottie';

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
    }
  ];

  return (
    <CustomLottie
      animationData={OffersForYouIconJson}
      autoplay={animationStart}
      loop={2}
      eventListeners={eventListeners}
      isStopped={isStopped}
    />
  );
};

export default OffersForYouIcon;
