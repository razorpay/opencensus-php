import React, { SyntheticEvent, useEffect, useState } from 'react';
import { Badge, Box, Text, ChevronDownIcon, ChevronUpIcon, Link } from '@razorpay/blade/components';
import styled from 'styled-components';

import ProgramFolderIcon from 'assets/partner-dashboard/partner-playbook/program-folder.svg';
import { isMobileResolution } from 'common/utils/rzp-utils';
import { trackPageSectionCtaClicked } from 'merchant/views/PartnerDashboard/PartnerPlaybook/analytics';
import ProgramItemsTable from 'merchant/views/PartnerDashboard/PartnerPlaybook/components/PlaybookSections/ProgramItemsTable';
import {
  ProgramFolder,
  ProgramItem,
  ProgramSection,
} from 'merchant/views/PartnerDashboard/PartnerPlaybook/types';

const StyledProgramHeader = styled.div`
  cursor: pointer;
`;
type ProgramHeaderProps = {
  header: NonNullable<ProgramFolder['header']>;
  onFolderHeaderClick: () => void;
  onViewAllClick: (e: SyntheticEvent) => void;
  isExpanded: boolean;
  folderIndex: number;
  hash: string;
};
const ProgramHeader = ({
  onFolderHeaderClick,
  onViewAllClick,
  isExpanded,
  folderIndex,
  hash,
  header: { title, description, count },
}: ProgramHeaderProps): JSX.Element => {
  const isMobile = isMobileResolution();

  return (
    <StyledProgramHeader onClick={onFolderHeaderClick}>
      <Box
        display="flex"
        flexDirection="row"
        gap="spacing.7"
        padding="spacing.5"
        alignItems="center"
        height="108px"
        backgroundColor="surface.background.level2.lowContrast"
      >
        <Box flexBasis="8%">
          <img width="80px" src={ProgramFolderIcon} alt="folder" />
        </Box>
        <Box flexBasis="18%">
          <Text weight="bold">{title}</Text>
        </Box>

        {!isMobile ? (
          <Box flexBasis="42%">
            <Text>{description}</Text>
          </Box>
        ) : null}
        <Box flexBasis="10%">
          <Badge testID={`badge-${hash}-${folderIndex}`} variant="blue" size="large">
            {count} {count === 1 ? 'item' : 'items'}
          </Badge>
        </Box>

        <Box flexBasis="8%">
          <Link variant="button" onClick={onViewAllClick} marginBottom="spacing.2">
            {/* eslint-disable-next-line */}
            {/* @ts-ignore TS2322 Link only accepts string children */}
            <Box display="flex" flexDirection="row" alignItems="center">
              <Box> View all</Box>
              {isExpanded ? (
                <ChevronUpIcon
                  marginLeft="spacing.1"
                  size="large"
                  color="badge.icon.blue.lowContrast"
                />
              ) : (
                <ChevronDownIcon
                  marginLeft="spacing.1"
                  size="large"
                  color="badge.icon.blue.lowContrast"
                />
              )}
            </Box>
          </Link>
        </Box>
      </Box>
    </StyledProgramHeader>
  );
};
type ProgramFoldersProps = Pick<ProgramSection, 'folders'> & {
  isSearchQueryPresent: boolean;
  sectionHeader: ProgramSection['header'];
  openPreview: (item: ProgramItem) => void;
};
const ProgramFolders = ({
  folders,
  isSearchQueryPresent,
  sectionHeader,
  openPreview,
}: ProgramFoldersProps): JSX.Element => {
  const [isExpandedState, setIsExpanded] = useState({});

  // Compute the 'after search' expanded state
  useEffect(() => {
    const expandedState = {};
    folders.forEach((_, folderIndex) => {
      expandedState[folderIndex] = isSearchQueryPresent;
    });
    setIsExpanded(expandedState);
  }, [isSearchQueryPresent, folders]);

  const toggleExpandedState = (folderIndex, header, ctaClicked) => {
    const isExpanded = isExpandedState[folderIndex];
    setIsExpanded((state) => ({ ...state, [folderIndex]: !isExpanded }));
    trackPageSectionCtaClicked({
      section: sectionHeader.title,
      folderName: header.title,
      pageFold: sectionHeader.pageFold,
      folderDescription: header.description,
      title: null,
      description: null,
      ctaClicked,
    });
  };

  return (
    <Box display="flex" flexDirection="column" gap="spacing.3">
      {folders.map(({ header, items }, folderIndex) => (
        <Box key={folderIndex} backgroundColor="surface.background.level2.lowContrast">
          {header ? (
            <ProgramHeader
              isExpanded={isExpandedState[folderIndex]}
              onViewAllClick={(e) => {
                e.stopPropagation();
                toggleExpandedState(folderIndex, header, 'View all');
              }}
              onFolderHeaderClick={() => toggleExpandedState(folderIndex, header, 'Folder header')}
              header={header}
              hash={sectionHeader.hash}
              folderIndex={folderIndex}
            />
          ) : null}

          {isExpandedState[folderIndex] || !header ? (
            <Box padding={header ? 'spacing.5' : 'spacing.0'}>
              <ProgramItemsTable
                openPreview={openPreview}
                sectionHeader={sectionHeader}
                header={header}
                items={items}
              />
            </Box>
          ) : null}
        </Box>
      ))}
    </Box>
  );
};

export default ProgramFolders;
