import React, { useEffect, useRef } from 'react';
import { Box, Spinner, Heading } from '@razorpay/blade/components';
import { useLocation } from 'react-router-dom';

import { isMobileResolution } from 'common/utils/rzp-utils';
import { useIntersectionObserver } from 'merchant/hooks/useIntersectionObserver';
import { debouncedTrackPageSectionReadSuccess } from 'merchant/views/PartnerDashboard/PartnerPlaybook/analytics';
import ProgramFolders from 'merchant/views/PartnerDashboard/PartnerPlaybook/components/PlaybookSections/ProgramFolders';
import SectionHeader from 'merchant/views/PartnerDashboard/PartnerPlaybook/components/PlaybookSections/SectionHeader';
import {
  PlaybookItems,
  ProgramItem,
  ProgramSection,
} from 'merchant/views/PartnerDashboard/PartnerPlaybook/types';
import { getDecodedParams } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/components/AllInvitesFilter';

import PlaybookNavLinks from './PlaybookNavLinks';
import { SpinnerContainer } from './styles';

type SectionItemProps = {
  isSearchQueryPresent: boolean;
  sectionItem: ProgramSection;
  openPreview: (item: ProgramItem) => void;
};
const SectionItem = ({
  isSearchQueryPresent,
  openPreview,
  sectionItem: { header, folders },
}: SectionItemProps) => {
  const sectionRef = useRef(null);
  const isOnScreen = useIntersectionObserver(sectionRef);
  useEffect(() => {
    if (isOnScreen) {
      debouncedTrackPageSectionReadSuccess({ section: header.title, pageFold: header.pageFold });
    }
  }, [isOnScreen, header.title, header.pageFold]);
  return (
    <Box ref={sectionRef} marginBottom="spacing.11">
      <Box marginBottom="spacing.6">
        <SectionHeader isSearchQueryPresent={isSearchQueryPresent} header={header} />
      </Box>
      <ProgramFolders
        sectionHeader={header}
        openPreview={openPreview}
        isSearchQueryPresent={isSearchQueryPresent}
        folders={folders}
      />
    </Box>
  );
};

type PlaybookSectionsProps = {
  isLoading: boolean;
  openPreview: (item: ProgramItem) => void;
  programItems: PlaybookItems | undefined | [];
};
const PlaybookSections = ({
  isLoading,
  programItems = [],
  openPreview,
}: PlaybookSectionsProps): JSX.Element => {
  const isMobile = isMobileResolution();
  const location = useLocation();
  const { query = '' } = getDecodedParams(location.search);

  const isSearchQueryPresent = query !== '';
  if (isLoading) {
    return (
      <SpinnerContainer>
        <Spinner testID="playbook-sections-spinner" accessibilityLabel="spinner" size="xlarge" />
      </SpinnerContainer>
    );
  }
  // Parse api data for visibility conditions
  let totalMatchCount = 0;
  let sectionItems = programItems.map(({ sectionItem }) => sectionItem);

  if (isSearchQueryPresent) {
    sectionItems = sectionItems.filter(({ header: { count } }) => {
      totalMatchCount += count;
      return count > 0;
    });
  }

  return (
    <Box>
      <Box
        marginTop="spacing.9"
        marginBottom="spacing.11"
        marginRight={isMobile ? 'spacing.5' : 'spacing.8'}
      >
        {isSearchQueryPresent ? (
          <Box marginTop="spacing.6" marginLeft="spacing.8" display="flex" justifyContent="middle">
            {totalMatchCount !== 0 ? (
              <Heading color="feedback.text.positive.intense" size="large">
                Found {totalMatchCount} {totalMatchCount == 1 ? 'result' : 'results'}
              </Heading>
            ) : null}
            {totalMatchCount === 0 ? (
              <Heading color="feedback.text.negative.intense" size="large">
                Oops! Couldn't find any results. Try searching something else.
              </Heading>
            ) : null}
          </Box>
        ) : null}
        {!isSearchQueryPresent ? <PlaybookNavLinks sectionItems={sectionItems} /> : null}
        {!isSearchQueryPresent || totalMatchCount !== 0 ? (
          <Box
            marginLeft="spacing.8"
            marginTop="spacing.8"
            marginRight={isMobile ? 'spacing.5' : 'spacing.8'}
          >
            {sectionItems.map((sectionItem, sectionIndex) => (
              <SectionItem
                key={sectionIndex}
                openPreview={openPreview}
                isSearchQueryPresent={isSearchQueryPresent}
                sectionItem={sectionItem}
              />
            ))}
          </Box>
        ) : null}
      </Box>
    </Box>
  );
};

export default PlaybookSections;
