import React, { Ref, useEffect, useMemo } from 'react';
import {
  Card,
  CardBody,
  Box,
  CardHeader,
  BoxRefType,
  ArrowDownRightIcon,
  Text,
  Amount,
  Skeleton,
  AcceptPaymentsIcon,
} from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';
import useOneHomeAnalytics from '@apps/one-home/src/hooks/useOneHomeAnalytics';
import {
  CARD_SHIMMER_HEIGHT,
  CARD_SHIMMER_WIDTH,
  CARD_SHIMMER_MIN_WIDTH,
  messages,
  EARNINS_CARD_MIN_WIDTH,
  EASY_ONBOARDING_URL,
  CARD_WIDTH_MIN_MOBILE_VIEW,
} from '../constants';
import generateEarningsCardConfig from './utils/generateEarningsCardConfig';
import GrowthCard from '../components/GrowthCard/GrowthCard';
import LockedCard from '../components/LockedCard/LockedCard';
import SummaryItem, { SummaryBreakupItem } from '../components/SummaryItem/SummaryItem';
import { EarningsCardProps, EarningsAction } from './types';

import paymentsLockedImg from '../../../assets/payments_locked.png';
import paymentsGrowthImage from '../../../assets/payments_growth.png';
import PercentageChangeDisplay from '../components/PercentageChangeDisplay/PercentageChangeDisplay';
import { dateFilterOptionsMap } from '../config';

const MIN_EARNINGS_CARD_WIDTH = '232px';

const earningsCardAnalyticsCommonProperties = {
  widgetId: messages.businessSummarySection.analytics.widgetId,
  title: messages.businessSummarySection.analytics.title,
  cardType: messages.earningsCard.analytics.cardType,
};

