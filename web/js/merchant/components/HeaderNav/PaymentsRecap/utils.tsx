import moment from 'moment';
import React, { useEffect } from 'react';
import { Box, Button, ShareIcon } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import { merchantFetch } from 'merchant/utils/ajax';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

import Frame from './Frame';
import { CarouselSlides } from './types';
import { StyledTextHeading, StyledTextSubHeading } from './styled';

export const postContent = 'What last year had in store! Check out my #RazorpayRewind';

export const DEFAULT_STORY_INTERVAL = 10000;

const KEY = 'rzp_rewind_cache';
export const getPaymentsRecapApiCache = ({ merchantId }) => {
  const paymentsRecapCache = localStorage.getItem(`${KEY}_${merchantId}`);
  if (!paymentsRecapCache) {
    return null;
  }
  try {
    const { data, expireAt } = JSON.parse(paymentsRecapCache);
    if (expireAt && moment().isAfter(expireAt)) {
      return null;
    }
    return data;
  } catch {
    // no action is required
  }
  return null;
};

export const setPaymentsRecapApiCache = ({ merchantId, paymentsRecapData }) => {
  localStorage.setItem(
    `${KEY}_${merchantId}`,
    JSON.stringify({
      data: paymentsRecapData,
      expireAt: moment().add(15, 'days').format(),
    }),
  );
};

export const trackPaymentsRecapEvent = ({
  objectName,
  actionName,
  properties = {},
}: {
  objectName: string;
  actionName: string;
  properties?: Record<string, any>;
}): void => {
  analyticsTrackWithUserInfo({
    objectName,
    actionName,
    screen: 'home page',
    properties,
    addUserProperties: true,
  });
};

export const usePaymentsRecap = (merchantId) => {
  return useQuery({
    queryKey: ['razorrewind-payments-recap'],
    queryFn: async () => {
      const paymentsRecapCache = getPaymentsRecapApiCache({ merchantId });
      if (paymentsRecapCache?.is_gvm || paymentsRecapCache?.payment_method_split_industry)
        return paymentsRecapCache;

      const { data } = await merchantFetch({
        url: 'care_service/merchant/twirp/rzp.care.dashboard.home.v1.HomeService/GetMerchantPaymentYearlyRecap',
        data: {},
        method: 'post',
      });

      // only cache for success response
      if (data?.is_gvm || data?.payment_method_split_industry) {
        setPaymentsRecapApiCache({ merchantId, paymentsRecapData: data });
      }
      return data;
    },
    refetchOnWindowFocus: false,
    retry: false,
  });
};

export const copyImgToClipboard = async (pngBlob, type): Promise<void> => {
  try {
    await navigator.clipboard.write([
      // eslint-disable-next-line no-undef
      new ClipboardItem({
        [type]: pngBlob,
      }),
    ]);
  } catch (error) {
    //
  }
};

const cdnUrl = '/dist/css/assets/razorpay-rewind';

const TimeImgMap = {
  '12PM to 5PM': '12PM-5PM',
  '5AM to 12PM': '5AM-12PM',
  '5PM to 9PM': '5PM-9PM',
  '9PM to 5AM': '9PM-5AM',
};

