import React from 'react';
import { SliderDots } from 'common/new-ui/Slider';
import { Box, Button } from '@razorpay/blade/components';

const SlideController = ({
  sliderProps,
  onNext,
  disNext = false,
  nextBtnLabel,
  nextBtnPendingLabel,
  isLoading = false,
}) => {
  const { next, prev, active } = sliderProps;
  const nextLabel = nextBtnLabel ? nextBtnLabel : 'Next';

  return (
    <Box padding="spacing.4">
      <Box
        display="flex"
        justifyContent="space-between"
        alignItems="center"
        gap="spacing.4"
        position="relative"
      >
        <Box>
          <SliderDots {...sliderProps} />
        </Box>
        <Box position="absolute" left="50%" transform="translateX(-50%)">
          <Button
            size="medium"
            isDisabled={disNext}
            onClick={() => {
              !disNext && next && next();
              return onNext && onNext();
            }}
            isLoading={isLoading}
          >
            {nextLabel}
          </Button>
        </Box>
      </Box>
    </Box>
  );
};

export default SlideController;
