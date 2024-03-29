import React, { useRef, useState } from 'react';
import { ActivityIcon, Heading } from '@razorpay/blade/components';
import { useQueryClient } from '@tanstack/react-query';
import { connect } from 'react-redux';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import Slider from 'common/ui/Slider';
import { useClickOutSide } from 'common/utils/customHooks';
import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import lazy from 'merchant/routes/LazyLoader';
import EcosystemRefreshNudge from 'merchant/views/EcosystemDowntimes/components/EcosystemRefreshNudge';
import {
  PAGE_TITLE,
  WAIT_TIME_FOR_NEXT_REFRESH,
  SR_QUERY_CACHE_KEY,
} from 'merchant/views/EcosystemDowntimes/constants';
import { EcosystemDowntimeProvider } from 'merchant/views/EcosystemDowntimes/context';
import {
  EcosystemHealthHeading,
  EcosystemDowntimeContainer,
} from 'merchant/views/EcosystemDowntimes/styles';
import { openSlider } from 'merchant_common/reducers/slider';
import { analyticsTrack } from 'common/utils/analytics';

// eslint-disable-next-line prettier/prettier
const MethodsContainer = lazy(
  () => import(/* webpackChunkName: "MethodsContainer" */ './MethodsContainer'),
);

const EcosystemDowntimesContainer = (props): JSX.Element => {
  const [isExpanded, setIsExpanded] = useState<boolean>(false);
  const { openSlider: sliderOpen, isRTUXHomepage } = props;
  const methodsContainerRef = useRef<HTMLDivElement | null>(null);
  const ecosystemHealthIcon = useRef<HTMLDivElement | null>(null);
  const queryCache = useQueryClient();

  const handleToggleSlider = () => {
    sliderOpen();
    setIsExpanded(!isExpanded);
  };

  const isDowntimeDetailsExits = () => {
    const ecosystemDowntimeDetail = document.querySelector('.ecosystem-downtime-details');
    return document.body.contains(ecosystemDowntimeDetail);
  };

  const onOutSideClick = () => {
    if (!isDowntimeDetailsExits()) {
      queryCache.invalidateQueries({ queryKey: [SR_QUERY_CACHE_KEY] });
      setIsExpanded(false);
    }
  };

  useClickOutSide([methodsContainerRef, ecosystemHealthIcon], onOutSideClick);

  return (
    <>
      <div
        aria-label="status-detail-icon-container"
        className={classList('status-details', isExpanded && 'status-details--active')}
      >
        <div className="status-details-slide-toggle">
          {isRTUXHomepage ? (
            <div
              onClick={() => {
                handleToggleSlider();
                analyticsTrack({
                  screen: 'home page',
                  objectName: 'Ecosystem Health Check Icon',
                  actionName: 'clicked',
                  properties: {
                    version: 'v2',
                    ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
                  },
                });
              }}
              ref={ecosystemHealthIcon}
            >
              <ActivityIcon size="medium" color="interactive.icon.gray.subtle" />
            </div>
          ) : (
            <i
              className="i i-downtime"
              aria-label="status-detail-icon"
              onClick={handleToggleSlider}
              ref={ecosystemHealthIcon}
              role="img"
            />
          )}
        </div>
      </div>
      {isExpanded ? (
        <Slider
          overlayCustomClass="ecosystem-downtime-overlay"
          checkIfOutsideClickDisabled={isDowntimeDetailsExits}
          onClose={onOutSideClick}
        >
          <EcosystemDowntimeContainer
            aria-label="ecosystem-health-container"
            ref={methodsContainerRef}
          >
            <EcosystemDowntimeProvider>
              <EcosystemHealthHeading>
                <Heading weight="semibold" size="medium" color="surface.text.gray.normal">
                  {PAGE_TITLE}
                </Heading>
                <EcosystemRefreshNudge waitInterval={WAIT_TIME_FOR_NEXT_REFRESH} />
              </EcosystemHealthHeading>
              <SuspenseWithLoader type="center">
                <MethodsContainer />
              </SuspenseWithLoader>
            </EcosystemDowntimeProvider>
          </EcosystemDowntimeContainer>
        </Slider>
      ) : null}
    </>
  );
};

export default connect(null, { openSlider })(EcosystemDowntimesContainer);
