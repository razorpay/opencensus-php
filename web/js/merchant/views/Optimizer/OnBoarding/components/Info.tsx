import React, { useEffect } from 'react';
import { Box, Carousel, CarouselItem } from '@razorpay/blade/components';
import {
  getOnBoardingDataFromLocalState,
  setOnBoardingDataInLocalState,
} from 'merchant/components/OnBoarding';
import { RZPFeatures } from 'merchant/helpers/data';
import {
  CAROUSEL_IMG_1,
  CAROUSEL_IMG_2,
  CAROUSEL_IMG_3,
} from 'merchant/views/Optimizer/OnBoarding/constants';
import { Pricing } from './Pricing';
import { SubmitSuccess } from './SubmitSuccess';

export const Info = ({
  mode,
  nextStep,
  successfullySubmitted,
}: {
  mode: string;
  nextStep: () => void;
  successfullySubmitted: boolean;
}): JSX.Element => {
  const onboardingData = getOnBoardingDataFromLocalState(RZPFeatures.OPTIMIZER);

  useEffect(() => {
    if (successfullySubmitted) {
      setOnBoardingDataInLocalState({
        feature: RZPFeatures.OPTIMIZER,
        data: { hasSubmittedSuccessfully: successfullySubmitted },
      });
    }
  }, [successfullySubmitted]);

  return (
    <Box
      display="flex"
      gap="spacing.7"
      padding="spacing.8"
      backgroundColor="surface.background.sea.intense"
      marginX="spacing.3"
      marginY="spacing.5"
      borderRadius="large"
    >
      <Box width="66%">
        <Carousel
          indicatorVariant="gray"
          navigationButtonPosition="bottom"
          navigationButtonVariant="filled"
          showIndicators={true}
          visibleItems={1}
        >
          <CarouselItem>
            <img src={CAROUSEL_IMG_1 as unknown as string} width="100%" />
          </CarouselItem>
          <CarouselItem>
            <img src={CAROUSEL_IMG_2 as unknown as string} width="100%" />
          </CarouselItem>
          <CarouselItem>
            <img src={CAROUSEL_IMG_3 as unknown as string} width="100%" />
          </CarouselItem>
        </Carousel>
      </Box>
      {!successfullySubmitted && !onboardingData?.hasSubmittedSuccessfully ? (
        <Pricing mode={mode} nextStep={nextStep} />
      ) : (
        <SubmitSuccess />
      )}
    </Box>
  );
};
