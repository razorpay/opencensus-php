import React from 'react';
import INSTANT_LOGO from 'assets/settlements/instant.svg';
import { connect } from 'react-redux';
import styled from 'styled-components';

import { useODSAutomaticPricingDiscount } from 'merchant/views/Settlements/InstantSettlements/hooks/useODSAutomaticPricingDiscount';

import Base, {
  Container,
  LeftSideBorder,
  Amount,
  AmountBlock,
  AmountContainer,
  DiscountBlock,
  Label,
  Percentage,
  Rupee,
  Title,
} from './Base';
import { NEW_BANNERS } from './constants';
import { getIsOdsMigrationEnabled } from 'merchant/views/Settlements/InstantSettlements/utils/common';
const color = '#008659';

const StyledContainer = styled(Container)`
  background: rgba(0, 156, 92, 0.03);
  border: 1px solid rgba(0, 156, 92, 0.1);
`;

const Discount = styled(DiscountBlock)`
  border: 1px solid rgba(0, 156, 92, 0.32);
`;

const AmountBlockWrapper = styled(AmountBlock)`
  border: 1px solid rgba(0, 156, 92, 0.32);
`;

const RupeeWrapper = styled(Rupee)`
  color: rgba(0, 156, 92, 0.32);
`;

function FullShiftSuccess({ user, onDismiss }) {
  const isOdsExpEnabled = getIsOdsMigrationEnabled(user);
  const { discountPercent, canViewDiscount } = useODSAutomaticPricingDiscount(
    isOdsExpEnabled,
    user.merchant.currency || 'INR',
  );

  return (
    <StyledContainer>
      <Base
        image={INSTANT_LOGO}
        sideBorder={<LeftSideBorder color={color} />}
        title={
          <Title width={224}>
            Your full balance will now be settled the same day, automatically!
          </Title>
        }
        onDismiss={() => onDismiss(NEW_BANNERS.FULL_SHIFT_SUCCESS)}
      >
        <Discount>
          <Percentage color={color}>100%</Percentage>
          <Label color={color}>Balance can now be settled the same day</Label>
        </Discount>
        {canViewDiscount && (
          <Discount>
            <Percentage color={color}>-{discountPercent}%</Percentage>
            <Label color={color}>Discount on your Instant Settlements fees</Label>
          </Discount>
        )}
        <AmountBlockWrapper>
          <AmountContainer>
            <RupeeWrapper>₹</RupeeWrapper>
            <Amount color={color}>0</Amount>
          </AmountContainer>
          <Label color={color}>Annual Maintenance Fee</Label>
        </AmountBlockWrapper>
      </Base>
    </StyledContainer>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(FullShiftSuccess);
