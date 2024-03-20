import React, { useEffect, useRef } from 'react';
import { Box } from '@razorpay/blade/components';
import IntroductionPosterMobile from 'assets/partner-dashboard/partner-playbook/introduction-poster-mobile.svg';
import IntroductionPoster from 'assets/partner-dashboard/partner-playbook/introduction-poster.svg';
import styled from 'styled-components';

import { UseFormikReturnType } from 'common/typings';
import { isMobileResolution } from 'common/utils/rzp-utils';
import { useIntersectionObserver } from 'merchant/hooks/useIntersectionObserver';
import { debouncedTrackPageSectionReadSuccess } from 'merchant/views/PartnerDashboard/PartnerPlaybook/analytics';
import { introVideoItem } from 'merchant/views/PartnerDashboard/PartnerPlaybook/data';
import { ProgramItem } from 'merchant/views/PartnerDashboard/PartnerPlaybook/types';

import SearchBar from './SearchBar';

const IntroClickOverlay = styled.div`
  position: absolute;
  top: 17%;
  left: 61%;
  width: 22.3%;
  height: 49.5%;
  cursor: pointer;
`;

const IntroClickOverlayMobile = styled.div`
  position: absolute;
  top: 69%;
  left: 34%;
  width: 32%;
  height: 12%;
  cursor: pointer;
`;

type PosterAndSearchBarProps = {
  formik: UseFormikReturnType;
  openPreview: (item: ProgramItem) => void;
};
const PosterAndSearchBar = ({ formik, openPreview }: PosterAndSearchBarProps): JSX.Element => {
  const isMobile = isMobileResolution();

  const sectionRef = useRef(null);
  const isOnScreen = useIntersectionObserver(sectionRef);
  const onWatchIntroClick = () => {
    openPreview(introVideoItem);
  };
  useEffect(() => {
    if (isOnScreen) {
      debouncedTrackPageSectionReadSuccess({
        section: 'Introducing Partner Playbook',
        pageFold: 1,
      });
    }
  }, [isOnScreen]);

  return (
    <Box ref={sectionRef} display="block">
      <Box position="relative">
        <img
          width="100%"
          src={isMobile ? IntroductionPosterMobile : IntroductionPoster}
          alt="Poster"
        />
        {isMobile ? (
          <IntroClickOverlayMobile
            data-testid="playbook-intro-overlay"
            onClick={onWatchIntroClick}
          />
        ) : (
          <IntroClickOverlay data-testid="playbook-intro-overlay" onClick={onWatchIntroClick} />
        )}
      </Box>
      <Box zIndex={1} marginLeft="spacing.8" marginRight="spacing.11" position="relative">
        <Box position="relative" height="spacing.0" top="-32px">
          <SearchBar formik={formik} />
        </Box>
      </Box>
    </Box>
  );
};

export default PosterAndSearchBar;
