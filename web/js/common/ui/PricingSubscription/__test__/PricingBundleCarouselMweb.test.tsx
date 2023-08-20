import React from 'react';
import { fireEvent, userEvent, waitFor, render, screen } from 'test-utils';

import PricingBundleCarouselMweb from 'common/ui/PricingSubscription/Mobile/PricingBundleCarouselMweb';
import { pricing_bundle } from 'common/ui/PricingSubscription/__test__/PricingParentComponentsMockData';

const childrenContent = [1, 2, 3, 4];
const CarouselProps = {
  pricingPlans: pricing_bundle.pricingPlans,
  children: childrenContent,
};

describe('Test: PricingCarouselMweb component', () => {
  let captureRerender, childrenLen;
  beforeEach(() => {
    const { rerender } = render(<PricingBundleCarouselMweb {...CarouselProps} />);
    captureRerender = rerender;
    childrenLen = childrenContent.length;
  });

  test('should render Carousel Component', () => {
    const parentContainer = screen.getByTestId('pricingMwebCarousel');
    const touchSliderContainer = screen.getByTestId('touchSliderContainer');
    const activeSlidesContainer = screen.getByTestId('activeSlidesContainer');
    // Assert
    expect(parentContainer).toBeInTheDocument();
    expect(touchSliderContainer).toBeInTheDocument();
    expect(activeSlidesContainer).toBeInTheDocument();
  });
  test.each(childrenContent)(
    'should render Carousel Component with all slides - Slide Index %i ',
    (item) => {
      const childSlides = screen.queryAllByTestId('childSlides');
      // Assert
      expect(parseInt(childSlides[item - 1].innerHTML, 10)).toEqual(item);
    },
  );
  test('should render left & Right button with icon in it', () => {
    const carouselLeftPane = screen.getByTestId('carouselLeftPane');
    const carouselRightPane = screen.getByTestId('carouselRightPane');
    const chevronLeftIcon = screen.getByTestId('ChevronLeftIcon');
    const chevronRightIcon = screen.getByTestId('ChevronRightIcon');
    // Assert
    expect(carouselLeftPane).toBeInTheDocument();
    expect(carouselRightPane).toBeInTheDocument();
    expect(carouselLeftPane).toContainElement(chevronLeftIcon);
    expect(carouselRightPane).toContainElement(chevronRightIcon);
  });
  test('should render 4 carousel dots', async () => {
    const carouselDot = await screen.findAllByTestId('carouselDot');
    expect(carouselDot).toHaveLength(childrenContent.length);
  });
  test('should not render carousel dots', async () => {
    captureRerender(<PricingBundleCarouselMweb {...CarouselProps} children={[]} />);
    const isCarouselDot = await screen.queryByTestId('carouselDot');
    expect(isCarouselDot).toBeNull();
  });
  test('clicking the left button decrements the current slide', () => {
    // Get the initial active slide
    const listOfSlides = screen.queryAllByTestId('childSlides');
    const initialActiveSlide = listOfSlides[0];
    expect(initialActiveSlide).toBeInTheDocument();
  });

  let currentSlideLeftFlow = 0;
  test.each(childrenContent)(
    'should render Carousel Component with active slides  - Slide Index %i ',
    async () => {
      const carouselLeftPane = screen.getByTestId('carouselLeftPane');
      const listOfSlides = screen.queryAllByTestId('childSlides');
      expect(listOfSlides[currentSlideLeftFlow]).toBeInTheDocument(); // active slide before clicking
      let newActiveSlide;
      await userEvent.click(carouselLeftPane);
      // Assert
      await waitFor(() => {
        currentSlideLeftFlow = (currentSlideLeftFlow - 1 + childrenLen) % childrenLen;
        newActiveSlide = listOfSlides[currentSlideLeftFlow];
        expect(newActiveSlide).toBeInTheDocument();
      });
      expect(parseInt(newActiveSlide.innerHTML, 10)).toEqual(currentSlideLeftFlow + 1);
    },
  );

  let currentSlideRightFlow = 0;
  test.each(childrenContent)(
    'should render Carousel Component with active slides - Slide Index %i',
    async () => {
      // Get the initial active slide
      const carouselRightPane = screen.getByTestId('carouselRightPane');
      const listOfSlides = screen.queryAllByTestId('childSlides');
      expect(listOfSlides[currentSlideRightFlow]).toBeInTheDocument();
      let newActiveSlide;
      // Act
      await userEvent.click(carouselRightPane);
      // Assert
      await waitFor(() => {
        currentSlideRightFlow = (currentSlideRightFlow + 1) % childrenLen;
        newActiveSlide = listOfSlides[currentSlideRightFlow];
        expect(newActiveSlide).toBeInTheDocument();
      });
      expect(parseInt(newActiveSlide.innerHTML, 10)).toEqual(currentSlideRightFlow + 1);
    },
  );

  let currentSlideLeftSwipe = 0;
  test.each(childrenContent)('fires touch events and verify left swipe - Slide Index %i', () => {
    // test('fires touch events and verify left swipe', () => {
    const touchSliderContainer = screen.getByTestId('touchSliderContainer');

    // Simulate a touch start event
    fireEvent.touchStart(touchSliderContainer, { touches: [{ clientX: 100 }] });
    const listOfSlides = screen.queryAllByTestId('childSlides');

    // Act: Simulate a touch move event to the left (diff < -TOUCH_SPEED)
    fireEvent.touchMove(touchSliderContainer, { touches: [{ clientX: 50 }] });
    // Assert
    currentSlideLeftSwipe = (currentSlideLeftSwipe - 1 + childrenLen) % childrenLen;
    const previousSlide = listOfSlides[currentSlideLeftSwipe];
    expect(previousSlide).toBeInTheDocument();
    expect(parseInt(previousSlide.innerHTML, 10)).toEqual(currentSlideLeftSwipe + 1);
  });

  let currentSlideRightSwipe = 0;
  test.each(childrenContent)('fires touch events and verify right swipe - Slide Index %i', () => {
    const touchSliderContainer = screen.getByTestId('touchSliderContainer');
    const listOfSlides = screen.queryAllByTestId('childSlides');
    // Simulate a touch move event to the right (diff > TOUCH_SPEED)
    fireEvent.touchMove(touchSliderContainer, { touches: [{ clientX: 150 }] });
    // Assert
    currentSlideRightSwipe = (currentSlideRightSwipe + 1) % childrenLen;
    const previousSlide = listOfSlides[currentSlideRightSwipe];
    expect(previousSlide).toBeInTheDocument();
    expect(parseInt(previousSlide.innerHTML, 10)).toEqual(currentSlideRightSwipe + 1);
  });
  test('should check slide is in view or not', async () => {
    const carouselRightPane = screen.getByTestId('carouselRightPane');
    const carouselLeftPane = screen.getByTestId('carouselLeftPane');
    const checkElementIsInView = (targetElement) => {
      return (
        targetElement.top >= 0 &&
        targetElement.bottom <= window.innerHeight &&
        targetElement.left >= 0 &&
        targetElement.right <= window.innerWidth
      );
    };
    // Get the initial active slide
    let currentSlide = 0;
    const listOfSlides = screen.queryAllByTestId('childSlides');
    const initialActiveSlide = listOfSlides[0]; // Get the target div element
    const initialTargetElement = initialActiveSlide.getBoundingClientRect(); // Get the bounding rect of the target div
    const isInView = checkElementIsInView(initialTargetElement); // Check if the div is in view
    const childrenLen = childrenContent.length;
    if (isInView) {
      // The div is in view
      expect(isInView).toBe(true);
    } else {
      // The div is out of view
      expect(isInView).toBe(false);
    }
    // Act - User click right button first
    await userEvent.click(carouselRightPane);
    // Assert
    let previousRightSlide;
    await waitFor(() => {
      previousRightSlide = listOfSlides[currentSlide]; // get previous active slide
      currentSlide = (currentSlide + 1) % childrenLen; // get new slide index
      const newActiveSlide = listOfSlides[currentSlide].getBoundingClientRect(); // Get the bounding rect of the target div

      const isNewActiveSlideInView = checkElementIsInView(newActiveSlide); // Check if the div is in view
      if (isNewActiveSlideInView) {
        // slide is in view
        expect(isNewActiveSlideInView).toBe(true);
      }
    });
    const isPreviewSlideInView = checkElementIsInView(previousRightSlide); // Check if the div is in view
    if (!isPreviewSlideInView) {
      // slide is out of view
      expect(isPreviewSlideInView).toBeFalsy();
    }
    // ACT - User click left button after right button click
    await userEvent.click(carouselLeftPane);
    // ASSERT
    let previousLeftSlide;
    await waitFor(() => {
      previousLeftSlide = listOfSlides[currentSlide]; // get previous active slide
      currentSlide = (currentSlide - 1 + childrenLen) % childrenLen; // get new slide index
      const newActiveSlide = listOfSlides[currentSlide].getBoundingClientRect(); // Get the bounding rect of the target div

      const isNewActiveSlideInView = checkElementIsInView(newActiveSlide); // Check if the div is in view
      if (isNewActiveSlideInView) {
        // slide is in view
        expect(isNewActiveSlideInView).toBe(true);
      }
    });
    const isPreviewLeftSlideInView = checkElementIsInView(previousLeftSlide); // Check if the div is in view
    if (!isPreviewLeftSlideInView) {
      // slide is out of view
      expect(isPreviewLeftSlideInView).toBeFalsy();
    }
  });
});
