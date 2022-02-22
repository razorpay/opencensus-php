import React from 'react';
import styled from 'styled-components';

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
        <Discount>
          <Percentage color={color}>-50%</Percentage>
          <Label color={color}>Discount on your Instant Settlements fees</Label>
        </Discount>
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
