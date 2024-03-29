import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import styled, { css, keyframes } from 'styled-components';

import User from 'merchant/models/User';
import { updateSession as fnUpdateSession } from 'merchant/reducers/session';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import { closeModal as fnCloseModal } from 'merchant_common/reducers/modals';

import TopSection from './components/TopSection';
import BottomSection from './components/BottomSection';
import Button from './components/Button';
import {
  SAMEDAY_TIMELINE_ICONS,
  PRE_ENABLE_VIEWS,
  getSamedayTimeline,
  SAMEDAY_MODAL_LOCATIONS,
} from './constants';
import {
  enableAutomaticSettlements,
  setEnableEsPartialAutomaticDate,
  getDiscountPercentage,
  isPricingRateValid,
} from './utils';
import {
  trackEnableModalCloseClick,
  trackEnableModalRendered,
  trackEnableNowClicked,
} from './analytics';

const IndicatorWrapper = styled.div`
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 12px;
`;

const IndicatorContainer = styled.div`
  display: flex;
  align-items: center;
  gap: 4px;
  position: relative;
`;

const IndicatorClickArea = styled.div`
  width: 41px;
  height: 17px;
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
`;

const IndicatorLeftClickArea = styled(IndicatorClickArea)`
  left: -10px;
`;

const IndicatorRightClickArea = styled(IndicatorClickArea)`
  right: -10px;
`;

const progress = keyframes`
  from {
    left: 0;
  }
  to {
    left: calc(100% - 20px)
  }
`;

const IndicatorLine = styled.span`
  width: 56px;
  height: 2px;
  background-color: #dfe3e9;
  overflow: hidden;
  position: relative;
  &::after {
    content: '';
    display: block;
    position: absolute;
    top: 0;
    left: ${({ reverse }) => (reverse ? ` calc(100% - 20px)` : 0)};
    width: 20px;
    height: 100%;
    background-color: #a3afbf;
    ${({ active, reverse }) =>
      active &&
      css`
        animation: ${progress} 4s linear infinite ${reverse ? 'reverse' : ''};
      `}
  }
`;

const IndicatorDot = styled.div`
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background-color: #dfe3e9;
  ${({ active }) => active && `background-color: #a3afbf;`}
`;

const SamedayWorksContainer = styled.div`
  display: flex;
  width: 100%;
  gap: 16px;
  margin: 22px 0 24px;
  padding: 0 4px;
`;

const TimelineContainer = styled.div`
  width: 20px;
  position: relative;
