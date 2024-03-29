import React, { useState } from 'react';
import { classList } from 'common/utils/rzp-utils';
import { ChevronLeftIcon, ChevronRightIcon } from '@razorpay/blade/components';

interface CarouselProps {
  carouselItems: JSX.Element[];
  hideArrows?: boolean;
  alignDotsLeft?: boolean;
  yellowDots?: boolean;
}

export const Carousel = ({
  carouselItems,
  hideArrows = false,
  alignDotsLeft = false,
  yellowDots = false,
}: CarouselProps): JSX.Element => {
  const [currentIndex, setCurrentIndex] = useState<number>(0);
  const carouselLength = carouselItems.length;

  const handleNext = (): void => {
    if (currentIndex === carouselLength - 1) {
      setCurrentIndex(0);
    } else {
      setCurrentIndex((index) => index + 1);
    }
  };

  const handlePrev = (): void => {
    if (currentIndex === 0) {
      setCurrentIndex(carouselLength - 1);
    } else {
      setCurrentIndex((index) => index - 1);
    }
  };

  const handleDotClick = (index) => {
    setCurrentIndex(index);
  };

  return (
    <>
      <div className="carousel-container">
        {!hideArrows && (
          <button className="prev-btn" type="button" onClick={handlePrev}>
            <ChevronLeftIcon color="feedback.icon.neutral.intense" size="medium" />
          </button>
        )}
        {carouselLength > 0 &&
          carouselItems.map((item, index) => {
            return (
              <div
                className={`carousel-item ${alignDotsLeft ? 'carousel-item-left' : ''}`}
                key={index}
                style={{
                  transform: `translate(-${currentIndex * 100}%)`,
                }}
              >
                {item}
              </div>
            );
          })}
        {!hideArrows && (
          <button className="next-btn" type="button" onClick={handleNext}>
            <ChevronRightIcon color="feedback.icon.neutral.intense" size="medium" />
          </button>
        )}
      </div>
      <div className={`dot-container ${alignDotsLeft ? 'dot-container-left' : ''}`}>
        {carouselLength > 0 &&
          carouselItems.map((item, index) => {
            return (
              <span
                data-testid="dots"
                key={index}
                className={classList(
                  'rounded-dot',
                  index === currentIndex ? 'active-dot' : '',
                  `${yellowDots && 'rounded-dot-yellow'}`,
                  `${yellowDots && `${index === currentIndex ? 'active-dot-yellow' : ''}`}`,
                )}
                onClick={() => {
                  handleDotClick(index);
                }}
              />
            );
          })}
      </div>
    </>
  );
};
