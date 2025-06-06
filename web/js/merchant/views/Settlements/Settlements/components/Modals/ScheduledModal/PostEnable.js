import React from 'react';
import { ZapIcon } from '@razorpay/blade/components';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import styled from 'styled-components';

import { useODSAutomaticPricingDiscount } from 'merchant/views/Settlements/InstantSettlements/hooks/useODSAutomaticPricingDiscount';
import { closeModal as fnCloseModal } from 'merchant_common/reducers/modals';

import BottomSection from './components/BottomSection';
import Button from './components/Button';
import List from './components/List';
import ProgressBar from './components/ProgressBar';
import TopSection from './components/TopSection';
import {
  UNLOCK_POINTS,
  DAILY_LIMIT_POINTS,
  POST_ENABLE_TYPES,
  ONDEMAND_FEE_BENEFITS,
  getSamedayBenefits,
} from './constants';
import {
  getAutomaticSettlementTime,
  getEnableEsPartialAutomaticDate,
  getNoOfDaysAfterEsPartialEnable,
} from './utils';
import { getIsOdsMigrationEnabled } from '@dashboards/payments/views/Settlements/InstantSettlements/utils/common';

const ItemsContainer = styled.div`
  width: 100%;
  margin: 20px 0;
`;

const Item = styled.div`
  width: 100%;
  background-color: rgb(248, 249, 251);
  padding: 11px 16px;
  display: flex;
  align-items: center;
  gap: 16px;
  &:not(:last-child) {
    margin-bottom: 6px;
  }
`;

const GradientText = styled.span`
  font-weight: 800;
  font-size: 32px;
  line-height: 40px;
  background: linear-gradient(#baf4f5, #30c5d8);
  background-clip: text;
  -webkit-background-clip: text; /* stylelint-disable-line property-no-vendor-prefix */
  -webkit-text-fill-color: transparent;
  text-align: right;
  width: 83px;
`;

const ItemLabel = styled.span`
  font-size: 13px;
  line-height: 17px;
  color: #5d6d86;
`;

const BadgeLabel = styled.div`
  font-size: 14px;
  line-height: 17px;
  text-align: ${({ align }) => align ?? 'left'};
  color: #f2f4f8;
  span {
    font-weight: 800;
  }
`;

const CURRENCY_PARTIAL_LIMIT = {
  INR: '₹15,000',
};