const EarningsCard = React.forwardRef<BoxRefType, EarningsCardProps>(
  ({ isLoading, data, dateFilter }, ref: Ref<BoxRefType> | undefined) => {
    const { trackOneHomeAnalytics } = useOneHomeAnalytics();
    const navigate = useNavigate();
    if (data && 'error' in data) {
      throw data.error;
    }

    const earningsDatum = useMemo(() => {
      if (!data) return null;
      return generateEarningsCardConfig(data?.data?.one_home_data?.business_summary?.payment);
    }, [data]);

    useEffect(() => {
      if (!isLoading && earningsDatum) {
        if (earningsDatum) {
          trackOneHomeAnalytics({
            objectName: 'Ucs widget',
            actionName: 'Loaded',
            properties: {
              ...earningsCardAnalyticsCommonProperties,
              dateRange: dateFilter,
              subWidgetId: `${messages.earningsCard.analytics.subwidgetId}_${data?.id}`,
              growthPagePresent: earningsDatum?.type === 'growth',
              businessSummaryState: 'ideal',
            },
          });
        }
      }
    }, [dateFilter, earningsDatum, isLoading]);

    if (isLoading || !earningsDatum) {
      return (
        <Box display={'flex'} justifyContent={'center'}>
          <Skeleton
            testID="business-insights-earnings-card-loader"
            width={{
              base: CARD_SHIMMER_WIDTH,
              m: CARD_SHIMMER_MIN_WIDTH,
              l: CARD_SHIMMER_WIDTH,
              xl: CARD_SHIMMER_WIDTH,
            }}
            height={CARD_SHIMMER_HEIGHT}
            borderRadius="large"
          />
        </Box>
      );
    }

    if (earningsDatum.type === 'locked') {
      return (
        <Box ref={ref} width={{ base: '100%', m: '100%', l: 'auto', xl: 'auto' }}>
          <LockedCard
            title={messages.earningsCard.lockedCardTitle}
            description={messages.earningsCard.lockedCardDescription}
            imgSrc={paymentsLockedImg}
            icon={AcceptPaymentsIcon}
          />
        </Box>
      );
    }

    const handleEarningGrowthClick = () => {
      trackOneHomeAnalytics({
        objectName: 'Ucs Link',
        actionName: 'Clicked',
        properties: {
          ...earningsCardAnalyticsCommonProperties,
          subWidgetId: `${messages.earningsCard.analytics.subwidgetId}_${data?.id}`,
          dateRange: dateFilter,
          businessSummaryState: 'ideal',
          buttonName: messages.earningsCard.growthCardCtaLabel,
        },
      });
      window.open(EASY_ONBOARDING_URL, '_blank');
    };

    const handleEarningItemClick = (action: EarningsAction | undefined) => {
      trackOneHomeAnalytics({
        objectName: 'Ucs Link',
        actionName: 'Clicked',
        properties: {
          ...earningsCardAnalyticsCommonProperties,
          subWidgetId: `${messages.earningsCard.analytics.subwidgetId}_${data?.id}`,
          dateRange: dateFilter,
          businessSummaryState: 'ideal',
          buttonName: action?.label,
        },
      });

      if (action?.type === 'navigate-external') {
        window.open(action.path, '_blank');
      } else if (action?.type === 'navigate-internal') {
        navigate(action.path);
      }
    };

    if (earningsDatum.type === 'growth') {
      return (
        <Box ref={ref} width={{ base: '100%', m: '100%', l: 'auto', xl: 'auto' }}>
          <GrowthCard
            title={messages.earningsCard.growthCardTitle}
            description={messages.earningsCard.growthCardDescription}
            icon={AcceptPaymentsIcon}
            action={{
              label: messages.earningsCard.growthCardCtaLabel,
              onClick: handleEarningGrowthClick,
            }}
            imageSrc={paymentsGrowthImage}
          />
        </Box>
      );
    }

    return (
      <Box ref={ref} width={{ base: '100%', m: '40%', l: 'auto', xl: 'auto' }}>
        {isLoading ? (
          <Skeleton width={CARD_SHIMMER_WIDTH} height={CARD_SHIMMER_HEIGHT} borderRadius="large" />
        ) : (
          <Card
            padding="spacing.5"
            elevation="none"
            minWidth={{
              base: EARNINS_CARD_MIN_WIDTH,
              m: CARD_WIDTH_MIN_MOBILE_VIEW,
              l: EARNINS_CARD_MIN_WIDTH,
              xl: EARNINS_CARD_MIN_WIDTH,
            }}
            borderRadius="large"
          >
            <CardHeader>
              <Box>
                <Box display="flex" gap="4px" alignItems="center">
                  <ArrowDownRightIcon size="small" color="surface.icon.gray.muted" />
                  <Text
                    size="xsmall"
                    variant="body"
                    color="surface.text.gray.muted"
                    weight="semibold"
                  >
                    {messages.earningsCard.title}
                  </Text>
                </Box>

                <Box display="flex" justifyContent="center" alignItems="center" gap="spacing.4">
                  <Amount
                    type="heading"
                    size="xlarge"
                    weight="semibold"
                    isAffixSubtle
                    suffix="humanize"
                    currencyIndicator="currency-symbol"
                    value={earningsDatum.total}
                    currency={earningsDatum.currency}
                  />

                  <PercentageChangeDisplay
                    percentageChange={Number(
                      parseFloat(`${earningsDatum?.percentageChange || 0}`).toFixed(2),
                    )}
                    timeDate={dateFilterOptionsMap[earningsDatum.inputTime]}
                  />
                </Box>
              </Box>
            </CardHeader>
            <CardBody>
              <Box display="flex" flexDirection="column" gap="spacing.5">
                {earningsDatum.earnings?.map((earning, index) => (
                  <Box
                    key={`earnings_component_${earning.title}_${index}`}
                    display="flex"
                    flexDirection="column"
                  >
                    <SummaryItem
                      title={earning.title}
                      amount={earning.amount}
                      currency={earning.currency}
                      icon={earning.icon}
                      isEnabled={earning.showComponent}
                      onClick={() => handleEarningItemClick(earning.action)}
                    />
                    {earning?.breakup.length >= 1
                      ? earning.breakup.map((breakupItem, subComponentIndex) => {
                          return breakupItem.showComponent ? (
                            breakupItem.title ? (
                              <SummaryBreakupItem
                                key={`earnings_subcomponent_${earning.title}_${index}_subComponent_${subComponentIndex}`}
                                title={breakupItem.title}
                                amount={breakupItem.amount}
                                currency={breakupItem.currency}
                              />
                            ) : null
                          ) : null;
                        })
                      : null}
                  </Box>
                ))}
              </Box>
            </CardBody>
          </Card>
        )}
      </Box>
    );
  },
);

export default EarningsCard;
