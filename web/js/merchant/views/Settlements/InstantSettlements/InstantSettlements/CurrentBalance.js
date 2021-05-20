import React, { useState, lazy, Suspense } from 'react';
import PropTypes from 'prop-types';
import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import trackIS from 'merchant/views/Settlements/InstantSettlements/ga';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Popover, { PopoverBody } from 'common/ui/Popover';
import SettleNowLottie from 'merchant/helpers/lottieConfigs/SettleNow.json';
import SettleNowLottieHover from 'merchant/helpers/lottieConfigs/SettleNowHover.json';
import settleNowIcon from '../../../../../../icons/merchant/settle-now-thunder.svg';
import { trackAnimatedSettleBtnImpressions } from '../../Settlements/ga';

const CustomLottie = lazy(() =>
  import(/* webpackChunkName: "CustomLottie" */ 'common/new-ui/Lottie'),
);

const DefaultSettlementBtn = ({ handleSettleNowClick, checkIfSettlementDisabled }) => {
  return (
    <Button.Primary
      className="current-balance--settle-btn settle-now settle-now--button"
      onClick={handleSettleNowClick}
      disabled={checkIfSettlementDisabled}
    >
      <img src={settleNowIcon} alt="settle-now-thunder" className="settlement-icon-thunder" />
      Settle Now
    </Button.Primary>
  );
};

const CurrentBalance = ({
  balance,
  showOndemandSettlementForm,
  updatedAt,
  isBalanceLoading,
  isSettleNowRestricted,
  settleNowRestrictionMsg,
  settlementExists,
  esOndemandSettlementEnabled,
  merchantId,
}) => {
  const [hoverOnSettleButton, setHoverOnSettleButton] = useState(false);
  const checkIfSettlementDisabled = isSettleNowRestricted || isBalanceLoading || balance < 100;

  const handleSettleNowClick = (e) => {
    trackIS.clickCTASettleNow();
    showOndemandSettlementForm(e);
  };

  const handleMouseActivityOverSettleBtn = (type) => {
    if (!checkIfSettlementDisabled) setHoverOnSettleButton(type === 'mouseEnter');
  };

  return (
    <div className="current-balance">
      <div className="current-balance--left">
        <div>
          <img src="/dist/css/assets/capital/bank-balance.svg" />
        </div>
        <div className="current-balance--cb-content">
          <div className="current-balance--cb-heading">Current Balance</div>
          <div className="current-balance--cb-date">
            Updated: <Time value={updatedAt} format="MMM DD, h:mm A" />
          </div>
        </div>
      </div>
      <div className="current-balance--right">
        <div className="current-balance--amount">
          {isBalanceLoading ? <PlaceholderLoader /> : <Amount value={balance} currency="INR" />}
        </div>
        <div>
          {!settlementExists && esOndemandSettlementEnabled ? (
            <div
              className="current-balance--settle-btn .settle-now"
              onMouseEnter={() => handleMouseActivityOverSettleBtn('mouseEnter')}
              onMouseLeave={() => handleMouseActivityOverSettleBtn('mouseLeave')}
            >
              <Suspense
                fallback={
                  <DefaultSettlementBtn
                    handleSettleNowClick={handleSettleNowClick}
                    checkIfSettlementDisabled={checkIfSettlementDisabled}
                  />
                }
              >
                <CustomLottie
                  onClick={handleSettleNowClick}
                  animationData={hoverOnSettleButton ? SettleNowLottieHover : SettleNowLottie}
                  autoplay={hoverOnSettleButton ? false : true}
                  loop={hoverOnSettleButton ? false : true}
                  width="138px"
                  isStopped={hoverOnSettleButton ? !hoverOnSettleButton : false}
                  disabled={checkIfSettlementDisabled}
                  trackInitialRenderImpression={trackAnimatedSettleBtnImpressions}
                  fromWhere="Instant Settlement"
                  merchantId={merchantId}
                />
              </Suspense>
            </div>
          ) : (
            <DefaultSettlementBtn
              handleSettleNowClick={handleSettleNowClick}
              checkIfSettlementDisabled={checkIfSettlementDisabled}
            />
          )}

          {settleNowRestrictionMsg && (
            <Popover
              align="top"
              parentQuerySelector={`.current-balance--settle-btn .settle-now`}
              theme="dark"
            >
              <PopoverBody>{settleNowRestrictionMsg}</PopoverBody>
            </Popover>
          )}
        </div>
      </div>
    </div>
  );
};

CurrentBalance.propTypes = {
  balance: PropTypes.number,
  showOndemandSettlementForm: PropTypes.func,
  updatedAt: PropTypes.number,
  isBalanceLoading: PropTypes.bool,
  isSettleNowRestricted: PropTypes.bool,
  settlementExists: PropTypes.bool,
};

export default CurrentBalance;
