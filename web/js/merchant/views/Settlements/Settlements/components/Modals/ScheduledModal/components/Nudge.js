import React from 'react';
import styled, { keyframes } from 'styled-components';

import { AsyncBtn } from 'common/new-ui/Button';
import ScheduledModal from '..';
import { POST_ENABLE_TYPES } from '../constants';
import { getEsNudgeSeen, getNoOfDaysAfterEsPartialEnable } from '../utils';

const WobbleHorizontal = keyframes`
	16.65% {
		transform: translateX(8px);
	}
	33.3% {
		transform: translateX(-6px);
	}
	49.95% {
		transform: translateX(4px);
	}
	66.6% {
		transform: translateX(-2px);
	}
	83.25% {
		transform: translateX(1px);
	}
	100% {
		transform: translateX(0);
	}
`;

const Container = styled.div`
  width: 100%;
  padding: 8px 13px;
  border-radius: 3px;
  background: rgba(0, 140, 177, 0.09);
  border: 1px solid rgba(0, 140, 177, 0.32);
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  .i {
    color: ${({ color }) => color ?? '#008cb1'};
    font-size: 12px;
  }
  .i-info-outline {
    font-size: 14px;
  }
`;

const WobbleHorizontalContainer = styled(Container)`
  animation: ${WobbleHorizontal} 1s ease-in-out;
`;

const PartialBenefitContainer = styled(Container)`
  width: unset;
  margin: -8px 24px 20px;
  align-items: flex-start;
  .i {
    margin-top: 3px;
  }
`;

const FullContainer = styled(Container)`
  background: rgba(0, 156, 92, 0.09);
  border: 1px solid rgba(0, 156, 92, 0.32);
`;

const Label = styled.div`
  font-size: ${({ color }) => (color ? 12 : 14)}px;
  line-height: 16px;
  color: ${({ color }) => color ?? '#008cb1'};
  margin: 0 5px 0 8.5px;
`;

const LearnMore = styled(AsyncBtn.Transparent)`
  font-size: ${({ color }) => (color ? 12 : 14)}px !important;
  text-decoration: underline;
  line-height: 16px !important;
  color: ${({ color }) => color ?? '#008cb1'} !important;
  margin: 0 !important;
`;

const PartialLearnMore = styled(LearnMore)`
  margin-left: 8.5px !important;
`;

const PartialBenefitLearnMore = styled(LearnMore)`
  margin-left: 5px !important;
`;

const PartialBenefit = ({ modalType, openModal }) => {
  const handleLearnMoreClick = () => {
    openModal({
      component: <ScheduledModal enabled postModalType={modalType} />,
      size: 'small',
      disableClose: true,
    });
  };

  return (
    <PartialBenefitContainer>
      <i className="i i-info-outline" />
      <Label>
        You are enjoying early access to Intant Settlements.
        <PartialBenefitLearnMore onClick={handleLearnMoreClick}>Learn More</PartialBenefitLearnMore>
      </Label>
    </PartialBenefitContainer>
  );
};

const Partial = ({ modalType, openModal }) => {
  const handleLearnMoreClick = () => {
    openModal({
      component: <ScheduledModal enabled postModalType={modalType} />,
      size: 'small',
      disableClose: true,
    });
  };

  return (
    <WobbleHorizontalContainer onClick={handleLearnMoreClick}>
      <i className="i i-info-outline" />
      <PartialLearnMore onClick={handleLearnMoreClick}>
        Why can't I settle more money?
      </PartialLearnMore>
    </WobbleHorizontalContainer>
  );
};

const Full = ({ modalType, openModal }) => {
  const color = '#008659';
  const handleLearnMoreClick = () => {
    openModal({
      component: <ScheduledModal enabled postModalType={modalType} />,
      size: 'small',
      disableClose: true,
    });
  };

  return (
    <FullContainer color={color}>
      <i className="i i-check-circle-outline" />
      <Label color={color}>You can now settle your full balance.</Label>
      <LearnMore color={color} onClick={handleLearnMoreClick}>
        Learn More
      </LearnMore>
    </FullContainer>
  );
};

export default function Nudge({ user, amount, settlableAmount, closeOrigin, openModal }) {
  const isOndemandSettlementsRestricted = user.isOndemandSettlementsRestricted;
  const isOndemandSettlementEnabled = user.isOndemandSettlementEnabled;
  const isFullOndemandSettlementEnabled =
    isOndemandSettlementEnabled && !isOndemandSettlementsRestricted;
  const isPartialOndemandSettlementEnabled =
    isOndemandSettlementEnabled && isOndemandSettlementsRestricted;

  const isInputAmountGreater = amount > settlableAmount / 100;
  const modalType = user.isAutomaticSettlementEnabled
    ? POST_ENABLE_TYPES.SAMEDAY_FULL_SUCCESS_SHIFT
    : POST_ENABLE_TYPES.FULL_SUCCESS_SHIFT_WITHOUT_SAMEDAY;

  const diff = getNoOfDaysAfterEsPartialEnable();
  const partialModalType = diff
    ? diff <= 30
      ? POST_ENABLE_TYPES.SAMEDAY_FULL_SHIFT_PROGRESS
      : POST_ENABLE_TYPES.SAMEDAY_FULL_FAILURE
    : POST_ENABLE_TYPES.SAMEDAY_FULL_SHIFT_PROGRESS;

  if (isFullOndemandSettlementEnabled && !closeOrigin && !getEsNudgeSeen('FULL-SUCCESS'))
    return <Full openModal={openModal} modalType={modalType} />;

  if (isPartialOndemandSettlementEnabled && closeOrigin === 'OnDemand')
    return <PartialBenefit openModal={openModal} modalType={partialModalType} />;

  if (isPartialOndemandSettlementEnabled && isInputAmountGreater)
    return <Partial openModal={openModal} modalType={partialModalType} />;

  return null;
}