`;

const TimelineTop = styled.div`
  position: absolute;
  top: 0;
  left: 0;
  z-index: 10;
  width: 100%;
  height: 198px;
  background: linear-gradient(180deg, #3984fb 0%, #26b1ff 94.87%);
  border-radius: 6px;
  padding: 10px 0 8px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;
  > .i {
    color: #ffffff;
    font-size: 10px;
  }
`;

const TimelineBottom = styled.div`
  position: absolute;
  bottom: 10px;
  left: 0;
  width: 100%;
  height: 52px;
  background: linear-gradient(
    180deg,
    rgba(0, 213, 234, 0.87) 17.69%,
    rgba(255, 255, 255, 0.87) 94.33%
  );
  border-radius: 2px;
`;

const SamedayDetailContainer = styled.div`
  flex: 1;
  display: flex;
  flex-direction: column;
`;

const SamedayDetailItem = styled.div`
  display: flex;
  flex-direction: column;
  &:first-child {
    margin-top: 10px;
  }
  &:not(:last-child) {
    margin-bottom: 36px;
  }
`;

const SamedayDetailTop = styled.div`
  display: flex;
  align-items: center;
  gap: 4px;
`;

const SamedayDetailTitle = styled.h3`
  margin: 0;
  font-weight: 600;
  font-size: 14px;
  line-height: 20px;
  color: #324664;
`;

const SamedayDetailDate = styled.span`
  font-size: 14px;
  line-height: 20px;
  color: #5d6d86;
`;

const SamedayDetailInfo = styled.p`
  font-size: 13px;
  line-height: 18px;
  color: #5d6d86;
  margin-top: 1px;
`;

const InstantSettlementsBenefits = styled.div`
  width: 100%;
`;

const BigDiscount = styled.div`
  font-weight: 800;
  font-size: 80px;
  line-height: 120px;
  background: linear-gradient(#baf4f5, #30c5d8);
  background-clip: text;
  -webkit-background-clip: text; /* stylelint-disable-line property-no-vendor-prefix */
  -webkit-text-fill-color: transparent;
  text-align: center;
`;

const InstantSettlementsTitle = styled.p`
  font-size: 16px;
  line-height: 20px;
  text-align: center;
  color: #00779e;
  margin-bottom: 20px;
`;

const InstantSettlementsFeeContainer = styled.div`
  display: flex;
  align-items: center;
  padding: 15px 46px;
  background: #ebf6f9;
  border-radius: 8px;
  margin-bottom: 8px;
  > .i-arrow-forward {
    color: #324664;
    margin: 3px 20px 0;
  }
`;

const InstantSettlementsCurrentFeeContainer = styled.div`
  position: relative;
`;

const InstantSettlementsCurrentFee = styled.div`
  font-size: 16px;
  line-height: 22px;
  color: #5d6d86;
`;

const StrikeThrough = styled.span`
  width: 38px;
  height: 2px;
  background-color: #008cb1;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
`;

const InstantSettlementsOfferFee = styled.span`
  font-weight: 600;
  font-size: 16px;
  line-height: 22px;
  color: #008cb1;
`;

const InstantSettlementsOfferFeeLabel = styled.span`
  font-size: 14px;
  line-height: 22px;
  color: #00779e;
  opacity: 0.87;
  margin-left: 4px;
`;

const InstantSettlementsLabel = styled.p`
  font-size: 14px;
  line-height: 20px;
  text-align: center;
  color: #5d6d86;
  margin-bottom: 20px;
`;

const FeeInfo = styled.p`
  font-size: 13px;
  line-height: 20px;
  text-align: center;
  color: #324664;
  margin-bottom: 6px;
  span {
    font-weight: 600;
  }
`;

const OptOutAnytime = styled.p`
  font-size: 13px;
  line-height: 16px;
  text-align: center;
  color: #a3afbf;
  margin-top: 4px;
`;

const getScreenForTrackEvent = (from) => {
  const isScreenSettlements = window.location.pathname.includes('/settlements');

  switch (from) {
    case SAMEDAY_MODAL_LOCATIONS.ONDEMAND: {
      return isScreenSettlements
        ? 'Settlements Page || Settle Now Modal || Settlement Successful || Same-day Settlements Modal'
        : 'PG Dashboard Home || Settle Now Modal || Settlement Successful || Same-day Settlements Modal';
    }

    case SAMEDAY_MODAL_LOCATIONS.SETTLEMENTS_DETAILS: {
      return isScreenSettlements
        ? 'Settlements Page || Settlement Details Modal || Same-day Settlements Modal'
        : 'PG Dashboard Home || Settlement Details Modal || Same-day Settlements Modal';
    }

    case SAMEDAY_MODAL_LOCATIONS.SETTLEMENTS_HOME: {
      return 'Settlements Page';
    }

    case SAMEDAY_MODAL_LOCATIONS.ENABLE_AUTOMATIC_ROUTE: {
      return 'Settlements Page - /enable_automatic route';
    }

    default:
      return null;
  }
};

function PreEnable({
  user,
  setAutoEnabled,
  pricingRate,
  updateSession,
  showNotification,
  closeModal,
  trackSameDaySettlement = () => {},
  from,
}) {
  const isOndemandSettlementEnabled = user.isOndemandSettlementEnabled;
  const isOndemandSettlementsRestricted = user.isOndemandSettlementsRestricted;
  const isFullOndemandSettlementEnabled =
    isOndemandSettlementEnabled && !isOndemandSettlementsRestricted;
  const isPartialOndemandSettlementEnabled =
    isOndemandSettlementEnabled && isOndemandSettlementsRestricted;

  const [isLoading, setLoading] = useState(false);
  const [view, setView] = useState(PRE_ENABLE_VIEWS.SAMEDAY_SETTLEMENTS);
  const [hovered, setHovered] = useState(false);
  const isPricingValid = isPricingRateValid(pricingRate);
  const screen = getScreenForTrackEvent(from);

  useEffect(() => {
    trackEnableModalRendered({ screen });
  }, []);

  useEffect(() => {
    if (isFullOndemandSettlementEnabled) {
      return setView(PRE_ENABLE_VIEWS.INSTANT_SETTLEMENTS);
    } else if (isPartialOndemandSettlementEnabled && !isPricingValid) {
      return setView(PRE_ENABLE_VIEWS.SAMEDAY_SETTLEMENTS);
    }

    const timeoutId = setInterval(() => {
      if (!hovered) {
        setView((prev) =>
          prev === PRE_ENABLE_VIEWS.SAMEDAY_SETTLEMENTS
            ? PRE_ENABLE_VIEWS.INSTANT_SETTLEMENTS
            : PRE_ENABLE_VIEWS.SAMEDAY_SETTLEMENTS,
        );
      }
    }, 4000);

    return () => clearInterval(timeoutId);
  }, [hovered, isFullOndemandSettlementEnabled, view]);

  const handleOnEnableClick = () => {
    setLoading(true);
    trackEnableNowClicked({ screen });

    return new Promise((resolve) => {
      enableAutomaticSettlements()
        .then(() => {
          const updatedUser = new User(user);
          updatedUser
            .fetch()
            .then((res) => {
              updateSession({ user: res.data });
              setAutoEnabled(true);
              setLoading(false);
              resolve();
            })
            .catch(() => {
              showNotification({
                type: 'error',
                message: 'Error loading user profile',
              });
              closeModal();
              resolve();
            });

          if (isOndemandSettlementEnabled && isOndemandSettlementsRestricted) {
            setEnableEsPartialAutomaticDate();
          }

          trackSameDaySettlement();
        })
        .catch(({ errors }) => {
          const error = errors?.[0] || 'Something went wrong!';
          showNotification({
            type: 'error',
            message: error,
          });
          setLoading(false);
          resolve();
        });
    });
  };

  const handleCloseClick = () => {
    trackEnableModalCloseClick({ screen });
    closeModal();
  };

  const onMouseEnter = () => setHovered(true);
  const onMouseLeave = () => setHovered(false);

  const getBottomHeading = () => {
    if (isFullOndemandSettlementEnabled && !isPricingValid) return null;

    switch (view) {
      case PRE_ENABLE_VIEWS.SAMEDAY_SETTLEMENTS: {
        return (
          <>
            How Same-day Settlements <br />
            work
          </>
        );
      }

      case PRE_ENABLE_VIEWS.INSTANT_SETTLEMENTS: {
        return (
          <>
            Enjoy additional benefits on <br />
            Instant Settlements
          </>
        );
      }

      default:
        return window.location.pathname;
    }
  };

  const getIndicators = () => {
    if (isFullOndemandSettlementEnabled || !isPricingValid) return null;

    return (
      <IndicatorWrapper>
        <IndicatorContainer>
          <IndicatorDot active={view === PRE_ENABLE_VIEWS.SAMEDAY_SETTLEMENTS} />
          <IndicatorLine
            key={view}
            reverse={view === PRE_ENABLE_VIEWS.INSTANT_SETTLEMENTS}
            active={!hovered}
          />
          <IndicatorDot active={view === PRE_ENABLE_VIEWS.INSTANT_SETTLEMENTS} />
          <IndicatorLeftClickArea
            data-testid="left-indicator"
            onClick={() => setView(PRE_ENABLE_VIEWS.SAMEDAY_SETTLEMENTS)}
          />
          <IndicatorRightClickArea
            data-testid="right-indicator"
            onClick={() => setView(PRE_ENABLE_VIEWS.INSTANT_SETTLEMENTS)}
          />
        </IndicatorContainer>
      </IndicatorWrapper>
    );
  };

  const getBottomMainContent = () => {
    switch (view) {
      case PRE_ENABLE_VIEWS.SAMEDAY_SETTLEMENTS: {
        return (
          <>
            {getIndicators()}
            <div onMouseEnter={onMouseEnter} onMouseLeave={onMouseLeave}>
              <SamedayWorksContainer>
                <TimelineContainer>
                  <TimelineTop>
                    {SAMEDAY_TIMELINE_ICONS.map((item) => (
                      <i key={item} className={`i i-${item}`} />
                    ))}
                  </TimelineTop>
                  <TimelineBottom />
                </TimelineContainer>

                <SamedayDetailContainer>
                  {getSamedayTimeline().map((item, idx) => (
                    <SamedayDetailItem key={idx}>
                      <SamedayDetailTop>
                        <SamedayDetailTitle>{item.title}</SamedayDetailTitle>
                        {item.date && (
                          <SamedayDetailDate>
                            {' '}
                            - {item.date.format('MMMM D, YYYY')}
                          </SamedayDetailDate>
                        )}
                      </SamedayDetailTop>
                      <SamedayDetailInfo>{item.info}</SamedayDetailInfo>
                    </SamedayDetailItem>
                  ))}
                </SamedayDetailContainer>
              </SamedayWorksContainer>
            </div>
            <FeeInfo>
              Minimal fee of <span>0.15%</span> per settlement
            </FeeInfo>
          </>
        );
      }

      case PRE_ENABLE_VIEWS.INSTANT_SETTLEMENTS: {
        if (!isPricingValid) return null;
        const discount = getDiscountPercentage(pricingRate);
        return (
          <>
            {getIndicators()}
            <div onMouseEnter={onMouseEnter} onMouseLeave={onMouseLeave}>
              <InstantSettlementsBenefits>
                <BigDiscount>{discount}%</BigDiscount>
                <InstantSettlementsTitle>
                  discount on Instant Settlements fees, forever!
                </InstantSettlementsTitle>

                <InstantSettlementsFeeContainer>
                  <InstantSettlementsCurrentFeeContainer>
                    <InstantSettlementsCurrentFee>
                      {(pricingRate / 100).toFixed(2)}
                    </InstantSettlementsCurrentFee>
                    <StrikeThrough />
                  </InstantSettlementsCurrentFeeContainer>
                  <i className="i i-arrow-forward" />
                  <InstantSettlementsOfferFee>0.15%</InstantSettlementsOfferFee>
                  <InstantSettlementsOfferFeeLabel>/ settlement</InstantSettlementsOfferFeeLabel>
                </InstantSettlementsFeeContainer>

                <InstantSettlementsLabel>
                  The discounted pricing for Instant Settlements will be effective once you enable
                  Same-day Settlements
                </InstantSettlementsLabel>
              </InstantSettlementsBenefits>
            </div>
            <FeeInfo>
              Same reduced fee of <span>0.15%</span> per settlement
            </FeeInfo>
          </>
        );
      }

      default:
        return null;
    }
  };

  return (
    <>
      <TopSection
        heading="Automate your settlements"
        subHeading="Get your daily revenue settled automatically on all working days"
        onCloseClick={handleCloseClick}
        showTimings
      />

      <BottomSection heading={getBottomHeading()}>
        {getBottomMainContent()}

        <Button pendingState="Enabling..." onClick={handleOnEnableClick} disabled={isLoading}>
          Enable Same-day Settlements
        </Button>
        <OptOutAnytime>Opt-out anytime</OptOutAnytime>
      </BottomSection>
    </>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, {
  updateSession: fnUpdateSession,
  showNotification: fnShowNotification,
  closeModal: fnCloseModal,
})(PreEnable);
