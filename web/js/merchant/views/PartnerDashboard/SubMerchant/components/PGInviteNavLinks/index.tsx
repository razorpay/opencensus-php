import React from 'react';
import styled from 'styled-components';
import { NavLink } from 'react-router-dom';
import FtuxTooltip from './components/FtuxTooltip';
import { trackAcceptedInvitesClick, trackAllInvitesClick } from './analytics';

const StyledPGInvitesNavLinks = styled.div(
  ({ theme }) => `
  padding: 20px 24px 12px;
  background: ${theme.colors.surface.background.level2.lowContrast};
  position: relative;
  .navlink { 
    padding-right: ${theme.spacing[7]}px;
    color: ${theme.colors.surface.text.muted.lowContrast};
    :hover{
      color: ${theme.colors.brand.primary[500]};
    }
  }
  .active {
    font-weight: bold;
    color: ${theme.colors.brand.primary[500]};
  }
`,
);

type PGInvitesNavLinksProps = {
  prefix: string;
};

const PGInvitesNavLinks = ({ prefix }: PGInvitesNavLinksProps): JSX.Element => {
  return (
    <StyledPGInvitesNavLinks>
      <FtuxTooltip />
      <NavLink
        exact
        activeClassName="active"
        onClick={trackAcceptedInvitesClick}
        className="navlink"
        to={`${prefix}`}
      >
        Accepted Invites
      </NavLink>
      <NavLink
        exact
        activeClassName="active"
        onClick={trackAllInvitesClick}
        className="navlink"
        to={`${prefix}/all`}
      >
        All Invites
      </NavLink>
    </StyledPGInvitesNavLinks>
  );
};

export default PGInvitesNavLinks;
