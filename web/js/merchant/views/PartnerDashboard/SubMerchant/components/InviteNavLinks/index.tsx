import React from 'react';
import { Box, Divider } from '@razorpay/blade/components';
import { NavLink } from 'react-router-dom';
import styled from 'styled-components';

import { PRODUCT_ROUTE_PREFIX, PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

import FtuxTooltip from './components/FtuxTooltip';

const StyledInviteNavLinks = styled.div(
  ({ theme }) => `
  background: ${theme.colors.surface.background.level3.lowContrast};
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

type InviteNavLinksProps = {
  productType: string;
  onAcceptedInvitesClick: (productType: string) => void;
  onAllInvitesClick: (productType: string) => void;
};
const InviteNavLinks = ({
  productType,
  onAcceptedInvitesClick,
  onAllInvitesClick,
}: InviteNavLinksProps): JSX.Element => {
  const prefix = PRODUCT_ROUTE_PREFIX[productType];
  return (
    <StyledInviteNavLinks>
      <Box paddingTop="spacing.6" paddingLeft="spacing.6" paddingRight="spacing.6">
        {productType === PRODUCT_TYPE.PG ? <FtuxTooltip /> : null}
        <NavLink
          end
          onClick={() => onAcceptedInvitesClick(productType)}
          className="navlink"
          to={`${prefix}`}
        >
          Accepted Invites
        </NavLink>
        <NavLink
          end
          onClick={() => onAllInvitesClick(productType)}
          className="navlink"
          to={`${prefix}/all`}
        >
          All Invites
        </NavLink>
        <Box paddingTop="spacing.4">
          <Divider variant="subtle" />
        </Box>
      </Box>
    </StyledInviteNavLinks>
  );
};

export default InviteNavLinks;
