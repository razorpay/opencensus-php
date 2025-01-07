import React from 'react';
import CLOCK_ICON from 'assets/settlements/clock.svg';
import styled from 'styled-components';

import { AsyncBtn } from 'common/new-ui/Button';
import ScheduledModal from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal';
import { POST_ENABLE_TYPES } from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/constants';

import Base, { Container, LeftSideBorder, Title } from './Base';
import { NEW_BANNERS } from './constants';

const color = '#008CB1';

const StyledContainer = styled(Container)`
  background: rgba(0, 140, 177, 0.03);
  border: 1px solid rgba(0, 140, 177, 0.1);
`;

const DescContainer = styled.div`
  display: flex;
  flex-direction: column;
  align-items: flex-start;
`;

const Desc = styled.p`
  font-size: 13px;
  line-height: 18px;
  color: #5d6d86;
`;

const Cta = styled(AsyncBtn.Transparent)`
  font-size: 13px;
`;

export default function FullShiftFailure({ openModal, onDismiss }) {
  const handleKnowMoreClick = () => {
    openModal({
      component: <ScheduledModal enabled postModalType={POST_ENABLE_TYPES.SAMEDAY_FULL_FAILURE} />,
      size: 'small',
      disableClose: true,
    });
  };

  return (
    <StyledContainer>
      <Base
        image={CLOCK_ICON}
        sideBorder={<LeftSideBorder color={color} />}
        title={<Title width={203}>Full benefits of Same-day Settlements are a few days away</Title>}
        onDismiss={() => onDismiss(NEW_BANNERS.FULL_SHIFT_FAILURE)}
      >
        <DescContainer>
          <Desc>
            It is taking us longer than expected to bring you the full benefits of Same-day
            Settlements
          </Desc>
          <Cta onClick={handleKnowMoreClick}>Know more</Cta>
        </DescContainer>
      </Base>
    </StyledContainer>
  );
}
