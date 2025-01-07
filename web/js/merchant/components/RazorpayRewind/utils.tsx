import React from 'react';
import { Box } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { merchantFetch } from 'merchant/utils/ajax';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { CarouselSlides, SizeOptions } from './types';
import { BestTimeContainer, BestTimePercentage, TextWrapper } from './styled';
import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';
import MobileLandingImage from 'assets/razorpay_rewind/mobile-entry.png';
import DesktopLandingImage from 'assets/razorpay_rewind/desktop-entry.png';
import TotalPaymentsMobile from 'assets/razorpay_rewind/total-payments-mobile.png';
import TotalPaymentsDesktop from 'assets/razorpay_rewind/total-payments-desktop.png';
import RevenueAchievedMobile from 'assets/razorpay_rewind/revenue-achieved-mobile.png';
import RevenueAchievedDesktop from 'assets/razorpay_rewind/revenue-achieved-desktop.png';
import IndustryGrowthMobile from 'assets/razorpay_rewind/industry-growth-mobile.png';
import IndustryGrowthDesktop from 'assets/razorpay_rewind/industry-growth-desktop.png';
import PersonalGrowthMobile from 'assets/razorpay_rewind/personal-growth-mobile.png';
import PersonalGrowthDesktop from 'assets/razorpay_rewind/personal-growth-desktop.png';
import BestDayMobile from 'assets/razorpay_rewind/best-day-mobile.png';
import BestDayDesktop from 'assets/razorpay_rewind/best-day-desktop.png';
import { captureImage } from './imageUtils';

export const postContent = 'What last quarter had in store! Check out my #RazorpayRewind';

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

export const isEligibleForRazorpayRewind = (splitz, user: User) => {
  return (
    isExperimentEnabled(splitz?.abExperiments?.enable_razorpay_rewind) &&
    user.isCountryIndia &&
    user.isOrgRZP &&
    user.isActivated
  );
};

