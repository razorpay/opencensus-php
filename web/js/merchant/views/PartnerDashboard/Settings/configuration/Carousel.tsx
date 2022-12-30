import React, { useState } from 'react';
import { classList } from 'common/utils/rzp-utils';

interface CarouselProps {
  carouselItems: JSX.Element[];
}

export const Carousel = ({ carouselItems }: CarouselProps): JSX.Element => {
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

  return (
    <>
      <div className="carousel-container">
        <button className="prev-btn" type="button" onClick={handlePrev}>
          <i className="i-solid i-chevron-left" />
        </button>
        {carouselLength > 0 &&
          carouselItems.map((item, index) => {
            return (
              <div
                className="carousel-item"
                key={index}
                style={{
                  transform: `translate(-${currentIndex * 100}%)`,
                }}
              >
                {item}
              </div>
            );
          })}
        <button className="next-btn" type="button" onClick={handleNext}>
          <i className="i-solid i-chevron-right" />
        </button>
      </div>
      <div className="dot-container">
        {carouselLength > 0 &&
          carouselItems.map((item, index) => {
            return (
              <span
                data-testid="dots"
                key={index}
                className={classList('rounded-dot', index === currentIndex ? 'active-dot' : '')}
              />
            );
          })}
      </div>
    </>
  );
};
