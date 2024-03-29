import React from 'react';
import { Box, Divider, Text, Theme } from '@razorpay/blade/components';
import { useLocation, useNavigate } from 'react-router-dom';
import styled from 'styled-components';

import { trackTopHeadingsClicked } from 'merchant/views/PartnerDashboard/PartnerPlaybook/analytics';
import { ProgramSection } from 'merchant/views/PartnerDashboard/PartnerPlaybook/types';

const StyledNavLink = styled.div(
  ({ theme, $isActive }: { theme: Theme; $isActive: boolean }) => `
  cursor: pointer;
  padding: 26px 10px 9px;
  ${$isActive ? `border-bottom: 1.5px solid ${theme.colors.interactive.text.primary.subtle};` : ''}
`,
);

type PlaybookNavLinksProps = {
  sectionItems: Array<ProgramSection>;
};
const PlaybookNavLinks = ({ sectionItems }: PlaybookNavLinksProps): JSX.Element => {
  const location = useLocation();
  const navigate = useNavigate();

  const tabsData = sectionItems.map(({ header: { hash, title } }, sectionIndex) => ({
    hash,
    title,
    isActive: (!location.hash && sectionIndex == 0) || location.hash === `#${hash}`,
    onClick: () => {
      document.getElementById(hash)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      navigate({ hash });
      trackTopHeadingsClicked({ ctaClicked: title });
    },
  }));
  return (
    <Box>
      <Box marginTop="spacing.11">
        <Divider />
        <Box marginLeft="spacing.7" display="flex" flexDirection="row" gap="spacing.9">
          {tabsData.map(({ onClick, title, hash, isActive }) => (
            <StyledNavLink $isActive={isActive} onClick={onClick} key={hash}>
              <Text
                color={
                  isActive ? 'interactive.text.primary.subtle' : 'interactive.text.gray.normal'
                }
                weight="semibold"
                size="medium"
              >
                {title}
              </Text>
            </StyledNavLink>
          ))}
        </Box>
        <Divider />
      </Box>
    </Box>
  );
};
export default PlaybookNavLinks;
