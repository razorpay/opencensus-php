import * as React from 'react';
import { ChevronLeftIcon, ChevronRightIcon, Box } from '@razorpay/blade/components';
import rTracking from 'react-tracking';
import { compose } from 'redux';

import { TOUCH_SPEED } from 'common/ui/PricingSubscription/constants';
import { RTrackingT } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';

import {
  StyleRightSlide,
  StyleLeftSlide,
  StyledCarouselDotWrapper,
  StyledCarouselDot,
  StyledCarouselSlide,
  StyledCarouselSlides,
  StyleSlideContainer,
} from './PricingMwebStyle';

import type { TrackingObjectType } from 'common/ui/PricingSubscription/PricingSubscriptionProps.type';
interface CarouselProps {
  children: JSX.Element[];
  pricingPlans: Array<Record<string, string>> | [];
  trackingData: TrackingObjectType;
  tracking: RTrackingT;
  togglePlan: string;
}
interface CarouselEventType {
  icon_type: string;
  plan_viewed: string;
  plan_id: string;
  last_plan_viewed: string;
  last_plan_id: string;
  default_plan: string;
  toggle_switch: string;
}

const Carousel = ({
  children,
  trackingData,
  pricingPlans,
  tracking,
  togglePlan,
}: CarouselProps): JSX.Element => {
  const [currentSlide, setCurrentSlide] = React.useState(0);
  const [touchPosition, setTouchPosition] = React.useState<number | null>(null);

  const activeSlide = children?.length
    ? children.map((slide, index) => (
        <StyledCarouselSlide active={currentSlide === index} key={index} data-testid="childSlides">
          {slide}
        </StyledCarouselSlide>
      ))
    : [];
  const lastPlanViewed = pricingPlans[currentSlide]?.title;
  const lastPlanId = pricingPlans[currentSlide]?.id;
  const sendEventForPlanView = (carouselEvent: CarouselEventType): void => {
    tracking.trackEvent(
      window.rzpQ &&
        window.rzpQ?.merchantActions().initiated('merchant_dashboard.click_icon', {
          ...trackingData,
          ...carouselEvent,
        }),
    );
  };
  const handleLeftClick = (): void => {
    const numberOfPlans = children.length;
    const previousPlan = (numberOfPlans + currentSlide - 1) % numberOfPlans;
    sendEventForPlanView({
      icon_type: 'Previous',
      plan_viewed: pricingPlans[previousPlan]?.title,
      plan_id: pricingPlans[previousPlan]?.id,
      last_plan_viewed: lastPlanViewed,
      last_plan_id: lastPlanId,
      default_plan: pricingPlans[0]?.title,
      toggle_switch: togglePlan,
    });
    setCurrentSlide((currentSlide - 1 + activeSlide.length) % activeSlide.length);
  };
  const handleRightClick = (): void => {
    const nextPlan = (currentSlide + 1) % children.length;
    sendEventForPlanView({
      icon_type: 'Next',
      plan_viewed: pricingPlans[nextPlan]?.title,
      plan_id: pricingPlans[nextPlan]?.id,
      last_plan_viewed: lastPlanViewed,
      last_plan_id: lastPlanId,
      default_plan: pricingPlans[0]?.title,
      toggle_switch: togglePlan,
    });
    setCurrentSlide((currentSlide + 1) % activeSlide.length);
  };
  const handleTouchStart = (e: React.TouchEvent<HTMLDivElement>): void => {
    const touchDown = e.touches[0]?.clientX;
    setTouchPosition(touchDown);
  };
  const handleTouchMove = (e: React.TouchEvent<HTMLDivElement>): void => {
    if (touchPosition === null) return;
    const currentTouch = e.touches[0]?.clientX;
    const diff = touchPosition - currentTouch;
    if (diff > TOUCH_SPEED) handleRightClick();
    if (diff < -TOUCH_SPEED) handleLeftClick();
    setTouchPosition(null);
  };
  return (
    <Box position="relative" testID="pricingMwebCarousel">
      <StyleSlideContainer
        data-testid="touchSliderContainer"
        onTouchStart={handleTouchStart}
        onTouchMove={handleTouchMove}
      >
        <StyledCarouselSlides data-testid="activeSlidesContainer" currentSlide={currentSlide}>
          {activeSlide}
        </StyledCarouselSlides>
      </StyleSlideContainer>

      <StyledCarouselDotWrapper>
        <StyleLeftSlide onClick={handleLeftClick} data-testid="carouselLeftPane">
          <ChevronLeftIcon
            data-testid="ChevronLeftIcon"
            color="feedback.icon.neutral.intense"
            size="xlarge"
          />
        </StyleLeftSlide>
        {children?.length &&
          children.map((_, index) => (
            <StyledCarouselDot
              data-testid="carouselDot"
              isActive={currentSlide === index}
              key={index}
            />
          ))}
        <StyleRightSlide onClick={handleRightClick} data-testid="carouselRightPane">
          <ChevronRightIcon
            data-testid="ChevronRightIcon"
            color="feedback.icon.neutral.intense"
            size="xlarge"
          />
        </StyleRightSlide>
      </StyledCarouselDotWrapper>
    </Box>
  );
};

export default compose(rTracking({ page: 'PricingBundleCarouselMwebHome' }))(Carousel);
