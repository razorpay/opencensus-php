import { useStore } from '@apps/shell/src/client/store/commonStore';
import { merchantFetch } from '@dashboards/payments/utils/merchantFetch';
import { DASHBOARD_TEAMS } from '@libs/shared-types';
import {
  analyticsTrackWithUserInfo,
  initRazorAnalytics,
  isExperimentEnabled,
  isMobileDevice,
} from '@libs/shared-utils';
import { useSplitzService } from '@libs/web-nexus/common/splitz';
import { Box, ConfettiIcon, Heading, Text, Link } from '@razorpay/blade/components';
import React, { useEffect, useMemo, useState } from 'react';
import styled from 'styled-components';
import PlotlineRewardsPopup from '../PlotlineRewardsPopup';
import PlotlineInfoCard from '../PlotlineCard';
import { getRewardCount, getDaysSinceActivation, getValidityExpiryDate } from './utils';

const DAYS_SINCE_ACTIVATION_LIMIT = 30;
const NUMBER_OF_TRANSACTIONS_LIMIT = 6;
const PLOTLINE_SDK_FRONTEND_PUBLIC_KEY = process.env['PLOTLINE_SDK_FRONTEND_PUBLIC_KEY'];

const ButtonNoStyles = styled.button`
  background: transparent;
  border: none;
  padding: 0;
  margin: 0;
  font: inherit;
  color: inherit;
  outline: none;
  cursor: pointer;
  appearance: none; /* Remove default browser styling */
  -webkit-appearance: none; /* Safari and Chrome */
  -moz-appearance: none; /* Firefox */
`;

