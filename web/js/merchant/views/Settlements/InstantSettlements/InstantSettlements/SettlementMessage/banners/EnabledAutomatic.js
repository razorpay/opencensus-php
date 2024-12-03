import React, { useEffect } from 'react';
import { Button } from '@razorpay/blade/components';
import INSTANT_LOGO from 'assets/settlements/instant.svg';
import { connect } from 'react-redux';
import styled from 'styled-components';

import { useODSAutomaticPricingDiscount } from 'merchant/views/Settlements/InstantSettlements/hooks/useODSAutomaticPricingDiscount';
import { useIsManagedMerchantAccount } from 'merchant/views/Settlements/InstantSettlements/hooks/useIsManagedMerchantAccount';
import ScheduledModal from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal';
import {
  trackEnableSamedayBannerRendered,
  trackExploreNowClicked,
} from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/analytics';
import { SAMEDAY_MODAL_LOCATIONS } from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/constants';

import Base, {
  Container,
  LeftSideBorder,
  Amount,
  AmountContainer,
  Label,
  Percentage,
  Rupee,
  Title,
} from './Base';

const Wrapper = styled.div`
  background: rgba(21, 102, 241, 0.09);
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 0 12px;
  width: 248px;
  height: 44px;
  border-radius: 4px;
  margin-right: 8px;
`;

function EnableAutomatic({ user, openModal }) {
  const { isLoading, discountPercent, canViewDiscount } = useODSAutomaticPricingDiscount(
    user.merchant.currency || 'INR',
  );
  const { isManagedMerchantAccount } = useIsManagedMerchantAccount();
  useEffect(() => {
    trackEnableSamedayBannerRendered();
  }, []);

  const handleClick = () => {
    openModal({
      component: <ScheduledModal from={SAMEDAY_MODAL_LOCATIONS.SETTLEMENTS_HOME} />,
      size: 'small',
      disableClose: true,
    });
    trackExploreNowClicked();
  };

  return (
    <Container>
      <Base
        image={INSTANT_LOGO}
        sideBorder={<LeftSideBorder />}
        title={<Title>Settle your funds the same day, automatically!</Title>}
      >
        {canViewDiscount && !isManagedMerchantAccount && (
          <Wrapper>
            <Percentage>-{discountPercent}%</Percentage>
            <Label>Discount on your Instant Settlements fees</Label>
          </Wrapper>
        )}
        <Wrapper>
          <AmountContainer>
            <Rupee>₹</Rupee>
            <Amount>0</Amount>
          </AmountContainer>
          <Label>Annual Maintenance & Set up Fee </Label>
        </Wrapper>
        <Button variant="secondary" isDisabled={isLoading} onClick={handleClick}>
          Explore now
        </Button>
      </Base>
    </Container>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(EnableAutomatic);
