import * as React from 'react';
import { ChevronLeftIcon, ChevronRightIcon, Box } from '@razorpay/blade/components';
import {
  StyleRightSlide,
  StyleLeftSlide,
  StyledCarouselDotWrapper,
  StyledCarouselDot,
  StyledCarouselSlide,
  StyledCarouselSlides,
  StyleSlideContainer,
} from './PricingMwebStyle';
import { TOUCH_SPEED } from 'common/ui/PricingSubscription/constants';
interface CarouselProps {
  children: JSX.Element[];
}

const Carousel = ({ children }: CarouselProps): JSX.Element => {
  const [currentSlide, setCurrentSlide] = React.useState(0);
  const [touchPosition, setTouchPosition] = React.useState<number | null>(null);

  const activeSlide = children?.length
    ? children.map((slide, index) => (
        <StyledCarouselSlide active={currentSlide === index} key={index}>
          {slide}
        </StyledCarouselSlide>
      ))
    : [];
  const handleLeftClick = (): void => {
    setCurrentSlide((currentSlide - 1 + activeSlide.length) % activeSlide.length);
  };
  const handleRightClick = (): void => {
    setCurrentSlide((currentSlide + 1) % activeSlide.length);
  };
  const handleTouchStart = (e: React.TouchEvent<HTMLDivElement>): void => {
    const touchDown = e.touches[0].clientX;
    setTouchPosition(touchDown);
  };
  const handleTouchMove = (e: React.TouchEvent<HTMLDivElement>): void => {
    if (touchPosition === null) return;
    const currentTouch = e.touches[0].clientX;
    const diff = touchPosition - currentTouch;
    if (diff > TOUCH_SPEED) handleRightClick();
    if (diff < -TOUCH_SPEED) handleLeftClick();
    setTouchPosition(null);
  };
  return (
    <Box position="relative">
      <StyleSlideContainer onTouchStart={handleTouchStart} onTouchMove={handleTouchMove}>
        <StyledCarouselSlides currentSlide={currentSlide}>{activeSlide}</StyledCarouselSlides>
      </StyleSlideContainer>

      <StyledCarouselDotWrapper>
        <StyleLeftSlide onClick={handleLeftClick}>
          <ChevronLeftIcon color="feedback.icon.neutral.lowContrast" size="xlarge" />
        </StyleLeftSlide>
        {children?.length &&
          children.map((_, index) => (
            <StyledCarouselDot isActive={currentSlide === index} key={index} />
          ))}
        <StyleRightSlide onClick={handleRightClick}>
          <ChevronRightIcon color="feedback.icon.neutral.lowContrast" size="xlarge" />
        </StyleRightSlide>
      </StyledCarouselDotWrapper>
    </Box>
  );
};

export default Carousel;