const PlotlineMilestoneWidget = () => {
  const splitz = useSplitzService();
  const { user, mode } = useStore((state) => state.session);
  const doesUserHaveMerchant = user?.merchant;
  const merchant = user?.merchant as {
    hold_funds: boolean;
    max_payment_amount: number;
    currency: string;
    country_code: string;
    id: string;
    activated_at?: number;
  };
  const isAccountOnTestMode = mode === 'test';

  const [isTransactionCountLoading, setIsTransactionCountLoading] = useState(false);
  const [transactionCount, setTransactionCount] = useState<number | null>(null);
  const [transactionCountApiError, setTransactionCountApiError] = useState(false);
  const [isRewardsModalOpen, setIsRewardsModalOpen] = useState<boolean>(false);

  const isExperimentActive = isExperimentEnabled(
    splitz?.abExperiments?.['plotline_milestone_widget'],
  );
  const isMIDWhitelisted = isExperimentEnabled(
    splitz?.abExperiments?.['plotline_milestone_whitelisted_mids'],
  );
  const merchantActivatedAt = merchant?.activated_at;

  const expiryDate = getValidityExpiryDate(DAYS_SINCE_ACTIVATION_LIMIT, merchantActivatedAt);
  const daysSinceActivation = getDaysSinceActivation(merchantActivatedAt);
  const daysRemainingSinceActivation = Math.max(
    0,
    DAYS_SINCE_ACTIVATION_LIMIT - daysSinceActivation,
  );
  const isTransactionCountInLimit =
    transactionCount !== null && transactionCount < NUMBER_OF_TRANSACTIONS_LIMIT;

  const rewardCount = getRewardCount(transactionCount);

  const { shouldShowWidget, showRewardsBanner } = useMemo(() => {
    if (
      isTransactionCountLoading ||
      transactionCountApiError ||
      transactionCount === null ||
      !doesUserHaveMerchant ||
      isAccountOnTestMode
    )
      return {
        shouldShowWidget: false,
        showRewardsBanner: false,
      };

    if (isMIDWhitelisted) {
      if (daysSinceActivation <= DAYS_SINCE_ACTIVATION_LIMIT)
        return {
          shouldShowWidget: true,
          showRewardsBanner: false,
        };
      else {
        if (transactionCount > 0) {
          return {
            shouldShowWidget: false,
            showRewardsBanner: true,
          };
        }
      }
    }

    if (
      isExperimentActive &&
      daysSinceActivation <= DAYS_SINCE_ACTIVATION_LIMIT &&
      isTransactionCountInLimit
    )
      return {
        shouldShowWidget: true,
        showRewardsBanner: false,
      };

    return {
      shouldShowWidget: false,
      showRewardsBanner: false,
    };
  }, [
    isTransactionCountLoading,
    transactionCountApiError,
    transactionCount,
    doesUserHaveMerchant,
    isAccountOnTestMode,
    isMIDWhitelisted,
    daysSinceActivation,
    isExperimentActive,
    isTransactionCountInLimit,
  ]);

  const getTransactionCount = async () => {
    try {
      setIsTransactionCountLoading(true);
      const url = `payments?count=${NUMBER_OF_TRANSACTIONS_LIMIT}`;
      const {
        data: { count },
      } = await merchantFetch<{ data: { count: number } }>({ url });
      setTransactionCount(count);
    } catch (error) {
      setTransactionCount(0);
      setTransactionCountApiError(true);
    } finally {
      setIsTransactionCountLoading(false);
    }
  };

  const getSectionTitle = (): JSX.Element | null => {
    if (!shouldShowWidget) return null;
    if (transactionCount === null) return null;
    if (transactionCount && isTransactionCountLoading && transactionCountApiError) return null;

    let titleDetails: {
      heading: string;
      subheading: string;
      ctaText?: string;
      showCta?: boolean;
    } | null = null;

    if (transactionCount === 0)
      titleDetails = {
        heading: 'Start now—your first reward is just one payment away',
        subheading: `Accept payments now to win big rewards. ${daysRemainingSinceActivation} days left.`,
      };

    if (transactionCount > 0 && transactionCount < 5)
      titleDetails = {
        heading: 'Get more payments, unlock more rewards!',
        subheading: `Each payment gets you closer to your next reward. ${daysRemainingSinceActivation} days left.`,
      };

    if (transactionCount >= 5)
      titleDetails = {
        heading: 'Welcome back to Razorpay',
        subheading: 'Explore your transactions, settlements and more.',
      };

    if (titleDetails)
      return (
        <Box
          textAlign="center"
          marginTop="spacing.5"
          marginBottom={{ m: 'spacing.2', base: 'spacing.7' }}
        >
          <Heading
            color="surface.text.gray.normal"
            size="xlarge"
            marginBottom="spacing.3"
            weight="semibold"
          >
            {titleDetails.heading}
          </Heading>
          <Box
            display="inline-flex"
            flexWrap="wrap"
            alignItems="center"
            justifyContent="center"
            gap="spacing.2"
          >
            {!isMobileDevice() && <ConfettiIcon />}
            <Text size="medium" color="surface.text.gray.subtle" weight="regular">
              {titleDetails.subheading}
              {titleDetails.showCta && titleDetails.ctaText && (
                <Link onClick={() => setIsRewardsModalOpen(true)}>{titleDetails.ctaText}</Link>
              )}
            </Text>
          </Box>
        </Box>
      );
    else return null;
  };

  const renderRewardsBanner = () => {
    if (transactionCount === null || transactionCount === 0) return null;

    const title =
      transactionCount >= 5
        ? 'You’ve won all the rewards. Check them out in the Rewards section.'
        : `You've won ${rewardCount} rewards. Check them out in the Rewards section.`;

    return (
      <PlotlineInfoCard
        icon={<ConfettiIcon />}
        title={title}
        showCta={showRewardsBanner}
        ctaText="View all your rewards"
        ctaOnClick={() => {
          if (typeof window.plotline === 'function') {
            window.plotline('init', PLOTLINE_SDK_FRONTEND_PUBLIC_KEY, merchant?.id);
            window.plotline('track', 'LAUNCH_REWARDS_PAGE');
          }
        }}
        backgroundAsset={{
          desktop: `${window.cdnBaseUrl}/static/assets/plotline/rewards_banner_desktop.png`,
          mobile: `${window.cdnBaseUrl}/static/assets/plotline/rewards_banner_mobile.png`,
        }}
      />
    );
  };

  useEffect(() => {
    getTransactionCount();
  }, []);

  useEffect(() => {
    if (shouldShowWidget && typeof window.plotline === 'function') {
      window.plotline('init', PLOTLINE_SDK_FRONTEND_PUBLIC_KEY, merchant?.id);

      analyticsTrackWithUserInfo({
        objectName: 'plotline widget',
        actionName: 'initialized',
        screen: 'home page',
      });

      initRazorAnalytics({ product: DASHBOARD_TEAMS.GROWTH, user });
    }

    return () => window.razorAnalytics?.disableTracking?.();
  }, [shouldShowWidget, merchant?.id]);

  return (
    <section id="plotline-milestone-widget-transaction-section">
      {showRewardsBanner && renderRewardsBanner()}

      {getSectionTitle()}

      {!showRewardsBanner && (
        <Box
          margin={{ m: ['spacing.6', 'spacing.6', 'spacing.0', 'spacing.6'], base: 'spacing.0' }}
          display="flex"
          justifyContent="center"
        >
          <ButtonNoStyles
            id="plotline-milestone-widget-transaction"
            data-plotline-widget="true"
            data-testid="plotline-milestone-widget"
            onClick={() => setIsRewardsModalOpen(true)}
          />
        </Box>
      )}

      <PlotlineRewardsPopup
        expiryDate={expiryDate}
        isOpen={isRewardsModalOpen}
        onDismiss={() => setIsRewardsModalOpen(false)}
        onPrimaryClick={() => {
          if (typeof window.plotline === 'function') {
            window.plotline('init', PLOTLINE_SDK_FRONTEND_PUBLIC_KEY, merchant?.id);
            window.plotline('track', 'LAUNCH_REWARDS_PAGE');
          }
        }}
        onSecondaryClick={() => {
          window.open('http://razorpay.com/milestone_rewards_terms', '_blank');
        }}
      />
    </section>
  );
};

export default PlotlineMilestoneWidget;
