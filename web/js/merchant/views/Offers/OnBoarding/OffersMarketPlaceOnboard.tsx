import React, { useEffect, useState, useMemo, useCallback } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

import Slider from 'common/new-ui/Slider';
import {
  OnBoardingWrapper,
} from 'merchant/components/OnBoarding';
import Features from 'merchant/components/OnBoarding/Slides/Features';
import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import { RZPFeatures } from 'merchant/helpers/data';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

import { FEATURES_DATA_OFFERS_MARKETPLACE, FEATURES_LINKS } from './data';
import { Box, Text, Button, CheckIcon } from '@razorpay/blade/components';
import { analyticsTrack } from 'common/utils/analytics';
import { getItem, setItem } from 'common/utils/localStorage';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import 'merchant/views/Offers/OnBoarding/index.css';

const OffersMarketPlaceOnboarding = (props) => {
  const [hasJoinedWaitlist, setHasJoinedWaitlist] = useState(false);
  const [isPending, setIsPending] = useState(false);

  const getNextBtnProp = () => {
    return (
      <Button isLoading={isPending} onClick={handleJoinWaitList} iconPosition='right' icon={hasJoinedWaitlist ? CheckIcon : undefined} variant='primary' color={hasJoinedWaitlist ? 'positive' : 'primary'}>
        {hasJoinedWaitlist ? 'Joined Waitlist' : 'Get Early Access'}
      </Button>
    );
  };

  const { active, user } = props;

  useEffect(() => {
    // get from local storage
    const isWaitlistJoined = getItem(`offers-marketplace-onboarding-banner-${user.current}`);
    if (isWaitlistJoined) {
      setHasJoinedWaitlist(true);
    }
  }, []);


  const joinWaitList = () => {
    return new Promise((resolve) => {
      setTimeout(() => {
        setItem(`offers-marketplace-onboarding-banner-${user.current}`, '1');
        analyticsTrack({
          objectName: 'Offers Marketplace Onboarding Interest Banner',
          actionName: 'clicked',
          screen: 'Offers Marketplace',
          properties: getCommonAnalyticsProperties(window.rzp_user),
          toLumberjack: true,
        });
        resolve('Success');
      }, 1000);
    })
  }

  const handleJoinWaitList = async () => {
    if (hasJoinedWaitlist) {
      return;
    }

    setIsPending(true);

    const res = await joinWaitList();
    if (res === 'Success') {
      setHasJoinedWaitlist(true);
      setIsPending(false);
    }
  }

  const descContent = useCallback(() => {
    return (
      <Box>
        <Text marginTop={'spacing.6'} marginBottom={'spacing.2'} color={'surface.text.gray.muted'}>Discover <Text as="span" weight="semibold" color='interactive.text.positive.normal'>pre-approved</Text> offers from top banks & businesses, specially curated for your checkout. No setup needed. Enable and drive higher conversions and GMV. </Text>
        <Box borderRadius={'large'} padding={'spacing.4'} paddingBottom={'spacing.6'} marginTop={'spacing.7'} backgroundColor={'surface.background.gray.subtle'}>
          <Text color={'surface.text.gray.muted'}>
            We are working hard to source new offers for you soon. You can get <Text weight="semibold" as="span" color='interactive.text.positive.normal'>Early Access</Text> by joining the waitlist. Only a few early spots available.
          </Text>
          <Button isLoading={isPending} iconPosition='right' icon={hasJoinedWaitlist ? CheckIcon : undefined} marginTop={'spacing.7'} color={hasJoinedWaitlist ? 'positive' : 'primary'} onClick={handleJoinWaitList} variant="secondary">
              {hasJoinedWaitlist ? 'Joined Waitlist' : 'Get Early Access'}
          </Button>
        </Box>
      </Box>
    );
  }, [isPending, hasJoinedWaitlist, handleJoinWaitList]);

  return (
    <OnBoardingWrapper className="Offers Offers-Marketplace">
      <Slider
        active={active}
      >
        {(sliderProps) => (
          <Landing
            {...sliderProps}
            feature={RZPFeatures.OFFERS}
            title="Unlock Exciting Offers with No Effort"
            imageUrl="https://cdn.razorpay.com/static/assets/offers/offers_marketplace.gif"
            desc={descContent}
          />
        )}

        {(sliderProps) => (
          <Features
            {...sliderProps}
            title="What makes Offers Marketplace great?"
            feature={RZPFeatures.OFFERS}
            nextBtn={getNextBtnProp}
            featureLinks={FEATURES_LINKS}
            features={FEATURES_DATA_OFFERS_MARKETPLACE}
          />
        )}
      </Slider>
    </OnBoardingWrapper>
  );
}

export default compose(
  connect(
    (state) => {
      return {
        offers: state.offers,
        user: state.session.user,
        offersProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.OFFERS),
      };
    },
    {
      handleProductQuickGuide,
    },
  ),
)(OffersMarketPlaceOnboarding);