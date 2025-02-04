import React, { useEffect } from 'react';
import { Carousel, CarouselItem, Box } from '@razorpay/blade/components';
import { Link } from 'react-router-dom';
import styled from 'styled-components';

import { ErrorState } from 'merchant/widgets/common/ErrorState';
import { CommonWidgetProps } from 'merchant/widgets/types';
import { getUcsAliasFromQueryKey, track } from 'merchant/widgets/utils';

import { CampaignHeroWidgetLoader } from './Loader';
import { CampaignHeroWidgetProps } from './types';
import { calcScaleFromWidth, calHeightOnAspectRatio } from './utils';
import { useRetryWidget } from '../hooks';

const DropShadowBox = styled.div`
  & > div {
    filter: drop-shadow(0px 2px 16px #1326441a);
  }
`;

export const CampaignHero = ({
  id,
  type,
  title,
  data,
  error,
  queryKey,
  isLoading,
}: CampaignHeroWidgetProps & CommonWidgetProps) => {
  const ref = React.useRef<HTMLDivElement>(null);
  const [isRetrying, retryHandler] = useRetryWidget(queryKey);
  const [isOnScreen, setIsOnScreen] = React.useState(false);
  const [width, setWidth] = React.useState(0);

  const screen = getUcsAliasFromQueryKey(queryKey) ?? '';
  const widgetId = `merchantDashboard.${screen}.${type}.${id}`;
  const campaigns = data?.campaign_hero_card_data?.assetData ?? [];
  const scale = calcScaleFromWidth(width);

  const _track = (index, objectName, actionName) => {
    const properties: any = {
      title,
      widgetId,
      actionBy: widgetId,
    };
    if (error) {
      properties.error = `${error.message}`;
    } else {
      properties.count = campaigns.length;
      properties.campaign = { position: index + 1, ...(campaigns?.[index]?.trackingData ?? {}) };
    }
    track({
      objectName,
      actionName,
      screen,
      properties,
    });
  };

  useEffect(() => {
    const container = ref.current;
    if (!container) return;

    const resizeObserver = new ResizeObserver((elements) => {
      // modify the width based on refresh rate of the browser, to optimise DOM modifications
      requestAnimationFrame(() => {
        setWidth(elements[0].contentRect.width);
      });
    });
    const screenObserver = new IntersectionObserver((elements) => {
      setIsOnScreen(elements[0].isIntersecting);
    });

    resizeObserver.observe(container);
    screenObserver.observe(container);

    // eslint-disable-next-line consistent-return
    return () => {
      resizeObserver.unobserve(container);
      screenObserver.unobserve(container);
    };
  }, [isLoading, isRetrying]);

  useEffect(() => {
    if ((!isLoading || !isRetrying) && (campaigns.length >= 1 || error)) {
      _track(0, 'widget', error ? 'error' : 'loaded');
    }
  }, [campaigns.length, error, isLoading, isRetrying]);

  if (!error && campaigns.length === 0 && (!isLoading || !isRetrying)) return null;

  return (
    <Box ref={ref} marginX={{ base: 'spacing.0', m: 'spacing.6' }} testID="campaign-hero-container">
      <DropShadowBox>
        {error ? (
          <ErrorState
            marginX={undefined}
            backgroundColor="surface.background.gray.intense"
            text={`${title} couldn't be loaded`}
            retryHandler={() => retryHandler({ id })}
            analyticsProperties={{
              screen,
              error: `${error.message}`,
              widgetId,
              actionBy: widgetId,
              title,
            }}
          />
        ) : isLoading ? (
          <CampaignHeroWidgetLoader width={width} scale={scale} />
        ) : (
          <Carousel
            visibleItems="autofit"
            navigationButtonPosition="side"
            autoPlay={isOnScreen}
            onChange={(idx) => _track(idx, 'widget', 'loaded')}
          >
            {campaigns.map(({ templates }, idx) => {
              const asset = templates[0].data;
              const template = asset.rtux_ucs_campaigns;
              const target = template.cta_link.indexOf('https://') === 0 ? '_blank' : '_self';
              return (
                <CarouselItem key={asset.id}>
                  <Box borderRadius="medium" overflow="hidden" testID="campaign-hero-card">
                    <Link
                      to={template.cta_link}
                      target={target}
                      onClick={() => _track(idx, 'link', 'clicked')}
                    >
                      <img
                        src={template.image[scale]}
                        alt={template.alt_text}
                        width="100%"
                        height={calHeightOnAspectRatio(width)}
                        style={{ objectFit: 'cover' }}
                        data-testid="campaign-hero-image"
                      />
                    </Link>
                  </Box>
                </CarouselItem>
              );
            })}
          </Carousel>
        )}
      </DropShadowBox>
    </Box>
  );
};
