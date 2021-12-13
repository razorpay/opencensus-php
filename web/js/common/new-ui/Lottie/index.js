import React, { useEffect } from 'react';
import Lottie from 'react-lottie';
import { NOOP } from 'merchant/views/Capital/Loans/constants';
import ErrorBoundary, { Ranks } from 'common/new-ui/ErrorBoundary';

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
  eventListeners = [],
}) => {
  useEffect(() => {
    trackInitialRenderImpression?.(merchantId, fromWhere);
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
    <ErrorBoundary resetOnProps rank={Ranks.P2}>
      <div
        onClick={!disabled ? onClick : NOOP}
        className={`lottie-wrapper ${disabled ? 'lottie-disabled' : ''}`}
      >
        <Lottie
          options={config}
          width={width ? width : '100%'}
          isStopped={isStopped ? isStopped : false}
          isClickToPauseDisabled={true}
          eventListeners={eventListeners}
        />
      </div>
    </ErrorBoundary>
  );
};

export default CustomLottie;