export const usePaymentsRecap = () => {
  return useQuery({
    queryKey: ['razorrewind-payments-recap'],
    queryFn: async () => {
      const { data } = await merchantFetch({
        url: 'care_service/merchant/twirp/rzp.care.dashboard.home.v1.HomeService/GetMerchantPaymentYearlyRecap',
        data: {},
        method: 'post',
      });

      return data;
    },
    refetchOnWindowFocus: false,
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

const TimeImgMap = {
  '12PM to 5PM': '12PM - 5PM',
  '5AM to 12PM': '5AM - 12PM',
  '5PM to 9PM': '5PM - 9PM',
  '9PM to 5AM': '9PM - 5AM',
};

const formatDate = (dateString: string) => {
  const monthsMap = {
    January: 'Jan',
    February: 'Feb',
    March: 'Mar',
    April: 'Apr',
    May: 'May',
    June: 'Jun',
    July: 'Jul',
    August: 'Aug',
    September: 'Sep',
    October: 'Oct',
    November: 'Nov',
    December: 'Dec',
  };

  const [day, month] = dateString.split(' ');
  return `${day} ${monthsMap[month] || month}`;
};

export const getSlides = ({ data, isMobile = false, isTablet = false, isSmallMobile = false }) => {
  const {
    is_gmv,
    quarter_gmv,
    time_of_day,
    transaction_bucket,
    morning_engagement_percentage,
    afternoon_engagement_percentage,
    evening_engagement_percentage,
    night_engagement_percentage,
    revenue_achieved_percentage,
    industry_growth_percentage,
    industry_grew_avg,
    gmv_date,
    overall_gmv_percentage,
  } = data ?? {};

  if (!is_gmv) return [];

  function getSize({
    mobileValue,
    tabletValue,
    defaultValue,
    smallMobileValue,
  }: SizeOptions): string {
    if (isSmallMobile) return smallMobileValue || mobileValue;
    if (isMobile) return mobileValue;
    if (isTablet) return tabletValue;
    return defaultValue;
  }

  const TimeIntervalArray = [
    {
      label: '5AM - 12PM',
      value: morning_engagement_percentage,
    },
    {
      label: '12PM - 5PM',
      value: afternoon_engagement_percentage,
    },
    {
      label: '5PM - 9PM',
      value: evening_engagement_percentage,
    },
    {
      label: '9PM - 5AM',
      value: night_engagement_percentage,
    },
  ];

  const carouselSlides: Array<CarouselSlides> = [
    {
      imgSrc: isMobile ? MobileLandingImage : DesktopLandingImage,
      imgOverlay: null,
      key: 'Intro',
    },
  ];

  if (is_gmv) {
    carouselSlides.push({
      imgSrc: isMobile ? TotalPaymentsMobile : TotalPaymentsDesktop,
      imgOverlay: (
        <Box position={'relative'}>
          <TextWrapper
            top={getSize({
              mobileValue: '53vw',
              tabletValue: '16vw',
              defaultValue: '100px',
              smallMobileValue: '52vw',
            })}
            left={getSize({ mobileValue: '13vw', tabletValue: '8vw', defaultValue: '50px' })}
            fontSize={getSize({ mobileValue: '8vw', tabletValue: '8vw', defaultValue: '80px' })}
          >
            {quarter_gmv}
          </TextWrapper>
        </Box>
      ),
      key: 'GMV',
    });
  }

  if (is_gmv && transaction_bucket && typeof transaction_bucket === 'string') {
    const transactionBucketTokens = transaction_bucket.split(' ');
    const txnBucketType = transactionBucketTokens[transactionBucketTokens.length - 1];
    const value = transactionBucketTokens[0];
    if (['Hour', 'Day', 'Quarter'].includes(txnBucketType) && !isNaN(+value)) {
      carouselSlides.push({
        imgSrc: isMobile
          ? require(`assets/razorpay_rewind/transactions/${txnBucketType}-mobile.png`)
          : require(`assets/razorpay_rewind/transactions/${txnBucketType}-desktop.png`),
        imgOverlay: (
          <Box position={'relative'}>
            <TextWrapper
              top={getSize({ mobileValue: '34vw', tabletValue: '13vw', defaultValue: '78px' })}
              left={getSize({ mobileValue: '13vw', tabletValue: '7vw', defaultValue: '50px' })}
              fontSize={getSize({ mobileValue: '15vw', tabletValue: '9vw', defaultValue: '80px' })}
            >
              {Math.round(+value)}
            </TextWrapper>
          </Box>
        ),
        key: 'Transaction bucket',
      });
    }
  }

  if (is_gmv && time_of_day && Object.keys(TimeImgMap).includes(time_of_day)) {
    carouselSlides.push({
      imgSrc: isMobile
        ? require(`assets/razorpay_rewind/time/${TimeImgMap[time_of_day]}-mobile.png`)
        : require(`assets/razorpay_rewind/time/${TimeImgMap[time_of_day]}-desktop.png`),
      imgOverlay: (
        <Box position={'relative'}>
          <Box
            position={'absolute'}
            left={isMobile ? '13vw' : isTablet ? '8vw' : '60px'}
            top={isMobile ? '100vw' : isTablet ? '32vw' : '220px'}
          >
            {TimeIntervalArray.map((timeInterval, index) => {
              return (
                <BestTimeContainer fontSize={isTablet ? '1.5vw' : '10px'} key={index}>
                  {timeInterval.label}
                  <Box
                    width={isTablet ? '12.5vw' : '110px'}
                    flexDirection={'row'}
                    display={'flex'}
                    alignItems={'center'}
                    marginLeft={index > 1 ? 'spacing.4' : 'spacing.3'}
                  >
                    <BestTimePercentage
                      height={isTablet ? '1.5vw' : '10px'}
                      width={`${timeInterval.value}%`}
                      isBestTime={timeInterval.label === TimeImgMap[time_of_day]}
                    />
                    {timeInterval.value}%
                  </Box>
                </BestTimeContainer>
              );
            })}
          </Box>
        </Box>
      ),
      key: 'Time of day',
    });
  }

  if (is_gmv && revenue_achieved_percentage) {
    carouselSlides.push({
      imgSrc: isMobile ? RevenueAchievedMobile : RevenueAchievedDesktop,
      imgOverlay: (
        <Box position={'relative'}>
          <TextWrapper
            top={getSize({
              mobileValue: '43vw',
              tabletValue: '20vw',
              defaultValue: '140px',
              smallMobileValue: '42vw',
            })}
            right={getSize({ mobileValue: 'auto', tabletValue: '20vw', defaultValue: '130px' })}
            left={getSize({ mobileValue: '12vw', tabletValue: 'auto', defaultValue: 'auto' })}
            fontSize={getSize({
              mobileValue: '19vw',
              tabletValue: '15vw',
              defaultValue: '110px',
              smallMobileValue: '18vw',
            })}
          >
            {revenue_achieved_percentage}%
          </TextWrapper>
        </Box>
      ),
      key: 'Revenue Achieved Percentile',
    });
  }

  if (is_gmv && industry_growth_percentage) {
    carouselSlides.push({
      imgSrc: isMobile ? IndustryGrowthMobile : IndustryGrowthDesktop,
      imgOverlay: (
        <Box position={'relative'}>
          <TextWrapper
            top={getSize({
              mobileValue: '40vw',
              tabletValue: '18vw',
              defaultValue: '130px',
              smallMobileValue: '39vw',
            })}
            right={getSize({ mobileValue: 'auto', tabletValue: '25vw', defaultValue: '180px' })}
            left={getSize({ mobileValue: '12vw', tabletValue: 'auto', defaultValue: 'auto' })}
            fontSize={getSize({
              mobileValue: '19vw',
              tabletValue: '15vw',
              defaultValue: '110px',
              smallMobileValue: '18vw',
            })}
          >
            {industry_growth_percentage}%
          </TextWrapper>
        </Box>
      ),
      key: 'Industry Growth Percentage',
    });
  }

  if (is_gmv && industry_grew_avg) {
    carouselSlides.push({
      imgSrc: isMobile ? PersonalGrowthMobile : PersonalGrowthDesktop,
      imgOverlay: (
        <Box position={'relative'}>
          <TextWrapper
            top={getSize({
              mobileValue: '40vw',
              tabletValue: '9vw',
              defaultValue: '57px',
              smallMobileValue: '39vw',
            })}
            right={getSize({ mobileValue: 'auto', tabletValue: 'auto', defaultValue: 'auto' })}
            left={getSize({ mobileValue: '12vw', tabletValue: '34vw', defaultValue: '240px' })}
            fontSize={getSize({
              mobileValue: '19vw',
              tabletValue: '14vw',
              defaultValue: '110px',
              smallMobileValue: '18vw',
            })}
          >
            {industry_grew_avg}
          </TextWrapper>
        </Box>
      ),
      key: 'Individual Growth Average',
    });
  }

  if (is_gmv && gmv_date && overall_gmv_percentage) {
    carouselSlides.push({
      imgSrc: isMobile ? BestDayMobile : BestDayDesktop,
      imgOverlay: (
        <Box position={'relative'}>
          <TextWrapper
            top={getSize({
              mobileValue: '46vw',
              tabletValue: '15vw',
              defaultValue: '90px',
              smallMobileValue: '45vw',
            })}
            right={getSize({ mobileValue: 'auto', tabletValue: 'auto', defaultValue: 'auto' })}
            left={getSize({ mobileValue: '13vw', tabletValue: '7vw', defaultValue: '45px' })}
            fontSize={getSize({ mobileValue: '8vw', tabletValue: '6vw', defaultValue: '55px' })}
            fontWeight={isMobile ? 700 : 800}
          >
            {isMobile ? formatDate(gmv_date) : gmv_date}
          </TextWrapper>
          <TextWrapper
            top={getSize({
              mobileValue: '63vw',
              tabletValue: '28vw',
              defaultValue: '195px',
              smallMobileValue: '60vw',
            })}
            right={getSize({ mobileValue: 'auto', tabletValue: '12vw', defaultValue: '70px' })}
            left={getSize({
              mobileValue: '13vw',
              tabletValue: 'auto',
              defaultValue: 'auto',
              smallMobileValue: '11.5vw',
            })}
            fontSize={getSize({
              mobileValue: '9vw',
              tabletValue: '7vw',
              defaultValue: '55px',
              smallMobileValue: '10vw',
            })}
            fontWeight={isMobile ? 700 : 800}
          >
            {overall_gmv_percentage}%
          </TextWrapper>
        </Box>
      ),
      key: 'Best Date GMV',
    });
  }
  return carouselSlides;
};

const fbBase = 'https://www.facebook.com/sharer/sharer.php';
const twitterBase = 'https://twitter.com/share';
const linkedinBase = 'https://www.linkedin.com/shareArticle';

export const socialShare = (componentRef, isMobile, type) => {
  let mediaUrl;

  const mediaMsg = window.encodeURIComponent(`"${postContent}"`);

  if (['facebook', 'twitter', 'linkedin'].includes(type) && !isMobile) {
    captureImage(componentRef, true, 'download');
  }

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
      window.open(mediaUrl, 'linkedin-share');
      break;

    default:
      return false;
  }

  return false;
};
