import React from 'react';
import { connect } from 'react-redux';

import BrandColor from './CheckoutStyling/BrandColor/BrandColor';
import ButtonStyle from './CheckoutStyling/ButtonStyle/ButtonStyle';
import FestivalTheme from './CheckoutStyling/FestivalTheme/FestivalTheme';
import FontStyle from './CheckoutStyling/FontStyle/FontStyle';
import SidebarGraphic from './CheckoutStyling/SidebarGraphic/SidebarGraphic';
import TitleStyle from './CheckoutStyling/TitleStyle/TitleStyle';
import RazorpayTrustesBadge from './CheckoutStyling/TrustedBadge/RazorpayTrustesBadge';
import { isRazorpayTrustedBadgeActive } from './CheckoutStyling/TrustedBadge/utils';
import { TrustedBadgeType } from './context/types';
import { User } from 'common/typings';

interface CheckoutStylesProps {
  trustedBadge: TrustedBadgeType;
  user: User;
}
const CheckoutStyles: React.FC<CheckoutStylesProps> = ({
  trustedBadge,
  user,
}: CheckoutStylesProps) => {
  const isRTBActive = isRazorpayTrustedBadgeActive(trustedBadge);
  return (
    <>
      <FestivalTheme />
      <BrandColor />
      {isRTBActive && user?.isCountryIndia && <RazorpayTrustesBadge isActive={isRTBActive} />}
      <TitleStyle />
      <ButtonStyle />
      <FontStyle />
      <SidebarGraphic />
      {!isRTBActive && <RazorpayTrustesBadge isActive={isRTBActive} />}
    </>
  );
};

export default connect((state) => {
  return {
    trustedBadge: state.trustedBadge,
    user: state.session.user,
  };
})(CheckoutStyles);
