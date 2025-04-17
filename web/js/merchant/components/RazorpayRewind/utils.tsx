import React from 'react';
import { Box } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { merchantFetch } from 'merchant/utils/ajax';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { CarouselSlides, MilestoneDataT, MilestoneT, SizeOptions } from './types';
import {
  Milestone,
  MilestoneData,
  MilestoneDate,
  MilestoneLabel,
  MilestoneLabelPrefix,
  MilestoneLabelSuffix,
  MilestoneLabelValue,
  MilestoneLowerText,
  MilestoneRow,
  MilestoneUpperText,
  TextWrapper,
} from './styled';
import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';
import MobileLandingImage from 'assets/razorpay_rewind/mobile-entry.png';
import DesktopLandingImage from 'assets/razorpay_rewind/desktop-entry.png';
import IndustryGrowthMobile from 'assets/razorpay_rewind/industry-growth-mobile.png';
import IndustryGrowthDesktop from 'assets/razorpay_rewind/industry-growth-desktop.png';
import OutroDesktop from 'assets/razorpay_rewind/outro-desktop.png';
import OutroMobile from 'assets/razorpay_rewind/outro-mobile.png';
import MerchantGrowthDesktop from 'assets/razorpay_rewind/merchant-growth-desktop.png';
import MerchantGrowthMobile from 'assets/razorpay_rewind/merchant-growth-mobile.png';
import { captureImage } from './imageUtils';
import { RewindFonts } from './RazorpayRewind';

export const postContent =
  'Just relived my year in payments. Spoiler alert: we crushed it in FY2024! #RazorpayRewind2025';

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

      return data?.recap_resp || {};
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

const DayOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
const MonthsList = [
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
];

const MilestoneOne = {
  '₹50K': {
    prefix: '₹',
    value: '50',
    suffix: 'K',
    key: 'r50k',
  },
  '₹1L': {
    prefix: '₹',
    value: '1',
    suffix: 'L',
    key: 'r1l',
  },
  '₹50L': {
    prefix: '₹',
    value: '50',
    suffix: 'L',
    key: 'r50l',
  },
  '₹1Cr': {
    prefix: '₹',
    value: '1',
    suffix: 'cr',
    key: 'r1cr',
  },
  '₹25Cr': {
    prefix: '₹',
    value: '25',
    suffix: 'cr',
    key: 'r25cr',
  },
};

const MilestoneTwo = {
  '10K': {
    prefix: '',
    value: '10',
    suffix: 'K',
    key: 't10k',
  },
  '50K': {
    prefix: '',
    value: '50',
    suffix: 'K',
    key: 't50k',
  },
  '1L': {
    prefix: '',
    value: '1',
    suffix: 'L',
    key: 't1l',
  },
  '50L': {
    prefix: '',
    value: '50',
    suffix: 'L',
    key: 't50l',
  },
  '1Cr': {
    prefix: '',
    value: '1',
    suffix: 'cr',
    key: 't1cr',
  },
};

