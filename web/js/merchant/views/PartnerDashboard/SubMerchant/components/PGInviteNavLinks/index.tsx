import React from 'react';
import styled from 'styled-components';
import { NavLink } from 'react-router-dom';
import FtuxTooltip from './components/FtuxTooltip';

const StyledPGInvitesNavLinks = styled.div(
  ({ theme }) => `
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
      <NavLink exact activeClassName="active" className="navlink" to={`${prefix}`}>
        Accepted Invites
      </NavLink>
      <NavLink exact activeClassName="active" className="navlink" to={`${prefix}/all`}>
        All Invites
      </NavLink>
    </StyledPGInvitesNavLinks>
  );
};

export default PGInvitesNavLinks;
