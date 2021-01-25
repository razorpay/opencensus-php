import React, { useState, memo, useCallback, useEffect } from 'react';
import PropTypes from 'prop-types';

const CarouselContext = React.createContext(null);

// 0 is also a valid slideID. So, cannot just do !!slideID
const isValidSlideID = (slideID) =>
  (typeof slideID === 'number' && !isNaN(slideID)) ||
  (typeof slideID === 'string' && slideID.length !== 0);

const assertDuplicateIDs = (slides) => {
  const uniqueIds = new Set([]);
  slides.forEach((slide) => {
    uniqueIds.add(slide.id);
  });
  if (uniqueIds.size !== slides.length) {
    console.warn(
      'List of slides provided to carousel component contains one or' +
        ' more duplicate IDs, Please ensure all IDs are unique.',
    );
  }
};

const Carousel = memo(({ slides, children, interval, autoRotate, activeSlide, onChange }) => {
  assertDuplicateIDs(slides);
  const [activeSlideID, setActiveSlideID] = useState(slides[0].id);
  const currentSlideIndex = slides.findIndex((slide) => slide.id === activeSlideID);
  const nextSlideID =
    currentSlideIndex === slides.length - 1 ? null : slides[currentSlideIndex + 1].id;
  const prevSlideID = currentSlideIndex === 0 ? null : slides[currentSlideIndex - 1].id;

  const slide = useCallback((slideID) => {
    if (isValidSlideID(slideID)) {
      setActiveSlideID(slideID);
    }
  }, []);

  useEffect(() => {
    if (isValidSlideID(activeSlide)) {
      setActiveSlideID(activeSlide);
    }
  }, [activeSlide]);

  useEffect(() => {
    setActiveSlideID(slides[0].id);
  }, [slides]);

  useEffect(() => {
    if (autoRotate && !activeSlide) {
      const next = (currentSlideIndex + 1) % slides.length;
      const id = setTimeout(() => setActiveSlideID(slides[next].id), interval);
      return () => clearTimeout(id);
    }
  }, [currentSlideIndex, slides.length, interval, autoRotate, activeSlide]);

  useEffect(() => {
    if (typeof onChange === 'function') {
      onChange(activeSlideID);
    }
  }, [activeSlideID]);

  const nextHandler = () => slide(nextSlideID);
  const backHandler = () => slide(prevSlideID);
  const customNavHandler = (targetSlide) => {
    if (isValidSlideID(targetSlide)) {
      setActiveSlideID(targetSlide);
    }
  };

  return (
    <CarouselContext.Provider
      value={{
        activeSlideID,
        navigation: {
          next: nextHandler,
          back: backHandler,
          setActiveSlide: customNavHandler,
        },
        slides,
        nextSlideID,
        prevSlideID,
        currentSlideIndex,
      }}
    >
      <div className="slides-container">{children}</div>
    </CarouselContext.Provider>
  );
});

Carousel.propTypes = {
  autoRotate: PropTypes.bool,
  interval: PropTypes.number,
  children: PropTypes.arrayOf(PropTypes.node).isRequired,
  slides: PropTypes.arrayOf(PropTypes.any).isRequired,
  activeSlide: PropTypes.oneOfType([PropTypes.number, PropTypes.string]),
  onChange: PropTypes.func,
};

Carousel.defaultProps = {
  autoRotate: true,
  interval: 10000,
  onChange: () => {},
};

Carousel.Slide = (props) => {
  return (
    <CarouselContext.Consumer>
      {(data) => (
        <div
          className={`slide-container ${
            props.slideID === data.activeSlideID ? 'active' : 'inactive'
          }`}
        >
          {props.children({ ...data, ...props })}
        </div>
      )}
    </CarouselContext.Consumer>
  );
};

Carousel.Slide.propTypes = {
  children: PropTypes.arrayOf(PropTypes.node).isRequired,
  slideID: PropTypes.oneOfType([PropTypes.number, PropTypes.string]),
};

Carousel.Slide.displayName = 'CarouselSlide';

Carousel.DotNavigation = ({ allowUserInteraction }) => {
  return (
    <CarouselContext.Consumer>
      {({ slides, activeSlideID, navigation }) => (
        <React.Fragment>
          {slides.length > 1 && (
            <div className="dot-navigation-container">
              {slides.map((slide) => (
                <button
                  key={slide.id}
                  disabled={!allowUserInteraction}
                  onClick={() => navigation.setActiveSlide(slide.id)}
                  className={`${allowUserInteraction ? '' : 'disabled'} nav-dot ${
                    slide.id === activeSlideID ? 'active' : 'inactive'
                  }`}
                />
              ))}
            </div>
          )}
        </React.Fragment>
      )}
    </CarouselContext.Consumer>
  );
};

Carousel.DotNavigation.propTypes = {
  allowUserInteraction: PropTypes.bool,
};
Carousel.DotNavigation.defaultProps = {
  allowUserInteraction: true,
};
Carousel.DotNavigation.displayName = 'CarouselDotNavigation';

Carousel.displayName = 'Carousel';
export default Carousel;
