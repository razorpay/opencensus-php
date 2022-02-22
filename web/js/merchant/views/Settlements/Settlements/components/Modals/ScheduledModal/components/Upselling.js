import React, { useState, useEffect } from 'react';
import styled, { css } from 'styled-components';
import { connect } from 'react-redux';

import { AsyncBtn } from 'common/new-ui/Button';
import User from 'merchant/models/User';
import { updateSession as fnUpdateSession } from 'merchant/reducers/session';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import {
  closeModal as fnCloseModal,
  openModal as fnOpenModal,
} from 'merchant_common/reducers/modals';

import ScheduledModal from '../';

import {
  enableAutomaticSettlements,
  getDiscountPercentage,
  getInstantPricingPercentage,
  setEnableEsPartialAutomaticDate,
} from '../utils';
import { DEFAULT_PRICING_RATE } from '../constants';

const BIG_UPSELLING_BG = '/dist/css/assets/settlements/bigupselling-bg.svg';
const SMALL_UPSELLING_BG = '/dist/css/assets/settlements/upselling-bg.svg';

const Container = styled.div`
  width: 100%;
  background: ${({ showDiscount }) =>
    showDiscount ? `url(${BIG_UPSELLING_BG})` : `url(${SMALL_UPSELLING_BG})`};
  background-repeat: no-repeat;
  background-size: cover;
  border-radius: 6px;
  padding: 16px;
`;

const DidYouKnowContainer = styled.div`
  display: flex;
  align-items: center;
  gap: 6.67px;
  margin-bottom: 10px;
  i {
    color: #ffffff;
    font-size: 18px;
  }
`;

const DidYouKnow = styled.span`
  font-weight: bold;
  font-size: 16px;
  line-height: 20px;
  color: #ffffff;
`;

const Detail = styled.div`
  font-size: 14px;
  line-height: 20px;
  color: #dfe3e9;
  margin-bottom: 12px;
  span {
    font-weight: 900;
  }
`;

const DiscountContainer = styled.div`
  padding: 16px 10px 12px;
  width: 100%;
  background-color: rgba(248, 249, 251, 0.16);
  border-radius: 3px;
  margin-bottom: 8px;
`;

const BigDiscount = styled.div`
  font-weight: 900;
  font-size: 32px;
  line-height: 40px;
  color: #f1ffed;
  text-align: center;
`;

const BigDiscountLabel = styled.div`
  font-size: 13px;
  line-height: 16px;
  text-align: center;
  color: #f0ffff;
  margin: 4px 0 8px;
`;

const InstantSettlementsFeeContainer = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 3px 0;
  background: #ebf6f9;
  border: 1px solid rgba(0, 140, 177, 0.32);
  border-radius: 3px;
  > .i-arrow-forward {
    color: #5d6d86;
    margin: 0 13px;
  }
`;

const InstantSettlementsCurrentFeeContainer = styled.div`
  position: relative;
`;

const InstantSettlementsCurrentFee = styled.div`
  font-size: 14px;
  line-height: 22px;
  color: #5d6d86;
`;

const StrikeThrough = styled.span`
  width: 35px;
  height: 1px;
  background-color: #435775;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
`;

const InstantSettlementsOfferFee = styled.span`
  font-weight: bold;
  font-size: 14px;
  line-height: 22px;
  color: #324664;
`;

const InstantSettlementsOfferFeeLabel = styled.span`
  font-size: 12px;
  line-height: 22px;
  opacity: 0.87;
  margin-left: 4px;
  color: #324664;
`;

const Buttons = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 9px;
`;

const Button = styled(AsyncBtn)`
  flex: 1;
  text-align: center;
  padding: 10px 0 !important;
  font-size: 12px;
  font-weight: bold;
  text-transform: capitalize;
  margin: 0 !important;
  ${({ outline }) =>
    outline
      ? css`
          border: 1px solid rgba(255, 255, 255, 0.4) !important;
          background: transparent !important;
          color: #fff !important;
        `
      : css`
          background: #ffffff !important;
          color: #2a86f3 !important;
        `}
`;

function Upselling({ user, showDiscount, openModal, closeModal, updateSession, showNotification }) {
  const [isLoading, setLoading] = useState(false);
  const [pricingRate, setPricingRate] = useState(DEFAULT_PRICING_RATE);

  useEffect(() => {
    getInstantPricingPercentage().then(({ data }) => {
      const pricingPercentage = data?.items?.[0]?.pricing_rule?.percent_rate;
      if (pricingPercentage) setPricingRate(pricingPercentage);
    });
  }, []);

  const isOndemandSettlementsRestricted = user.isOndemandSettlementsRestricted;
  const isOndemandSettlementEnabled = user.isOndemandSettlementEnabled;

  const handleKnowMoreClick = () => {
    openModal({
      component: <ScheduledModal />,
      size: 'small',
      disableClose: true,
    });
  };

  const handleEnableNowClick = () => {
    return new Promise((resolve) => {
      enableAutomaticSettlements()
        .then(() => {
          const updatedUser = new User(user);
          updatedUser
            .fetch()
            .then((res) => {
              updateSession({ user: res.data });
              openModal({
                component: <ScheduledModal enabled />,
                size: 'small',
                disableClose: true,
              });
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

  return (
    <Container showDiscount={showDiscount}>
      <DidYouKnowContainer>
        <i className="i i-info-outline" />
        <DidYouKnow>Did you know?</DidYouKnow>
      </DidYouKnowContainer>
      <Detail>
        You can get your daily revenue automatically at <span>09:00 AM</span> and{' '}
        <span>05:00 PM</span> on all working days with Same-day Settlements
      </Detail>

      {showDiscount && (
        <DiscountContainer>
          <BigDiscount>{getDiscountPercentage(pricingRate)}%</BigDiscount>
          <BigDiscountLabel>discount on your Instant Settlements fee, forever!</BigDiscountLabel>
          <InstantSettlementsFeeContainer>
            <InstantSettlementsCurrentFeeContainer>
              <InstantSettlementsCurrentFee>0.3%</InstantSettlementsCurrentFee>
              <StrikeThrough />
            </InstantSettlementsCurrentFeeContainer>
            <i className="i i-arrow-forward" />
            <InstantSettlementsOfferFee>0.15%</InstantSettlementsOfferFee>
            <InstantSettlementsOfferFeeLabel>/ settlement</InstantSettlementsOfferFeeLabel>
          </InstantSettlementsFeeContainer>
        </DiscountContainer>
      )}

      <Buttons>
        <Button outline={showDiscount} onClick={handleKnowMoreClick} disabled={isLoading}>
          Know more
        </Button>
        {showDiscount && (
          <Button onClick={handleEnableNowClick} disabled={isLoading}>
            Enable now
          </Button>
        )}
      </Buttons>
    </Container>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, {
  closeModal: fnCloseModal,
  openModal: fnOpenModal,
  updateSession: fnUpdateSession,
  showNotification: fnShowNotification,
})(Upselling);
