import React, { useRef, useEffect, useMemo } from 'react';
import {
  Card,
  CardBody,
  Box,
  CardHeader,
  Text,
  CoinIcon,
  Amount,
  Button,
  PlusIcon,
  Heading,
  Skeleton,
  BoxRefType,
} from '@razorpay/blade/components';
import {
  BALANCE_CARD_SHIMMER_HEIGHT,
  BALANCE_CARD_SHIMMER_WIDTH,
  BALANCE_CARD_SHIMMER_MIN_WIDTH,
  messages,
} from '../constants';
import { BalanceCardHeaderProps, BalanceCardBodyProps, BalanceCardProps } from './types';
import generateBalanceCardConfig from './utils/generateBalanceCardConfig';

import useOneHomeAnalytics from '@apps/one-home/src/hooks/useOneHomeAnalytics';

/** status -  unmasked, masked
 * total balance -
 * current balance
 * settlement balance
 */

const balanceCardAnalyticsCommonProperties = {
  widgetId: messages.businessSummarySection.analytics.widgetId,
  title: messages.businessSummarySection.analytics.title,
  cardType: messages.balanceCard.analytics.cardType,
};

const maskedValue = '******';

const BalanceCardHeaderComponent: React.FC<BalanceCardHeaderProps> = ({
  type,
  value,
  currency,
  title,
}) => {
  return (
    <Box
      display="flex"
      flex={1}
      flexDirection="column"
      alignItems="center"
      justifyContent="center"
      gap="spacing.2"
    >
      <Box display="flex" gap="spacing.2" alignItems="center">
        <CoinIcon size="small" />
        <Text size="xsmall" color="surface.text.gray.muted" weight="semibold" textAlign="center">
          {title}
        </Text>
      </Box>

      {type == 'masked' ? (
        <Heading size="xlarge" color="surface.text.gray.normal" textAlign="center">
          {maskedValue}
        </Heading>
      ) : (
        <Amount
          type="heading"
          size="xlarge"
          weight="semibold"
          isAffixSubtle
          suffix="humanize"
          currencyIndicator="currency-symbol"
          value={value}
          currency={currency}
        />
      )}
    </Box>
  );
};

const BalanceCardBodyComponent: React.FC<BalanceCardBodyProps> = ({
  type,
  value,
  currency,
  title,
}) => {
  return (
    <Box display="flex" alignItems="center" flexDirection="column">
      <Box>
        <Text variant="body" color="surface.text.gray.muted" textAlign="center">
          {title}
        </Text>
      </Box>
      {type == 'masked' ? (
        <Text variant="body" size="medium" weight="semibold" textAlign="center">
          {maskedValue}
        </Text>
      ) : (
        <Amount
          size="medium"
          weight="semibold"
          isAffixSubtle
          suffix="humanize"
          currencyIndicator="currency-symbol"
          value={value}
          currency={currency}
        />
      )}
    </Box>
  );
};

const BalanceCard = React.forwardRef<BoxRefType, BalanceCardProps>(
  ({ isLoading, isMobile, data, dateFilter }, ref) => {
    const cardRef = useRef<BoxRefType | null>(null);
    const parentRef = useRef<BoxRefType | null>(null);
    const { trackOneHomeAnalytics } = useOneHomeAnalytics();

    if (data && 'error' in data) {
      throw data.error;
    }

    const balanceCardDatum = useMemo(() => {
      if (!data) return null;
      return generateBalanceCardConfig(data.data?.one_home_data.business_summary.balance);
    }, [data]);

    useEffect(() => {
      if (ref && typeof ref === 'object' && ref !== null) {
        if (!isLoading && balanceCardDatum) {
          ref.current =
            balanceCardDatum?.showButton && !isMobile ? cardRef.current : parentRef.current;
        } else {
          ref.current = null; // Ensure ref is reset when loading
        }
      }
    }, [isMobile, balanceCardDatum?.showButton, isLoading]);

    useEffect(() => {
      if (!isLoading && balanceCardDatum) {
        if (balanceCardDatum) {
          trackOneHomeAnalytics({
            objectName: 'Ucs widget',
            actionName: 'Loaded',
            properties: {
              ...balanceCardAnalyticsCommonProperties,
              subWidgetId: `${messages.balanceCard.analytics.subwidgetId}_${data?.id}`,
              dateRange: dateFilter,
              businessSummaryState: balanceCardDatum.type,
            },
          });
        }
      }
    }, [isLoading, balanceCardDatum, dateFilter]);

    if (isLoading || !balanceCardDatum) {
      return (
        <Skeleton
          testID="business-insights-balance-card-loader"
          height={BALANCE_CARD_SHIMMER_HEIGHT}
          borderRadius="large"
          width={{
            base: BALANCE_CARD_SHIMMER_WIDTH,
            m: BALANCE_CARD_SHIMMER_MIN_WIDTH,
          }}
        />
      );
    }

    const handleCreateAccountClick = () => {
      trackOneHomeAnalytics({
        objectName: 'Ucs Link',
        actionName: 'Clicked',
        properties: {
          ...balanceCardAnalyticsCommonProperties,
          subWidgetId: `${messages.balanceCard.analytics.subwidgetId}_${data?.id}`,
          dateRange: dateFilter,
          businessSummaryState: balanceCardDatum.type,
          buttonName: messages.balanceCard.currentAccountBtnText,
        },
      });

      window.open(
        'https://x.razorpay.com/auth/signup?utm_source=r1_dashboard&utm_content=home_business_summary',
        '_blank',
      );
    };

    const { totalBalance, currentAccount, settlementAccount, showButton } = balanceCardDatum;

    const shouldRenderTotalInBody = !currentAccount && !settlementAccount;

    return (
      <Box ref={parentRef} display="flex" flexDirection="column" gap="spacing.3">
        {showButton && (
          <Button
            variant="secondary"
            color="primary"
            icon={PlusIcon}
            iconPosition="left"
            onClick={handleCreateAccountClick}
          >
            {messages.balanceCard.currentAccountBtnText}
          </Button>
        )}
        <Box ref={cardRef}>
          <Card padding="spacing.5" elevation="none" borderRadius="large">
            {!shouldRenderTotalInBody && (
              <CardHeader>
                <BalanceCardHeaderComponent
                  type={totalBalance.type}
                  value={totalBalance.value}
                  currency={totalBalance.currency}
                  title={totalBalance.title}
                />
              </CardHeader>
            )}

            <CardBody>
              {shouldRenderTotalInBody && (
                <BalanceCardHeaderComponent
                  type={totalBalance.type}
                  value={totalBalance.value}
                  currency={totalBalance.currency}
                  title={totalBalance.title}
                />
              )}

              <Box
                display="flex"
                gap="spacing.5"
                marginTop="spacing.2"
                flexDirection={{
                  base: 'column',
                  xs: 'row',
                  s: 'row',
                  m: 'column',
                  l: 'row',
                  xl: 'row',
                }}
              >
                {/* current account balance */}
                {currentAccount ? (
                  <BalanceCardBodyComponent
                    type={currentAccount.type}
                    value={currentAccount.value}
                    currency={currentAccount.currency}
                    title={currentAccount.title}
                  />
                ) : null}

                {/** settlement account balance */}
                {settlementAccount ? (
                  <BalanceCardBodyComponent
                    type={settlementAccount.type}
                    value={settlementAccount.value}
                    currency={settlementAccount.currency}
                    title={settlementAccount.title}
                  />
                ) : null}
              </Box>
            </CardBody>
          </Card>
        </Box>
      </Box>
    );
  },
);

export default BalanceCard;
