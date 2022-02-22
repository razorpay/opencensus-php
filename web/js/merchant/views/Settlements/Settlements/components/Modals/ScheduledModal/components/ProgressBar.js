import React from 'react';
import moment from 'moment';
import styled from 'styled-components';

import { getEnableEsPartialAutomaticDate } from '../utils';
import { FULL_SHIFT_DAYS } from '../constants';

const Container = styled.div`
  width: 100%;
  padding: 0 5px;
  margin-top: 16px;
`;

const ProgressContainer = styled.div`
  width: 100%;
  display: flex;
  align-items: center;
`;

const DaysToGo = styled.div`
  width: 100%;
  position: relative;
  font-weight: bold;
  font-size: 14px;
  line-height: 20px;
  color: #008cb1;
  margin-bottom: 16px;
  text-align: center;
  &::before,
  &::after {
    content: '';
    display: inline-block;
    background-color: #dfe3e9;
    width: 99px;
    height: 1px;
    position: absolute;
    top: 50%;
    left: -10px;
    transform: translateY(-50%);
  }
  &::after {
    left: unset;
    right: -10px;
  }
`;

const Dot = styled.span`
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background-color: #dfe3e9;
  margin: 0 2px;
`;

const Progress = styled.span`
  height: 8px;
  background: #30c5d8;
  border-radius: 4px;
  padding: 0 1.5px;
  display: flex;
  align-items: center;
  .dot {
    background-color: #f8f9fb;
  }
`;

const DatesContainer = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 6px;
`;

const Date = styled.span`
  font-weight: 500;
  font-size: 12px;
  line-height: 16px;
  color: #8895a8;
  text-transform: uppercase;
`;

const Legends = styled.div`
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
`;

const Legend = styled.div`
  display: flex;
  align-items: center;
  padding: 2px 8px 11px;
  font-weight: bold;
  font-size: 12px;
  line-height: 16px;
  color: #008cb1;
  position: relative;
  img {
    width: 128px;
    position: absolute;
    top: 0;
    right: 0;
  }
`;

const EarlyAccess = styled(Legend)`
  gap: 4px;
  img {
    width: 98px;
    left: 0;
  }
`;

const Star = styled.div`
  font-size: 7px;
  color: #8895a8;
`;

export default function ProgressBar() {
  const enableDate = getEnableEsPartialAutomaticDate();

  const startDate = enableDate && moment(enableDate, 'DD/MM/YYYY');
  const finalDate = enableDate && moment(enableDate, 'DD/MM/YYYY').add(FULL_SHIFT_DAYS, 'days');
  const diff = enableDate && moment().diff(startDate, 'days');
  const progress = diff === 0 ? 1 : diff;
  const nonActive = FULL_SHIFT_DAYS - progress;

  if (progress > FULL_SHIFT_DAYS || !enableDate) return null;

  return (
    <Container>
      <DaysToGo>{nonActive} days to go...</DaysToGo>
      <Legends>
        <EarlyAccess>
          <img src="/dist/css/assets/settlements/left-message.svg" alt="left" />
          <i className="i i-unlock" />
          <span> Early Access</span>
        </EarlyAccess>
        <Legend>
          <img src="/dist/css/assets/settlements/right-message.svg" alt="right" />
          <span>🚀 </span>
          <span>100% Settlements</span>
        </Legend>
      </Legends>
      <ProgressContainer>
        <Progress>
          {new Array(progress).fill(null).map((_, idx) => (
            <Dot className="dot" key={idx} />
          ))}
        </Progress>
        {new Array(nonActive).fill(null).map((_, idx) => (
          <Dot key={idx} />
        ))}
        <Star>
          <i className="i i-star" />
        </Star>
      </ProgressContainer>
      <DatesContainer>
        <Date>{startDate.format('DD MMM YYYY')}</Date>
        <Date>{finalDate.format('DD MMM YYYY')}</Date>
      </DatesContainer>
    </Container>
  );
}
