import * as React from 'react';
import { ChevronLeftIcon, ChevronRightIcon, Box } from '@razorpay/blade/components';
import {
  StyleRightSlide,
  StyleLeftSlide,
  StyledCarouselDotWrapper,
  StyledCarouselDot,
  StyledCarouselSlide,
  StyledCarouselSlides,
} from './PricingMwebStyle';
interface CarouselProps {
  children: JSX.Element[];
}

const Carousel = ({ children }: CarouselProps): JSX.Element => {
  const [currentSlide, setCurrentSlide] = React.useState(0);

  const activeSlide = children?.length
    ? children.map((slide, index) => (
        <StyledCarouselSlide active={currentSlide === index} key={index}>
          {slide}
        </StyledCarouselSlide>
      ))
    : [];

  return (
    <Box position="relative">
      <Box display="flex">
        <StyledCarouselSlides currentSlide={currentSlide}>{activeSlide}</StyledCarouselSlides>
      </Box>
      <StyledCarouselDotWrapper>
        <StyleLeftSlide
          onClick={() => {
            setCurrentSlide((currentSlide - 1 + activeSlide.length) % activeSlide.length);
          }}
        >
          <ChevronLeftIcon color="feedback.icon.neutral.lowContrast" size="xlarge" />
        </StyleLeftSlide>
        {children?.length &&
          children.map((_, index) => (
            <StyledCarouselDot isActive={currentSlide === index} key={index} />
          ))}
        <StyleRightSlide
          onClick={() => {
            setCurrentSlide((currentSlide + 1) % activeSlide.length);
          }}
        >
          <ChevronRightIcon color="feedback.icon.neutral.lowContrast" size="xlarge" />
        </StyleRightSlide>
      </StyledCarouselDotWrapper>
    </Box>
  );
};

export default Carousel;
