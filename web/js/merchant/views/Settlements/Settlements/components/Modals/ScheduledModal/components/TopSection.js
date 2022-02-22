import React from 'react';
import PropTypes from 'prop-types';
import styled from 'styled-components';
import { connect } from 'react-redux';

import Popover, { PopoverBody } from 'common/ui/Popover';
import { closeModal as fnCloseModal } from 'merchant_common/reducers/modals';

import { SETTLEMENTS_TIMING } from '../constants';

const Container = styled.div`
  width: 100%;
  background: linear-gradient(
    138.21deg,
    rgba(0, 102, 222, 0.87) -11.78%,
    rgba(0, 45, 206, 0.87) 87.31%
  );
  border-radius: 3px 3px 0px 0px;
  position: relative;
  padding: 32px 24px 20px;
`;

const CloseButton = styled.button`
  position: absolute;
  top: 15.3px;
  right: 15.3px;
  outline: none;
  border: none;
  background: transparent;
  color: #f8f9fb;
  font-size: 15px;
  opacity: 0.6;
`;

const Emoji = styled.div`
  font-weight: 800;
  font-size: 32px;
  line-height: 32px;
  text-align: center;
  margin-bottom: 12px;
`;

const TopHeading = styled.h2`
  font-weight: 800;
  font-size: 20px;
  line-height: 30px;
  text-align: center;
  color: #ffffff;
  margin: 0 0 6px 0;
`;

const BadgeLabel = styled.div`
  width: 100%;
  background-color: rgb(42, 134, 243);
  border-radius: 4px;
  padding: 12px;
`;

const TopSubHeading = styled.p`
  font-size: 14px;
  line-height: 20px;
  text-align: center;
  color: #dfe3e9;
  & + .badge-label {
    margin-top: 8px;
  }
  span {
    font-weight: bold;
  }
`;

const SettlementsTimingContainer = styled.div`
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 16px;
`;

const SettlementTiming = styled.div`
  width: 162px;
  height: 62px;
  padding: 12px 10px;
  background: #2a86f3;
  opacity: 0.87;
  border-radius: 4px;
  display: flex;
  flex-direction: column;
`;

const SettlementTimingTop = styled.div`
  display: flex;
  align-items: center;
  margin-bottom: 6px;
  gap: 5px;
  > .i {
    color: #f2f4f8;
    font-size: 12px;
  }
`;

const SettlementTimingBottom = styled.div`
  display: flex;
  align-items: center;
  gap: 4px;
`;

const SettlementTimingLabel = styled.span`
  font-size: 12px;
  line-height: 16px;
  color: #f2f4f8;
`;

const SettlementTimingTime = styled.span`
  font-weight: bold;
  font-size: 13px;
  line-height: 16px;
  color: #ffffff;
  margin-bottom: 2px;
`;

const SettlementTimingInfo = styled.small`
  .i-info-outline {
    color: #f2f4f8;
  }
  .rzp-tooltip.theme-dark .rzp-tooltip-inner {
    background-color: #0a1d38 !important;
    padding: 14px !important;
  }
`;

function TopSection({
  emoji,
  heading,
  subHeading,
  badgeLabel,
  showTimings,
  children,
  onCloseClick,
  closeModal,
}) {
  return (
    <Container>
      <CloseButton data-testid="close-button" type="button" onClick={onCloseClick || closeModal}>
        <i class="i i-close" />
      </CloseButton>

      {emoji && <Emoji>{emoji}</Emoji>}
      <TopHeading>{heading}</TopHeading>
      <TopSubHeading>{subHeading}</TopSubHeading>
      {badgeLabel && <BadgeLabel className="badge-label">{badgeLabel}</BadgeLabel>}
      {showTimings && (
        <SettlementsTimingContainer>
          {SETTLEMENTS_TIMING.map((item, idx) => (
            <SettlementTiming key={idx}>
              <SettlementTimingTop>
                <i className={`i i-${item.icon}`} />
                <SettlementTimingTime>{item.time}</SettlementTimingTime>
              </SettlementTimingTop>

              <SettlementTimingBottom>
                <SettlementTimingLabel>{item.label}</SettlementTimingLabel>
                <SettlementTimingInfo className="small">
                  <i className="i i-info-outline" />
                  <Popover
                    align="bottom"
                    theme="dark"
                    parentQuerySelector=".enable-sameday-settlements-modal"
                  >
                    <PopoverBody>{item.info}</PopoverBody>
                  </Popover>
                </SettlementTimingInfo>
              </SettlementTimingBottom>
            </SettlementTiming>
          ))}
        </SettlementsTimingContainer>
      )}

      {children}
    </Container>
  );
}

TopSection.propTypes = {
  emoji: PropTypes.string,
  heading: PropTypes.string.isRequired,
  subHeading: PropTypes.oneOfType([PropTypes.string, PropTypes.node]),
  badgeLabel: PropTypes.node,
  showTimings: PropTypes.bool,
  closeModal: PropTypes.func.isRequired,
  onCloseClick: PropTypes.func,
  children: PropTypes.node,
};

export default connect(null, {
  closeModal: fnCloseModal,
})(TopSection);