export const getSlides = ({ data, handleShare, isMobile = false }): Array<CarouselSlides> => {
  const {
    is_gvm,
    gvm_this_year,
    transaction_best_hour,
    growth_percentile,
    transaction_best_day,
    transaction_best_month,
    transaction_bucket,
    payment_method_split,
    payment_method_split_industry,
  } = data ?? {};

  if (!is_gvm && !payment_method_split_industry?.length) return [];

  const carouselSlides: CarouselSlides[] = [
    {
      imgSrc: `${cdnUrl}/intro.png`,
      imgOverlay: null,
      key: 'Intro',
    },
  ];

  if (is_gvm && gvm_this_year) {
    carouselSlides.push({
      imgSrc: `${cdnUrl}/total-gmv.png`,
      imgOverlay: (
        <Box
          position="absolute"
          bottom={isMobile ? '40px' : '55px'}
          left={isMobile ? '13px' : '20px'}
        >
          <StyledTextHeading color="#FFD336" fontSize={isMobile ? '35px' : '50px'}>
            {gvm_this_year}
          </StyledTextHeading>
        </Box>
      ),
      key: 'GMV',
    });
  }

  if (is_gvm && transaction_bucket && typeof transaction_bucket === 'string') {
    const transactionBucketTokens = transaction_bucket.split(' ');
    const txnBucketType = transactionBucketTokens[transactionBucketTokens.length - 1];
    const value = transactionBucketTokens[0];
    if (['Week', 'Day', 'Hour', 'annually'].includes(txnBucketType) && !isNaN(+value)) {
      carouselSlides.push({
        imgSrc: `${cdnUrl}/txn/${txnBucketType}.png`,
        imgOverlay: (
          <Box
            position="absolute"
            top={isMobile ? '48px' : '62px'}
            left={isMobile ? '25px' : '33px'}
          >
            <StyledTextHeading color="#FFD336" fontSize={isMobile ? '40px' : '60px'}>
              {value}
            </StyledTextHeading>
          </Box>
        ),
        key: 'Transaction bucket',
      });
    }
  }

  if (is_gvm && growth_percentile && [10, 20, 30, 40, 50].includes(growth_percentile)) {
    carouselSlides.push({
      imgSrc: `${cdnUrl}/gmv-per/top-${growth_percentile}.png`,
      imgOverlay: null,
      key: 'Growth Percentile',
    });
  }

  if (is_gvm && transaction_best_hour && Object.keys(TimeImgMap).includes(transaction_best_hour)) {
    carouselSlides.push({
      imgSrc: `${cdnUrl}/time/${TimeImgMap[transaction_best_hour]}.png`,
      imgOverlay: null,
      key: 'Time of day',
    });
  }

  if (
    is_gvm &&
    transaction_best_day &&
    ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'].includes(
      transaction_best_day,
    )
  ) {
    carouselSlides.push({
      imgSrc: `${cdnUrl}/week/${transaction_best_day}.png`,
      imgOverlay: null,
      key: 'Day of week',
    });
  }

  if (
    is_gvm &&
    transaction_best_month &&
    [
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December',
    ].includes(transaction_best_month)
  ) {
    carouselSlides.push({
      imgSrc: `${cdnUrl}/month/${transaction_best_month}.png`,
      imgOverlay: null,
      key: 'Month',
    });
  }

  if (is_gvm && payment_method_split && payment_method_split.length) {
    carouselSlides.push({
      imgSrc: `${cdnUrl}/payment-method-split.png`,
      imgOverlay: (
        <Box
          position="absolute"
          top={isMobile ? '85px' : '100px'}
          left={isMobile ? '24px' : '30px'}
          display="flex"
          flexDirection="row"
          flexWrap="wrap"
          gap="spacing.7"
        >
          {payment_method_split.map(({ method, value }, index) => (
            <Box
              display="flex"
              flexDirection="column"
              justifyContent="space-between"
              alignItems="center"
              key={index}
            >
              <StyledTextHeading fontSize={isMobile ? '24px' : '35px'}>{value}%</StyledTextHeading>
              <StyledTextSubHeading fontSize={isMobile ? '12px' : '20px'}>
                {method}
              </StyledTextSubHeading>
            </Box>
          ))}
        </Box>
      ),
      key: 'Payment Method',
    });
  }

  if (payment_method_split_industry && payment_method_split_industry.length) {
    carouselSlides.push({
      imgSrc: `${cdnUrl}/payment-method-split-industry.png`,
      imgOverlay: (
        <Box
          position="absolute"
          top={isMobile ? '85px' : '100px'}
          left={isMobile ? '24px' : '30px'}
          display="flex"
          flexDirection="row"
          flexWrap="wrap"
          gap="spacing.7"
        >
          {payment_method_split_industry.map(({ method, value }, index) => (
            <Box
              display="flex"
              flexDirection="column"
              justifyContent="space-between"
              alignItems="center"
              key={index}
            >
              <StyledTextHeading fontSize={isMobile ? '24px' : '35px'}>{value}%</StyledTextHeading>
              <StyledTextSubHeading fontSize={isMobile ? '12px' : '20px'}>
                {method}
              </StyledTextSubHeading>
            </Box>
          ))}
        </Box>
      ),
      key: 'Payment Method Industry',
    });
  }

  carouselSlides.push({
    imgSrc: `${cdnUrl}/last.png`,
    imgOverlay: (
      <Box position="absolute" top={isMobile ? '110px' : '155px'} left={isMobile ? '30px' : '40px'}>
        <Button
          onClick={handleShare}
          icon={ShareIcon}
          iconPosition="left"
          zIndex={99999}
          variant="primary"
          color="white"
        >
          Share
        </Button>
      </Box>
    ),
    key: 'End Share',
    duration: 2 * DEFAULT_STORY_INTERVAL,
  });

  return carouselSlides;
};

const fbBase = 'https://www.facebook.com/sharer/sharer.php';
const twitterBase = 'https://twitter.com/share';
const linkedinBase = 'https://www.linkedin.com/shareArticle';

export const socialShare = (type): boolean => {
  let mediaUrl;

  const mediaMsg = window.encodeURIComponent(`"${postContent}"`);

  switch (type) {
    case 'facebook':
      mediaUrl = `${fbBase}?quote=${mediaMsg}`;

      window.open(mediaUrl, 'facebook-share');
      break;

    case 'twitter':
      mediaUrl = `${twitterBase}?text=${mediaMsg}`;

      window.open(mediaUrl, 'twitter-share');
      break;

    case 'linkedin':
      mediaUrl = `${linkedinBase}?mini=true&title=${mediaMsg}`;

      window.open(mediaUrl, 'twitter-share');
      break;

    default:
      return false;
  }

  return false;
};

const Story = ({ slide, onStoryStart, imgDimension }) => {
  const { imgOverlay, imgSrc, key } = slide;
  useEffect(() => {
    onStoryStart(key);
  }, []);
  return (
    <Frame backgroundImageSrc={imgSrc} children={imgOverlay} imgDimension={`${imgDimension}px`} />
  );
};

export const getStories = ({ slides, imgDimension, onStoryStart }) => {
  return slides.map((slide) => {
    const { key, duration } = slide;
    return {
      content: () => (
        <Story imgDimension={imgDimension} onStoryStart={onStoryStart} slide={slide} />
      ),
      key,
      duration: duration ?? DEFAULT_STORY_INTERVAL,
    };
  });
};
