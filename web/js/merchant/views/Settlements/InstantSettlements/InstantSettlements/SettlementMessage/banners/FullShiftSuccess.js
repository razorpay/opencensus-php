import React, { useEffect, useState } from 'react';
import styled from 'styled-components';
import {
  getDiscountPercentage,
  getInstantPricingPercentage,
  isPricingRateValid,
} from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/utils';
import { DEFAULT_PRICING_RATE } from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/constants';
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
import { resolvePath } from 'common/utils/rzp-utils';

const color = '#008659';
const INSTANT_LOGO = '/dist/css/assets/settlements/instant.svg';

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

export default function FullShiftSuccess({ onDismiss }) {
  const [pricingRate, setPricingRate] = useState(DEFAULT_PRICING_RATE);
  const isPricingValid = isPricingRateValid(pricingRate);

  useEffect(() => {
    getInstantPricingPercentage().then(({ data }) => {
      const pricingPercentage = resolvePath(
        data,
        'items[0].pricing_rule.percent_rate',
        DEFAULT_PRICING_RATE,
      );
      if (pricingPercentage) setPricingRate(pricingPercentage);
    });
  }, []);

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
        {isPricingValid && (
          <Discount>
            <Percentage color={color}>{getDiscountPercentage(pricingRate)}%</Percentage>
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
