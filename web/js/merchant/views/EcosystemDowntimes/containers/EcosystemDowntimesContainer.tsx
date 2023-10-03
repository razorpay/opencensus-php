import React, { useRef, useState } from 'react';
import { EcosystemDowntimeProvider } from 'merchant/views/EcosystemDowntimes/context';
import { connect } from 'react-redux';
import { openSlider } from 'merchant_common/reducers/slider';
import { useClickOutSide } from 'common/utils/customHooks';
import Slider from 'common/ui/Slider';
import {
  EcosystemHealthHeading,
  EcosystemDowntimeContainer,
} from 'merchant/views/EcosystemDowntimes/styles';
import { classList } from 'common/utils/rzp-utils';
import { Heading } from '@razorpay/blade/components';

import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import {
  PAGE_TITLE,
  WAIT_TIME_FOR_NEXT_REFRESH,
  SR_QUERY_CACHE_KEY,
} from 'merchant/views/EcosystemDowntimes/constants';
import EcosystemRefreshNudge from 'merchant/views/EcosystemDowntimes/components/EcosystemRefreshNudge';
import { useQueryCache } from 'react-query';

// eslint-disable-next-line prettier/prettier
const MethodsContainer = lazy(
  () => import(/* webpackChunkName: "MethodsContainer" */ './MethodsContainer'),
);

const EcosystemDowntimesContainer = (props): JSX.Element => {
  const [isExpanded, setIsExpanded] = useState<boolean>(false);
  const { openSlider: sliderOpen } = props;
  const methodsContainerRef = useRef<HTMLDivElement | null>(null);
  const ecosystemHealthIcon = useRef<HTMLDivElement | null>(null);
  const queryCache = useQueryCache();

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
      queryCache.invalidateQueries(SR_QUERY_CACHE_KEY);
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
          <i
            className="i i-downtime"
            aria-label="status-detail-icon"
            onClick={handleToggleSlider}
            ref={ecosystemHealthIcon}
            role="img"
          />
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
                <Heading contrast="low" size="large" type="normal" variant="regular" weight="bold">
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
