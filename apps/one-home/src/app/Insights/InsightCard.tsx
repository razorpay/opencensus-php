import React, { useEffect } from 'react';
import { paiseToRupees } from '@libs/shared-utils';
import {
  Amount,
  AnimateInteractions,
  ArrowSquareDownIcon,
  ArrowSquareUpIcon,
  ArrowUpRightIcon,
  Box,
  Button,
  Card,
  Elevate,
  Heading,
  InfoIcon,
  Link,
  Move,
  Skeleton,
  Text,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';
import useOneHomeAnalytics from '../../hooks/useOneHomeAnalytics';
import { insightCardsStaticData, staticContent } from './constants';
import RayInsight from './RayInsight';
import {
  InsightCardButtonProp,
  InsightCardContentProp,
  InsightCardHeaderProp,
  InsightCardProp,
  InsightCardType,
  InsightCardWithErrorBoundaryProp,
  OtherInsightCardType,
} from './types';
import { getAmountSuffix, getPercentageChangeSign, isEmptyRayInsight } from './utils';
import { ErrorBoundary } from '@libs/shared-ui';
import { DASHBOARD_PRIORITY_RANKS, DASHBOARD_TEAMS } from '@libs/shared-types';
import ErrorState from '../../components/ErrorBoundary/ErrorState';
import LockedCard from './LockedCard';
import EmptyCard from './EmptyCard';

export const InsightCardHeader = ({
  insightsStaticData,
  isMobile,
  componentData,
  analytics,
  showViewDetails,
}: InsightCardHeaderProp) => {
  const { trackOneHomeAnalytics } = useOneHomeAnalytics();
  const navigate = useNavigate();

  const trackViewDetailsClick = () => {
    trackOneHomeAnalytics({
      objectName: 'Ucs Link',
      actionName: 'Clicked',
      properties: { ...analytics },
    });
  };

  return (
    <Box
      display="flex"
      alignItems="center"
      justifyContent="space-between"
      marginBottom={{ base: 'spacing.2', s: 'spacing.2', m: 'spacing.0', l: 'spacing.2' }}
      whiteSpace="noWrap"
    >
      <Box gap="spacing.2" alignItems="center" display="flex">
        {isMobile ? (
          <Text variant="body" color="surface.text.gray.subtle" size="small" weight="semibold">
            {componentData.title}
          </Text>
        ) : (
          <Heading color="surface.text.gray.muted" size="small" weight="semibold">
            {componentData.title}
          </Heading>
        )}

        {insightsStaticData.tooltipContent && (
          <Tooltip content={insightsStaticData.tooltipContent} placement="bottom">
            <TooltipInteractiveWrapper display="flex">
              {isMobile ? (
                <InfoIcon color="surface.icon.gray.muted" size="medium" />
              ) : (
                <InfoIcon color="surface.icon.gray.muted" size="small" />
              )}
            </TooltipInteractiveWrapper>
          </Tooltip>
        )}
      </Box>

      {!isMobile && showViewDetails && (
        <Move motionTriggers={['on-animate-interactions']}>
          <Link
            size="medium"
            color="primary"
            icon={ArrowUpRightIcon}
            iconPosition="right"
            onClick={() => {
              navigate(insightsStaticData.redirectionUrl);
              trackViewDetailsClick();
            }}
            accessibilityLabel={staticContent.insightsLinkLabel}
          >
            {staticContent.viewDetailsText}
          </Link>
        </Move>
      )}
    </Box>
  );
};

const InsightCardContent = ({ cardType, isMobile, dataSummary }: InsightCardContentProp) => {
  const percentage_change = dataSummary?.percentage_change ?? 0;
  const trend = getPercentageChangeSign(percentage_change);
  const amountSuffixText = getAmountSuffix(dataSummary?.input_time ?? '');
  const trendColour =
    trend === 'positive' ? 'feedback.text.positive.intense' : 'feedback.text.negative.intense';

  return (
    <Box
      display="flex"
      alignItems="baseline"
      gap={{ base: 'spacing.3', s: 'spacing.3', m: 'spacing.7', l: 'spacing.7' }}
    >
      {cardType === 'success_rate' ? (
        <Heading size="2xlarge" color="surface.text.gray.normal" weight="semibold">
          {dataSummary?.current_data}%
        </Heading>
      ) : (
        <Amount
          value={paiseToRupees(Number(dataSummary?.current_data) ?? 0)}
          isAffixSubtle
          suffix="humanize"
          currencyIndicator="currency-symbol"
          currency="INR"
          size="2xlarge"
          color="surface.text.gray.normal"
          weight="semibold"
          type="heading"
        />
      )}

      {trend !== 'neutral' && (
        <Box display="flex" gap="spacing.2" alignItems="center" transform={`translateY(3px)`}>
          {trend === 'positive' ? (
            <ArrowSquareUpIcon
              color="feedback.icon.positive.intense"
              size={isMobile ? 'medium' : 'xlarge'}
            />
          ) : (
            <ArrowSquareDownIcon
              color="feedback.icon.negative.intense"
              size={isMobile ? 'medium' : 'xlarge'}
            />
          )}
          {isMobile ? (
            <Text size="xsmall" color={trendColour} weight="semibold">
              {Math.abs(percentage_change)}% {amountSuffixText}
            </Text>
          ) : (
            <Text variant="body" size="medium" color={trendColour} weight="semibold" display="flex">
              {Math.abs(percentage_change)}%&nbsp;
              <Text
                variant="body"
                display={{ base: 'none', m: 'none', l: 'block' }}
                size="medium"
                color={trendColour}
                weight="semibold"
              >
                {amountSuffixText}
              </Text>
            </Text>
          )}
        </Box>
      )}
    </Box>
  );
};

const InsightCardButton = ({ insightsStaticData, analytics }: InsightCardButtonProp) => {
  const navigate = useNavigate();
  const { trackOneHomeAnalytics } = useOneHomeAnalytics();

  const trackDetailedBreakdownClick = () => {
    const properties = {
      ...analytics,
      buttonName: `${staticContent.showDetailedBreakdownText}`,
    };
    trackOneHomeAnalytics({
      objectName: 'Ucs Link',
      actionName: 'Clicked',
      properties: { ...properties },
    });
  };
  return (
    <Button
      variant="secondary"
      color="primary"
      size="small"
      isFullWidth
      marginTop="spacing.5"
      onClick={() => {
        navigate(insightsStaticData.redirectionUrl);
        trackDetailedBreakdownClick();
      }}
      accessibilityLabel={staticContent.mobileBtnLabel}
    >
      {staticContent.showDetailedBreakdownText}
    </Button>
  );
};

const InsightCardWithErrorBoundary = ({
  cardType,
  isMobile,
  componentData,
  source,
  analytics,
  paymentLocked,
}: InsightCardWithErrorBoundaryProp) => {
  const { trackOneHomeAnalytics } = useOneHomeAnalytics();

  if (!componentData || componentData?.error) {
    throw componentData && componentData.error
      ? componentData.error
      : new Error('Error fetching data in Insight Card');
  }

  useEffect(() => {
    if (!componentData || componentData?.error) return;

    const items = [];

    // Add view details action if available (desktop)
    if (!isMobile) {
      items.push(staticContent.viewDetailsText);
    }

    // Add mobile-specific action if available
    if (isMobile) {
      items.push(staticContent.showDetailedBreakdownText);
    }

    trackOneHomeAnalytics({
      objectName: 'Ucs Widget',
      actionName: 'Loaded',
      properties: {
        ...analytics,
        items,
      },
    });
  }, [componentData, isMobile]);

  const insightsStaticData =
    insightCardsStaticData[cardType as InsightCardType | OtherInsightCardType];

  if (paymentLocked) {
    return (
      <LockedCard
        insightsStaticData={insightsStaticData}
        isMobile={isMobile}
        componentData={componentData}
        analytics={analytics}
      />
    );
  }

  let cardData;
  if (source === 'other_insights') {
    cardData =
      componentData?.data?.one_home_data?.other_insight?.[cardType as OtherInsightCardType];
  } else {
    cardData = componentData?.data?.one_home_data?.insight?.[cardType as InsightCardType];
  }

  const dataSummary = cardData?.data_summary;
  const isEmptyDataSummary =
    !dataSummary ||
    Object.keys(dataSummary).length === 0 ||
    dataSummary?.current_data == null ||
    dataSummary?.percentage_change == null ||
    dataSummary?.current_data === 0;

  if (isEmptyDataSummary) {
    return (
      <EmptyCard
        insightsStaticData={insightsStaticData}
        isMobile={isMobile}
        componentData={componentData}
        analytics={analytics}
        cardType={cardType}
        dataSummary={dataSummary}
      />
    );
  }

  const rayInsightsData = cardData?.ray_insight || {};
  const isEmptyRayInsightsData = isEmptyRayInsight(cardType, rayInsightsData);

  return (
    <Box
      margin={{
        base: 'spacing.0',
        s: 'spacing.0',
        m: 'spacing.5',
        l: 'spacing.5',
      }}
    >
      <InsightCardHeader
        insightsStaticData={insightsStaticData}
        isMobile={isMobile}
        componentData={componentData}
        analytics={analytics}
        showViewDetails={true}
      />
      <InsightCardContent cardType={cardType} isMobile={isMobile} dataSummary={dataSummary} />
      {!isEmptyRayInsightsData && (
        <RayInsight
          cardType={cardType}
          insightsStaticData={insightsStaticData}
          rayInsightsData={rayInsightsData}
          inputTime={dataSummary?.input_time}
        />
      )}
      {isMobile && (
        <InsightCardButton insightsStaticData={insightsStaticData} analytics={analytics} />
      )}
    </Box>
  );
};

const cardProps = {
  width: {
    base: '100%',
    s: '100%',
    m: 'calc((100% - 16px) / 2)',
    l: 'calc((100% - 16px) / 2)',
    xl: 'calc((100% - 32px) / 3)',
  },
  borderRadius: 'large',
  elevation: 'none',
  backgroundColor: 'surface.background.gray.intense',
  minHeight: '100%', // to stretch the items
} as const;

const InsightCard = ({
  cardType,
  isLoading,
  isMobile,
  componentData,
  source,
  paymentLocked,
  analytics,
}: InsightCardProp) => {
  if (isLoading) {
    return (
      <Card {...cardProps} padding="spacing.0">
        <Skeleton height="272px" borderRadius="large" testID="onehome-payment-insight-skeleton" />
      </Card>
    );
  }
  return (
    <AnimateInteractions motionTriggers={['hover']}>
      <Elevate>
        <Card {...cardProps} padding="spacing.5">
          <ErrorBoundary
            key={cardType}
            rank={DASHBOARD_PRIORITY_RANKS.P0}
            team={DASHBOARD_TEAMS.R1_CONNECTED_EXPERIENCE}
            tags={{ module: 'One_Home_Insight_Card' }}
            FallbackComponent={() => (
              <ErrorState title={staticContent.errorText} withBorder={false} />
            )}
          >
            <InsightCardWithErrorBoundary
              cardType={cardType}
              isMobile={isMobile}
              componentData={componentData}
              source={source}
              analytics={analytics}
              paymentLocked={paymentLocked}
            />
          </ErrorBoundary>
        </Card>
      </Elevate>
    </AnimateInteractions>
  );
};

export default InsightCard;
