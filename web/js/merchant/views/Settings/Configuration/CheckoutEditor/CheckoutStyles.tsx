import React, { memo, useMemo } from 'react';

import { STATUS } from 'merchant/views/Account/TrustedBadge/constants/data';

import BrandColor from './CheckoutStyling/BrandColor';
import ButtonStyle from './CheckoutStyling/ButtonStyle/ButttonStyle';
import FontStyle from './CheckoutStyling/FontStyle/FontStyle';
import SidebarGraphic from './CheckoutStyling/SidebarGraphic/SidebarGraphic';
import TitleStyle from './CheckoutStyling/TitleStyle/TitleStyle';
import RazorpayTrustesBadge from './CheckoutStyling/TrustedBadge/RazorpayTrustesBadge';
import { TrustedBadgeType } from './context/types';

interface CheckoutStylesProps {
  trustedBadge: TrustedBadgeType;
}
const CheckoutStyles: React.FC<CheckoutStylesProps> = memo(({ trustedBadge }) => {
  const badgeStatus = useMemo(() => trustedBadge?.status?.badgeStatus, [trustedBadge]);
  return (
    <>
      <BrandColor />
      {badgeStatus !== STATUS.NOT_ELIGIBLE_YES_WAITLISTED_DELISTED && <RazorpayTrustesBadge />}
      <TitleStyle />
      <ButtonStyle />
      <FontStyle />
      <SidebarGraphic />
      {badgeStatus === STATUS.NOT_ELIGIBLE_YES_WAITLISTED_DELISTED && <RazorpayTrustesBadge />}
    </>
  );
});

export default CheckoutStyles;
