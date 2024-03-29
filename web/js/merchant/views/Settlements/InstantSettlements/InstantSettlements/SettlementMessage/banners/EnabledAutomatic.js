import React, { useState, useEffect } from 'react';
import styled from 'styled-components';

import ScheduledModal from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal';
import {
  getDiscountPercentage,
  getInstantPricingPercentage,
  isPricingRateValid,
} from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/utils';
import {
  DEFAULT_PRICING_RATE,
  SAMEDAY_MODAL_LOCATIONS,
} from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/constants';

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
import {
  trackEnableSamedayBannerRendered,
  trackExploreNowClicked,
} from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/analytics';

import INSTANT_LOGO from 'assets/settlements/instant.svg';

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

const Cta = styled.button`
  outline: none;
  border: 1px solid #2a86f3;
  background: #f8f9fb;
  border-radius: 2px;
  padding: 8px 16px;
  margin-left: 8px;
  font-weight: 600;
  font-size: 13px;
  line-height: 16px;
  color: #2a86f3;
`;

export default function EnableAutomatic({ openModal }) {
  const [pricingRate, setPricingRate] = useState(DEFAULT_PRICING_RATE);
  const isPricingValid = isPricingRateValid(pricingRate);

  useEffect(() => {
    getInstantPricingPercentage().then(({ data }) => {
      const pricingPercentage = data?.items?.[0]?.pricing_rule?.percent_rate;
      if (pricingPercentage) setPricingRate(pricingPercentage);
    });
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
        {isPricingValid && (
          <Wrapper>
            <Percentage>{getDiscountPercentage(pricingRate)}%</Percentage>
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
        <Cta onClick={handleClick}>Explore now</Cta>
      </Base>
    </Container>
  );
}
