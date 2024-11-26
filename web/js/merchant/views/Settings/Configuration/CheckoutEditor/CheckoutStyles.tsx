import React from 'react';
import { connect } from 'react-redux';

import BrandColor from './CheckoutStyling/BrandColor/BrandColor';
import ButtonStyle from './CheckoutStyling/ButtonStyle/ButtonStyle';
import FontStyle from './CheckoutStyling/FontStyle/FontStyle';
import SidebarGraphic from './CheckoutStyling/SidebarGraphic/SidebarGraphic';
import TitleStyle from './CheckoutStyling/TitleStyle/TitleStyle';
import RazorpayTrustesBadge from './CheckoutStyling/TrustedBadge/RazorpayTrustesBadge';
import { TrustedBadgeType } from './context/types';

interface CheckoutStylesProps {
  trustedBadge: TrustedBadgeType;
}
const CheckoutStyles: React.FC<CheckoutStylesProps> = ({ trustedBadge }: CheckoutStylesProps) => {
  const isRTBActive =
    trustedBadge.status.original.merchant_status !== 'optout' &&
    (trustedBadge.status.original.status === 'eligible' ||
      trustedBadge.status.original.status === 'whitelist');
  return (
    <>
      <BrandColor />
      {isRTBActive && <RazorpayTrustesBadge isActive={isRTBActive} />}
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
  };
})(CheckoutStyles);
