import React, { useMemo, useEffect } from 'react';
import {
  Card,
  CardBody,
  Box,
  CardHeader,
  BoxRefType,
  Text,
  Amount,
  ArrowUpRightIcon,
  Skeleton,
  BusinessBankingIcon,
} from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';
import {
  CARD_SHIMMER_HEIGHT,
  CARD_SHIMMER_WIDTH,
  CARD_SHIMMER_MIN_WIDTH,
  SPENDS_CARD_MIN_WIDTH,
  messages,
  CARD_WIDTH_MIN_MOBILE_VIEW,
} from '../constants';
import useOneHomeAnalytics from '@apps/one-home/src/hooks/useOneHomeAnalytics';
import generateSpendingsCardConfig from './utils/generateSpendsCardConfig';
import GrowthCard from '../components/GrowthCard/GrowthCard';
import LockedCard from '../components/LockedCard/LockedCard';
import SummaryItem, { SummaryBreakupItem } from '../components/SummaryItem/SummaryItem';
import { SpendingsAction, SpendsCardProps } from './types';
import PercentageChangeDisplay from '../components/PercentageChangeDisplay/PercentageChangeDisplay';
import { dateFilterOptionsMap } from '../config';
import bankingGrowthImg from '../../../assets/banking_growth.png';
import bankingLocked from '../../../assets/banking_locked.png';

const MIN_SPENDS_CARD_WIDTH = '232px';

const spendsCardAnalyticsCommonProperties = {
  widgetId: messages.businessSummarySection.analytics.widgetId,
  title: messages.businessSummarySection.analytics.title,
  cardType: messages.spendsCard.analytics.cardType,
};

