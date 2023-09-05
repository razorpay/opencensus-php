import React, { Suspense, useEffect, useRef } from 'react';
import styled from 'styled-components';

import CustomLottie from 'common/new-ui/Lottie';

import desktop from './LottieConfig/desktop.json';
import mobile from './LottieConfig/mobile.json';

const FestiveAnimationContainer = styled.div`
  position: fixed;
  top: 0;
  left: 0;
  height: 100%;
  width: 100%;
  pointer-events: none;
  z-index: 1112;
`;

const FestiveAnimation = ({ isMobile, isShow, handleClose, updateConfig }) => {
  const timeRef = useRef(null);

  useEffect(() => {
    updateConfig();
    timeRef.current = setTimeout(() => {
      handleClose();
    }, 5000);
    return () => {
      clearTimeout(timeRef.current);
    };
  }, []);

  const animationData = isMobile ? mobile : desktop;

  return (
    <FestiveAnimationContainer>
      <Suspense>
        <CustomLottie
          autoplay={isShow}
          animationData={animationData}
          loop={true}
          isStopped={!isShow}
          customProps={{
            height: '100vh',
          }}
        />
      </Suspense>
    </FestiveAnimationContainer>
  );
};

export default FestiveAnimation;
