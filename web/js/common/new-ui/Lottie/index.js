import React, { useEffect } from 'react';
import Lottie from 'react-lottie';

const CustomLottie = ({
  animationData,
  loop,
  autoplay,
  width,
  isStopped,
  onClick,
  disabled,
  trackInitialRenderImpression,
  fromWhere,
  merchantId,
}) => {
  useEffect(() => {
    trackInitialRenderImpression && trackInitialRenderImpression(merchantId, fromWhere);
  }, []);

  const config = {
    animationData,
    loop: loop ? loop : false,
    autoplay: autoplay ? autoplay : false,
    rendererSettings: {
      preserveAspectRatio: 'xMidYMid slice',
    },
  };

  return (
    <div onClick={onClick} className={`lottie-wrapper ${disabled ? 'lottie-disabled' : ''}`}>
      <Lottie
        options={config}
        width={width ? width : '100%'}
        isStopped={isStopped ? isStopped : false}
        isClickToPauseDisabled={true}
      />
    </div>
  );
};

export default CustomLottie;