const SpendsCard = React.forwardRef<BoxRefType, SpendsCardProps>(
  ({ isLoading, data, dateFilter }, ref) => {
    const { trackOneHomeAnalytics } = useOneHomeAnalytics();

    const navigate = useNavigate();

    if (data && 'error' in data) {
      throw data.error;
    }

    const spendingsDatum = useMemo(() => {
      if (!data) return null;
      return generateSpendingsCardConfig(data?.data?.one_home_data?.business_summary?.payout);
    }, [data]);

    useEffect(() => {
      if (!isLoading && spendingsDatum) {
        if (spendingsDatum) {
          trackOneHomeAnalytics({
            objectName: 'Ucs widget',
            actionName: 'Loaded',
            properties: {
              ...spendsCardAnalyticsCommonProperties,
              dateRange: dateFilter,
              subWidgetId: `${messages.spendsCard.analytics.subwidgetId}_${data?.id}`,
              growthPagePresent: spendingsDatum.type === 'growth',
              businessSummaryState: 'ideal',
            },
          });
        }
      }
    }, [dateFilter, spendingsDatum, isLoading]);

    if (isLoading || !spendingsDatum) {
      return (
        <Box display={'flex'} justifyContent={'center'}>
          <Skeleton
            testID="business-insights-spends-card-loader"
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

    const handleSpendingItemClick = (action: SpendingsAction | undefined) => {
      trackOneHomeAnalytics({
        objectName: 'Ucs Link',
        actionName: 'Clicked',
        properties: {
          ...spendsCardAnalyticsCommonProperties,
          subWidgetId: `${messages.spendsCard.analytics.subwidgetId}_${data?.id}`,
          dateRange: dateFilter,
          businessSummaryState: 'ideal',
          buttonName: action?.label,
        },
      });

      if (!action?.path || !action?.url) return;

      // TODO: Replace this with actual experiment flag check
      const isBankingExperimentEnabled = false;
      const isPayrollExperimentEnabled = false; //Slack Thread: https://razorpay.slack.com/archives/C06F9MYVBR7/p1743580184123169

      const bankingTargetPath = isBankingExperimentEnabled ? action.url : action.path;

      //Handle for payroll
      if (isPayrollExperimentEnabled) {
        navigate(action.path);
      } else {
        window.open(action.url, '_blank');
      }

      if (isBankingExperimentEnabled) {
        navigate(bankingTargetPath);
      } else {
        window.open(bankingTargetPath, '_blank');
      }
    };

    const handleBankingGrowthOnClick = () => {
      trackOneHomeAnalytics({
        objectName: 'Ucs Link',
        actionName: 'Clicked',
        properties: {
          ...spendsCardAnalyticsCommonProperties,
          subWidgetId: `${messages.spendsCard.analytics.subwidgetId}_${data?.id}`,
          dateRange: dateFilter,
          businessSummaryState: 'ideal',
          buttonName: messages.spendsCard.growthCardCtaLabel,
        },
      });
      navigate('/banking');
    };

    if (spendingsDatum.type === 'locked') {
      return (
        <Box ref={ref} width={{ base: '100%', m: '100%', l: 'auto', xl: 'auto' }}>
          <LockedCard
            title={messages.spendsCard.lockedCardTitle}
            description={messages.spendsCard.lockedCardDescription}
            icon={BusinessBankingIcon}
            imgSrc={bankingLocked}
          />
        </Box>
      );
    }

    if (spendingsDatum.type === 'growth') {
      return (
        <Box ref={ref} width={{ base: '100%', m: '100%', l: 'auto', xl: 'auto' }}>
          <GrowthCard
            title={messages.spendsCard.growthCardTitle}
            description={messages.spendsCard.growthCardDescription}
            imageSrc={bankingGrowthImg}
            action={{
              label: messages.spendsCard.growthCardCtaLabel,
              onClick: handleBankingGrowthOnClick,
            }}
            icon={BusinessBankingIcon}
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
            elevation="none"
            padding="spacing.5"
            minWidth={{
              base: SPENDS_CARD_MIN_WIDTH,
              m: CARD_WIDTH_MIN_MOBILE_VIEW,
              l: SPENDS_CARD_MIN_WIDTH,
              xl: SPENDS_CARD_MIN_WIDTH,
            }}
            borderRadius="large"
          >
            <CardHeader>
              <Box>
                <Box display="flex" gap="4px" alignItems="center">
                  <ArrowUpRightIcon size="small" color="surface.icon.gray.muted" />
                  <Text
                    size="xsmall"
                    variant="body"
                    color="surface.text.gray.muted"
                    weight="semibold"
                  >
                    {messages.spendsCard.title}
                  </Text>
                </Box>
                <Box display="flex" justifyContent="center" alignItems="center" gap="12px">
                  <Amount
                    type="heading"
                    size="xlarge"
                    weight="semibold"
                    isAffixSubtle
                    suffix="humanize"
                    currencyIndicator="currency-symbol"
                    value={spendingsDatum.total}
                    currency={spendingsDatum.currency}
                  />
                  <PercentageChangeDisplay
                    percentageChange={Number(
                      parseFloat(`${spendingsDatum?.percentageChange || 0}`).toFixed(2),
                    )}
                    timeDate={dateFilterOptionsMap[spendingsDatum.inputTime]}
                  />
                </Box>
              </Box>
            </CardHeader>
            <CardBody>
              <Box display="flex" flexDirection="column" rowGap="16px">
                {spendingsDatum.spendings.map((spending, index) => {
                  return (
                    <Box
                      key={`earnings_component_${spending.title}_${index}`}
                      display="flex"
                      flexDirection="column"
                    >
                      <SummaryItem
                        title={spending.title}
                        amount={spending.amount}
                        currency={spending.currency}
                        icon={spending.icon}
                        isEnabled={spending.showComponent}
                        onClick={() => handleSpendingItemClick(spending.action)}
                      />
                      {spending.breakup.length >= 2
                        ? spending.breakup.map((breakupItem, subComponentIndex) => (
                            <SummaryBreakupItem
                              key={`earnings_subcomponent_${spending.title}_${index}_subComponent_${subComponentIndex}`}
                              title={breakupItem.title}
                              amount={breakupItem.amount}
                              currency={breakupItem.currency}
                            />
                          ))
                        : null}
                    </Box>
                  );
                })}
              </Box>
            </CardBody>
          </Card>
        )}
      </Box>
    );
  },
);

export default SpendsCard;