function PostEnable({ user, postModalType, closeModal }) {
  const isOdsExpEnabled = getIsOdsMigrationEnabled(user);
  const { discountPercent, canViewDiscount } = useODSAutomaticPricingDiscount(
    isOdsExpEnabled,
    user.merchant.currency || 'INR',
  );
  const isOndemandSettlementEnabled = user.isOndemandSettlementEnabled;
  const isOndemandSettlementsRestricted = user.isOndemandSettlementsRestricted;
  const isPartialOndemandSettlementEnabled =
    isOndemandSettlementEnabled && isOndemandSettlementsRestricted;
  const isFullOndemandSettlementEnabled =
    isOndemandSettlementEnabled && !isOndemandSettlementsRestricted;
  const esPartialAutomaticEnableDate = getEnableEsPartialAutomaticDate();

  const getModalType = () => {
    switch (true) {
      case esPartialAutomaticEnableDate: {
        const diff = getNoOfDaysAfterEsPartialEnable();
        if (diff <= 30) {
          return POST_ENABLE_TYPES.SAMEDAY_PARTIAL_SUCCESS;
        }
        return POST_ENABLE_TYPES.SAMEDAY_FULL_FAILURE;
      }
      case isFullOndemandSettlementEnabled:
        return POST_ENABLE_TYPES.SAMEDAY_FULL_SUCCESS;
      case isPartialOndemandSettlementEnabled:
        return POST_ENABLE_TYPES.SAMEDAY_PARTIAL_SUCCESS;
      default:
        return null;
    }
  };

  const modalType = postModalType || getModalType();

  const getUnderstoodButton = () => <Button onClick={closeModal}>Understood</Button>;

  const getContent = () => {
    switch (modalType) {
      case POST_ENABLE_TYPES.SAMEDAY_PARTIAL_SUCCESS: {
        return (
          <>
            <TopSection
              emoji="🎉"
              heading="Same-day Settlements activated"
              subHeading={
                <>
                  Congratulations! You’ve successfully activated Same-day Settlements and your next
                  settlement will happen at <span>{getAutomaticSettlementTime()}</span>. Enjoy!
                </>
              }
            />
            <BottomSection heading="While you enjoy your benefits...">
              <ProgressBar />
              <List
                label="To unlock 100% settlements, keep up your sales cycle and follow the eligibility criteria given below"
                items={UNLOCK_POINTS}
              />
              {getUnderstoodButton()}
            </BottomSection>
          </>
        );
      }

      case POST_ENABLE_TYPES.SAMEDAY_FULL_SUCCESS: {
        return (
          <>
            <TopSection
              emoji="🎉"
              heading="Same-day Settlements activated"
              subHeading={
                <>
                  Congratulations! You’ve successfully activated Same-day Settlements and your next
                  settlement will happen at <span>{getAutomaticSettlementTime()}</span>. Enjoy!
                </>
              }
            />
            <BottomSection heading="Enjoy all your benefits!">
              <ItemsContainer>
                {getSamedayBenefits(discountPercent, canViewDiscount).map((item, idx) => (
                  <Item key={idx}>
                    <GradientText>{item.percentage}</GradientText>
                    <ItemLabel>{item.label}</ItemLabel>
                  </Item>
                ))}
              </ItemsContainer>
              {getUnderstoodButton()}
            </BottomSection>
          </>
        );
      }

      case POST_ENABLE_TYPES.SAMEDAY_FULL_SUCCESS_SHIFT: {
        return (
          <>
            <TopSection
              emoji="🎉"
              heading="Congratulations"
              subHeading="You can now settle your full balance via Instant and Same-day Settlements"
            />
            <BottomSection heading="Enjoy all your benefits!">
              <ItemsContainer>
                {getSamedayBenefits(discountPercent, canViewDiscount).map((item, idx) => (
                  <Item key={idx}>
                    <GradientText>{item.percentage}</GradientText>
                    <ItemLabel>{item.label}</ItemLabel>
                  </Item>
                ))}
              </ItemsContainer>
              {getUnderstoodButton()}
            </BottomSection>
          </>
        );
      }

      case POST_ENABLE_TYPES.FULL_SUCCESS_SHIFT_WITHOUT_SAMEDAY: {
        return (
          <>
            <TopSection
              emoji="🎉"
              heading="Congratulations"
              subHeading="You can now settle your full balance via Instant Settlements"
            />
            <BottomSection heading="Enjoy all your benefits!">
              <ItemsContainer>
                {ONDEMAND_FEE_BENEFITS.map((item, idx) => (
                  <Item key={idx}>
                    <GradientText>{item.percentage}</GradientText>
                    <ItemLabel>{item.label}</ItemLabel>
                  </Item>
                ))}
              </ItemsContainer>
              {getUnderstoodButton()}
            </BottomSection>
          </>
        );
      }

      case POST_ENABLE_TYPES.SAMEDAY_FULL_FAILURE: {
        return (
          <>
            <TopSection
              emoji="🕛"
              heading="Hold on tight.."
              badgeLabel={
                <BadgeLabel align="center">
                  It is taking us longer to bring you full benefits of Same-day Settlements
                </BadgeLabel>
              }
            />
            <BottomSection heading="To unlock 100% settlements">
              <List
                label="Keep up your sales cycle and follow the eligibility criteria given below"
                items={UNLOCK_POINTS}
              />
              {getUnderstoodButton()}
            </BottomSection>
          </>
        );
      }

      case POST_ENABLE_TYPES.ODS_MERCHANT_LEVEL_LIMIT: {
        return (
          <>
            <TopSection
              emoji={<ZapIcon color="surface.background.cloud.subtle" size="xlarge" />}
              heading="Instant Settlements now come with a daily settlement limit."
            />
            <BottomSection heading="Daily limits ensure">
              <List
                labelPosition="bottom"
                label="If you require assistance or need to discuss your limit, please contact your Relationship Manager or raise a support ticket here."
                items={DAILY_LIMIT_POINTS}
              />
              {getUnderstoodButton()}
            </BottomSection>
          </>
        );
      }

      case POST_ENABLE_TYPES.SAMEDAY_FULL_SHIFT_PROGRESS: {
        const currencyCode = user.merchant.currency || 'INR';
        const partialLimit = CURRENCY_PARTIAL_LIMIT[currencyCode];
        return (
          <>
            <TopSection
              heading="How much can I settle?"
              subHeading="You are enjoying early access to Instant Settlements and can settle a part of your balance"
              badgeLabel={
                <BadgeLabel>
                  ✅ &nbsp;<span>60%</span> of your balance{' '}
                  {partialLimit ? <span>upto {partialLimit}</span> : null}
                </BadgeLabel>
              }
            />
            <BottomSection heading="While you enjoy your benefits...">
              <ProgressBar />
              <List
                label="To unlock 100% settlements, keep up your sales cycle and follow the eligibility criteria given below"
                items={UNLOCK_POINTS}
              />
              {getUnderstoodButton()}
            </BottomSection>
          </>
        );
      }

      case POST_ENABLE_TYPES.SAMEDAY_FULL_UNLOCK_STATUS: {
        return (
          <>
            <TopSection
              heading="Same-Day Settlements"
              subHeading="Your daily revenue settled automatically on all working days"
              showTimings
            />
            <BottomSection heading="While you enjoy your benefits...">
              <ProgressBar />
              <List
                label="To unlock 100% settlements, keep up your sales cycle and follow the eligibility criteria given below"
                items={UNLOCK_POINTS}
              />
              {getUnderstoodButton()}
            </BottomSection>
          </>
        );
      }

      default:
        return null;
    }
  };

  return getContent();
}

PostEnable.propTypes = {
  user: PropTypes.any,
  postModalType: PropTypes.string,
  closeModal: PropTypes.func,
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, {
  closeModal: fnCloseModal,
})(PostEnable);
