import React from 'react';
import { Box, Divider } from '@razorpay/blade/components';
import { NavLink } from 'react-router-dom';
import styled from 'styled-components';

import { PRODUCT_ROUTE_PATH_PREFIX, PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

import FtuxTooltip from './components/FtuxTooltip';

const StyledInviteNavLinks = styled.div(
  ({ theme }) => `
  background: ${theme.colors.surface.background.gray.moderate};
  position: relative;
  .navlink { 
    padding-right: ${theme.spacing[7]}px;
    color: ${theme.colors.surface.text.gray.muted};
    :hover{
      color: ${theme.colors.surface.background.primary.intense};
    }
  }
  .active {
    font-weight: 600;
    color: ${theme.colors.surface.background.primary.intense};
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
  const productRoute = PRODUCT_ROUTE_PATH_PREFIX[productType];
  return (
    <StyledInviteNavLinks>
      <Box paddingTop="spacing.6" paddingLeft="spacing.6" paddingRight="spacing.6">
        {productType === PRODUCT_TYPE.PG ? <FtuxTooltip /> : null}
        <NavLink
          end
          onClick={() => onAcceptedInvitesClick(productType)}
          className="navlink"
          to={`/partners/submerchants${productRoute}`}
        >
          Accepted Invites
        </NavLink>
        <NavLink
          end
          onClick={() => onAllInvitesClick(productType)}
          className="navlink"
          to={`/partners/submerchants${productRoute}/all`}
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