const MilestoneThree = {
  '24Hrs': {
    prefix: '',
    value: '24',
    suffix: 'hrs',
    key: 't24hrs',
  },
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

function formatInINRCr(x: number): string | null {
  if (0 < x && x < 1e7) {
    // Less than 1 Cr: use Indian number formatting without decimals
    return x.toLocaleString('en-IN', { maximumFractionDigits: 0 });
  } else if (x < 1e10) {
    // 1 Cr to <1000 Cr: show in Cr with 1 decimal
    return (x / 1e7).toFixed(1) + ' Cr';
  } else if (x >= 1e10) {
    // 1000 Cr and above: show in K Cr with 2 decimals
    return (x / 1e10).toFixed(2) + 'K Cr';
  } else {
    return null;
  }
}

const threeMilestonesDesktopPositions = [
  `
      left: 41%;
      top: 60%;
      transform: translate(-50%, -50%);
    `,
  `
      left: 63.5%;
      top: 47%;
      transform: translate(-50%, -50%);
    `,
  `
      left: 88.5%;
      top: 62%;
      transform: translate(-50%, -50%);
    `,
];

const threeMilestonesTabletPositions = [
  `
      left: 40%;
      top: 58%;
      transform: translate(-50%, -50%);
    `,
  `
      left: 65%;
      top: 47%;
      transform: translate(-50%, -50%);
    `,
  `
      left: 88%;
      top: 58%;
      transform: translate(-50%, -50%);
    `,
];

const threeMilestonesMobilePositions = [
  `
      left: 60%;
      top: 40%;
      transform: translate(-50%, -50%);
    `,
  `
      left: 35%;
      top: 60%;
      transform: translate(-50%, -50%);
    `,
  `
			left: 60%;
      top: 80%;
      transform: translate(-50%, -50%);
    `,
];

const twoMilestonesDesktopPositions = [
  `
      left: 50%;
      top: 47%;
      transform: translate(-50%, -50%);
    `,
  `
      left: 74%;
      top: 58%;
      transform: translate(-50%, -50%);
    `,
];

const twoMilestonesTabletPositions = [
  `
      left: 50%;
      top: 47%;
      transform: translate(-50%, -50%);
    `,
  `
      left: 75%;
      top: 58%;
      transform: translate(-50%, -50%);
    `,
];

const twoMilestonesMobilePositions = [
  `
      left: 62.5%;
      top: 51%;
      transform: translate(-50%, -50%);
    `,
  `
      left: 31.5%;
      top: 70%;
      transform: translate(-50%, -50%);
    `,
];

function getMilestonesPositions({ isTwoPiece, isMobile, isTablet }) {
  if (isMobile) {
    return isTwoPiece ? twoMilestonesMobilePositions : threeMilestonesMobilePositions;
  }

  if (isTablet) {
    return isTwoPiece ? twoMilestonesTabletPositions : threeMilestonesTabletPositions;
  }

  return isTwoPiece ? twoMilestonesDesktopPositions : threeMilestonesDesktopPositions;
}

function getMilestones(data: MilestoneDataT): MilestoneT[] {
  const {
    milestone_date_one,
    milestone_date_label_one,
    milestone_date_two,
    milestone_date_label_two,
    milestone_date_three,
    milestone_date_label_three,
  } = data ?? {};

  const validMilestones: MilestoneT[] = [];

  if (
    milestone_date_label_one &&
    milestone_date_one &&
    Object.keys(MilestoneOne).includes(milestone_date_label_one)
  ) {
    const { prefix, value, suffix } = MilestoneOne[milestone_date_label_one];
    validMilestones.push({
      date: milestone_date_one,
      prefix,
      value,
      suffix,
      upperText: 'First',
      lowerText: 'In Revenue',
    });
  }

  if (
    milestone_date_label_two &&
    milestone_date_two &&
    Object.keys(MilestoneTwo).includes(milestone_date_label_two)
  ) {
    const { prefix, value, suffix } = MilestoneTwo[milestone_date_label_two];
    validMilestones.push({
      date: milestone_date_two,
      prefix,
      value,
      suffix,
      upperText: 'First',
      lowerText: 'Transactions',
    });
  }

  if (
    milestone_date_label_three &&
    milestone_date_three &&
    Object.keys(MilestoneThree).includes(milestone_date_label_three)
  ) {
    const { prefix, value, suffix } = MilestoneThree[milestone_date_label_three];
    validMilestones.push({
      date: milestone_date_three,
      prefix,
      value,
      suffix,
      upperText: 'Most Revenue in',
      lowerText: '',
    });
  }

  return validMilestones;
}

export const getSlides = ({ data, isMobile = false, isTablet = false, isSmallMobile = false }) => {
  const {
    is_gmv,
    gmv,
    best_time,
    merchant_growth,
    industry_growth,
    best_day_of_week,
    best_month,
    transactions,
    merchant_growth_commentary,
    badge_of_honour,
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

  const carouselSlides: Array<CarouselSlides> = [];

  if (is_gmv) {
    carouselSlides.push({
      imgSrc: isMobile ? MobileLandingImage : DesktopLandingImage,
      imgOverlay: null,
      key: 'Intro',
    });
  }

  const milestones = getMilestones(data);
  if (is_gmv && milestones.length >= 2) {
    const isTwoPiece = milestones.length === 2;

    const milestonesPositions = getMilestonesPositions({
      isTwoPiece,
      isMobile,
      isTablet,
    });

    const shouldUseMobileFontSize = window && window.innerWidth <= 600;
    const styleProps = {
      shouldUseMobileFontSize,
      isMobile,
      isTablet,
    };

    carouselSlides.push({
      imgSrc: isMobile
        ? isTwoPiece
          ? require(`assets/razorpay_rewind/milestone/mobile_two_piece.png`)
          : require(`assets/razorpay_rewind/milestone/mobile_three_piece.png`)
        : isTwoPiece
        ? require(`assets/razorpay_rewind/milestone/desktop_two_piece.png`)
        : require(`assets/razorpay_rewind/milestone/desktop_three_piece.png`),
      imgOverlay: (
        <Box>
          {milestones.map(({ upperText, lowerText, date, prefix, suffix, value }, index) => (
            <Milestone key={index} position={milestonesPositions[index]} {...styleProps}>
              <MilestoneDate {...styleProps}>{date}</MilestoneDate>
              <MilestoneData {...styleProps}>
                {upperText ? (
                  <MilestoneUpperText {...styleProps}>{upperText}</MilestoneUpperText>
                ) : null}
                <MilestoneRow {...styleProps}>
                  <MilestoneLabel {...styleProps}>
                    {prefix ? (
                      <MilestoneLabelPrefix {...styleProps}>{prefix}</MilestoneLabelPrefix>
                    ) : null}
                    <MilestoneLabelValue {...styleProps}>{value}</MilestoneLabelValue>
                    {suffix ? (
                      <MilestoneLabelSuffix {...styleProps}>{suffix}</MilestoneLabelSuffix>
                    ) : null}
                  </MilestoneLabel>
                  {lowerText ? (
                    <MilestoneLowerText {...styleProps}>{lowerText}</MilestoneLowerText>
                  ) : null}
                </MilestoneRow>
              </MilestoneData>
            </Milestone>
          ))}
        </Box>
      ),
      key: 'milestone',
    });
  }

  if (is_gmv && badge_of_honour) {
    carouselSlides.push({
      imgSrc: isMobile
        ? require(`assets/razorpay_rewind/top-${badge_of_honour}-mobile.png`)
        : require(`assets/razorpay_rewind/top-${badge_of_honour}-desktop.png`),
      imgOverlay: null,
      key: 'badge_of_honour',
    });
  }

  if (is_gmv && best_time && Object.keys(TimeImgMap).includes(best_time)) {
    carouselSlides.push({
      imgSrc: isMobile
        ? require(`assets/razorpay_rewind/time/${TimeImgMap[best_time]}-mobile.png`)
        : require(`assets/razorpay_rewind/time/${TimeImgMap[best_time]}-desktop.png`),
      imgOverlay: null,
      key: 'best_time',
    });
  }

  if (is_gmv && best_day_of_week && DayOfWeek.includes(best_day_of_week)) {
    carouselSlides.push({
      imgSrc: isMobile
        ? require(`assets/razorpay_rewind/day/${best_day_of_week.toLowerCase()}-mobile.png`)
        : require(`assets/razorpay_rewind/day/${best_day_of_week.toLowerCase()}-desktop.png`),
      imgOverlay: null,
      key: 'best_day_of_week',
    });
  }

  if (is_gmv && best_month && MonthsList.includes(best_month)) {
    carouselSlides.push({
      imgSrc: isMobile
        ? require(`assets/razorpay_rewind/month/${best_month.toLowerCase()}-mobile.png`)
        : require(`assets/razorpay_rewind/month/${best_month.toLowerCase()}-desktop.png`),
      imgOverlay: null,
      key: 'best_month',
    });
  }

  const transactionsNumber = Number(transactions);
  if (
    is_gmv &&
    transactionsNumber &&
    merchant_growth_commentary &&
    typeof transactionsNumber === 'number' &&
    !isNaN(transactionsNumber)
  ) {
    const transactionString = formatInINRCr(transactionsNumber);
    const txnBucketType =
      transactionsNumber < 10_000
        ? 'sub10k'
        : transactionsNumber < 60_000
        ? '10k_60k'
        : transactionsNumber < 50_00_000
        ? '60k_50l'
        : transactionsNumber <= 105_00_00_000
        ? '50l_105c'
        : null;
    const textMapping = {
      sub10k: () => `What a takeoff!\nHere’s to reaching new heights.`,
      '10k_60k': (x) => `That’s ${x} fully sold out \nflights to Goa!`,
      '60k_50l': (x) => `That's ${x} fully sold out\nWankhede Stadiums!`,
      '50l_105c': (x) => `That's every movie theatre \nin India sold out ${x} times!`,
    };
    const isSub10K = txnBucketType === 'sub10k';
    const text =
      txnBucketType && textMapping[txnBucketType]
        ? textMapping[txnBucketType](merchant_growth_commentary)
        : null;
    if (txnBucketType) {
      carouselSlides.push({
        imgSrc: isMobile
          ? require(`assets/razorpay_rewind/transactions/${txnBucketType}-mobile.png`)
          : require(`assets/razorpay_rewind/transactions/${txnBucketType}-desktop.png`),
        imgOverlay: (
          <Box position={'relative'}>
            <TextWrapper
              top={getSize({
                mobileValue: '34vw',
                tabletValue: '10vw',
                defaultValue: '75px',
                smallMobileValue: '32vw',
              })}
              left={getSize({
                mobileValue: '0px',
                tabletValue: isSub10K ? '5vw' : '0px',
                defaultValue: isSub10K ? '35px' : '0px',
              })}
              right={getSize({ mobileValue: '3vw', tabletValue: '0px', defaultValue: '0px' })}
              fontSize={getSize({
                mobileValue: '13vw',
                tabletValue: '19vw',
                defaultValue: '140px',
              })}
              fontFamily={RewindFonts.SnugSharp}
              textAlign={isMobile ? 'center' : 'unset'}
            >
              {`\u00A0\u00A0${transactionString}`}
            </TextWrapper>
            <TextWrapper
              top={getSize({
                mobileValue: '94vw',
                tabletValue: '39.5vw',
                defaultValue: '280px',
                smallMobileValue: '90vw',
              })}
              left={getSize({ mobileValue: 'auto', tabletValue: 'auto', defaultValue: 'auto' })}
              right={getSize({ mobileValue: '19vw', tabletValue: '9vw', defaultValue: '40px' })}
              fontSize={getSize({ mobileValue: '3vw', tabletValue: '1.9vw', defaultValue: '16px' })}
              fontWeight={400}
              color="#ffffff"
              fontFamily={RewindFonts.TasaOrbiter}
            >
              {!isSub10K ? text : null}
            </TextWrapper>
          </Box>
        ),
        key: 'transactions',
      });
    }
  }

  const gmvNumber = Number(gmv);
  if (is_gmv && gmvNumber && typeof gmvNumber === 'number' && !isNaN(gmvNumber)) {
    const gmvString = formatInINRCr(gmvNumber);
    const gmvType =
      0 < gmvNumber && gmvNumber < 500000
        ? 'sme'
        : // 5 cr
        gmvNumber < 50000000
        ? 'mm'
        : //  > 5cr
        gmvNumber >= 50000000
        ? 'ent'
        : null;
    if (gmvType && gmvString) {
      carouselSlides.push({
        imgSrc: isMobile
          ? require(`assets/razorpay_rewind/gmv-${gmvType}-mobile.png`)
          : require(`assets/razorpay_rewind/gmv-${gmvType}-desktop.png`),
        imgOverlay: (
          <Box position={'relative'}>
            <TextWrapper
              top={getSize({
                mobileValue: '53vw',
                tabletValue: '14vw',
                defaultValue: '100px',
                smallMobileValue: '52vw',
              })}
              left={getSize({ mobileValue: '0vw', tabletValue: '14vw', defaultValue: '105px' })}
              fontSize={getSize({
                mobileValue: '10vw',
                tabletValue: '13vw',
                defaultValue: '100px',
              })}
              fontFamily={RewindFonts.SnugSharp}
              textAlign={isMobile ? 'center' : 'unset'}
            >
              {`₹${gmvString}`}
            </TextWrapper>
          </Box>
        ),
        key: 'quarter_gmv',
      });
    }
  }

  if (is_gmv && merchant_growth) {
    carouselSlides.push({
      imgSrc: isMobile ? MerchantGrowthMobile : MerchantGrowthDesktop,
      imgOverlay: (
        <Box position={'relative'}>
          <TextWrapper
            top={getSize({
              mobileValue: '37vw',
              tabletValue: '7vw',
              defaultValue: '50px',
              smallMobileValue: '36vw',
            })}
            right={getSize({ mobileValue: 'auto', tabletValue: 'auto', defaultValue: 'auto' })}
            left={getSize({ mobileValue: '14vw', tabletValue: '12vw', defaultValue: '80px' })}
            fontSize={getSize({
              mobileValue: '16vw',
              tabletValue: '23vw',
              defaultValue: '165px',
              smallMobileValue: '16vw',
            })}
            fontFamily={RewindFonts.SnugSharp}
          >
            {merchant_growth}%
          </TextWrapper>
        </Box>
      ),
      key: 'merchant_growth',
    });
  }

  if (is_gmv && industry_growth) {
    carouselSlides.push({
      imgSrc: isMobile ? IndustryGrowthMobile : IndustryGrowthDesktop,
      imgOverlay: (
        <Box position={'relative'}>
          <TextWrapper
            top={getSize({
              mobileValue: '37vw',
              tabletValue: '7vw',
              defaultValue: '50px',
              smallMobileValue: '36vw',
            })}
            right={getSize({ mobileValue: 'auto', tabletValue: 'auto', defaultValue: 'auto' })}
            left={getSize({ mobileValue: '14vw', tabletValue: '12vw', defaultValue: '80px' })}
            fontSize={getSize({
              mobileValue: '16vw',
              tabletValue: '23vw',
              defaultValue: '165px',
              smallMobileValue: '16vw',
            })}
            fontFamily={RewindFonts.SnugSharp}
          >
            {industry_growth}%
          </TextWrapper>
        </Box>
      ),
      key: 'industry_growth',
    });
  }

  if (is_gmv) {
    carouselSlides.push({
      imgSrc: isMobile ? OutroMobile : OutroDesktop,
      imgOverlay: null,
      key: 'Outro',
    });
  }

  return carouselSlides;
};

const fbBase = 'https://www.facebook.com/sharer/sharer.php';
const twitterBase = 'https://twitter.com/share';
const linkedinBase = 'https://www.linkedin.com/shareArticle';

export const socialShare = ({ componentRef, isMobile, isTablet = false, type }) => {
  let mediaUrl;

  const mediaMsg = window.encodeURIComponent(`${postContent}`);

  if (['facebook', 'twitter', 'linkedin'].includes(type) && !isMobile) {
    captureImage(componentRef, isTablet, 'download');
  }

  switch (type) {
    case 'facebook':
      mediaUrl = `${fbBase}?quote=${mediaMsg}`;
      window.open(mediaUrl, 'facebook-share');
      break;

    case 'twitter':
      mediaUrl = `${twitterBase}?url=%20&text=${mediaMsg}`;
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

export function addFontLinkIfMissing(linkId: string, url: string) {
  const existingLink = document.getElementById(linkId);

  if (!existingLink) {
    const link = document.createElement('link');
    link.id = linkId;
    link.href = url;
    link.rel = 'stylesheet';
    document.head.appendChild(link);
  }
}
