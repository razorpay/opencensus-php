import React from 'react';
import PropTypes from 'prop-types';
import Carousel from './Carousel';
import CalendarIcon from './CalendarIcon';

const CashAdvanceCarouselSlide = React.memo(
  ({ slide, navigation, nextSlideID, prevSlideID, handleAction, isCompactMode, slides }) => {
    return (
      <div className="slide" style={{ background: slide.color }}>
        <img
          className="background-pattern-image"
          height="120%"
          width="120%"
          src={require("assets/capital/carousel-bg-pattern.svg")}
        />
        <div className="header">
          <div className="left">
            <p className="title">{slide.title}</p>
            <p className="subtitle">{slide.subTitle}</p>
          </div>
          <div className="right">
            <Carousel.DotNavigation allowUserInteraction />
          </div>
        </div>
        <p className="body">{slide.body}</p>
        <div className="footer">
          <div className="left">
            <button style={{ color: slide.color }} onClick={() => handleAction(slide.cta.actionId)}>
              {slide.cta.text}
            </button>
            {slides.length > 1 ? (
              <React.Fragment>
                <button
                  className={prevSlideID === null ? 'nav inactive' : 'nav active'}
                  onClick={() => navigation.back()}
                >
                  <i className="i i-chevron-left" aria-hidden="true" />
                </button>
                <button
                  className={nextSlideID === null ? 'nav inactive' : 'nav active'}
                  onClick={() => navigation.next()}
                >
                  <i className="i i-chevron-right" aria-hidden="true" />
                </button>
              </React.Fragment>
            ) : null}
          </div>
          <div className="right">
            {slide.rightDataPair.length > 0 && !isCompactMode ? (
              <div className="right-section-wrapper">
                <div className="data-pair-container">
                  {slide.rightDataPair.map((dataPair, index) => (
                    <div className="data-pair" key={index}>
                      <div className="text label">{dataPair.label}</div>
                      <div className="text value">{dataPair.value}</div>
                    </div>
                  ))}
                </div>
                <div className="icon-set">
                  <div className="layer1">
                    <CalendarIcon variant={slide.calendarColorVariant} />
                  </div>
                  <div className="layer2">{slide.secondaryIcon}</div>
                </div>
              </div>
            ) : (
              <div className="icon-set">
                <div className="layer1">
                  <CalendarIcon variant={slide.calendarColorVariant} />
                </div>
                <div className="layer2">{slide.secondaryIcon}</div>
              </div>
            )}
          </div>
        </div>
      </div>
    );
  },
);

CashAdvanceCarouselSlide.propTypes = {
  slide: PropTypes.shape({
    backgroundPattern: PropTypes.bool.isRequired,
    body: PropTypes.oneOfType([PropTypes.node, PropTypes.string]).isRequired,
    calendarColorVariant: PropTypes.string.isRequired,
    color: PropTypes.string.isRequired,
    cta: PropTypes.shape({
      actionId: PropTypes.string.isRequired,
      text: PropTypes.string.isRequired,
    }).isRequired,
    id: PropTypes.number.isRequired,
    rightDataPair: PropTypes.arrayOf(
      PropTypes.shape({
        label: PropTypes.string.isRequired,
        value: PropTypes.string.isRequired,
      }).isRequired,
    ).isRequired,
    secondaryIcon: PropTypes.node.isRequired,
    subTitle: PropTypes.oneOfType([PropTypes.node, PropTypes.string]).isRequired,
    title: PropTypes.oneOfType([PropTypes.node, PropTypes.string]).isRequired,
  }).isRequired,
  navigation: PropTypes.shape({
    next: PropTypes.func.isRequired,
    back: PropTypes.func.isRequired,
    setActiveSlide: PropTypes.func.isRequired,
  }).isRequired,
  nextSlideID: PropTypes.oneOfType([PropTypes.string]),
  prevSlideID: PropTypes.oneOfType([PropTypes.string]),
  handleAction: PropTypes.func,
  isCompactMode: PropTypes.bool.isRequired,
};
CashAdvanceCarouselSlide.displayName = 'CashAdvanceCarouselSlide';

export default CashAdvanceCarouselSlide;
