/* eslint-disable */
import React, { lazy, Suspense, useState } from 'react';
import Button from 'common/new-ui/Button';
import { getItem } from 'common/utils/localStorage';
import SettleNowLottie from 'merchant/helpers/lottieConfigs/SettleNow.json';
import SettleNowLottieHover from 'merchant/helpers/lottieConfigs/SettleNowHover.json';
import { trackAnimatedSettleBtnImpressions } from 'merchant/views/Settlements/Settlements/ga';
import settleNowIcon from '../../../../../../icons/merchant/settle-now-thunder.svg';
import PropTypes from 'prop-types';
import { trackSettleNowClicked } from '../../trackEvents';

const CustomLottie = lazy(() =>
  import(/* webpackChunkName: "CustomLottie" */ 'common/new-ui/Lottie'),
);

const DefaultSettlementBtn = ({ onClick, disabled }) => {
  return (
    <Button.Primary
      className="current-balance--settle-btn settle-now settle-now--button"
      onClick={onClick}
      disabled={disabled}
    >
      <img src={settleNowIcon} alt="settle-now-thunder" className="settlement-icon-thunder" />
      Settle Now
    </Button.Primary>
  );
};

const SettleNowButton = ({
  disabled,
  merchantId,
  fromWhere,
  settlementExists,
  esOndemandSettlementEnabled,
  showOndemandSettlementForm,
  checkIfFirstEverSettlement,
}) => {
  const [hoverOnSettleButton, setHoverOnSettleButton] = useState(false);

  const handleMouseActivityOverSettleBtn = (type) => {
    if (!disabled) setHoverOnSettleButton(type === 'mouseEnter');
  };

  const handleSettleNowClick = (e) => {
    trackSettleNowClicked(fromWhere);
    const merchantsSettlementStatus = JSON.parse(getItem('merchantsSettlementStatus')) || {};
    const isAnimationDisabled =
      merchantsSettlementStatus[merchantId] !== true &&
      merchantsSettlementStatus[merchantId] !== 'disableAnimation';

    if (isAnimationDisabled) checkIfFirstEverSettlement('disableAnimation');
    showOndemandSettlementForm(e);
  };

  return !settlementExists && esOndemandSettlementEnabled ? (
    <div
      onMouseEnter={() => handleMouseActivityOverSettleBtn('mouseEnter')}
      onMouseLeave={() => handleMouseActivityOverSettleBtn('mouseLeave')}
      class="settle-btn settle-now--desktop"
    >
      <Suspense
        fallback={<DefaultSettlementBtn onClick={handleSettleNowClick} disabled={disabled} />}
      >
        <CustomLottie
          onClick={handleSettleNowClick}
          animationData={hoverOnSettleButton ? SettleNowLottieHover : SettleNowLottie}
          autoplay={!hoverOnSettleButton}
          loop={!hoverOnSettleButton}
          width="138px"
          isStopped={disabled}
          disabled={disabled}
          trackInitialRenderImpression={trackAnimatedSettleBtnImpressions}
          fromWhere={fromWhere}
          merchantId={merchantId}
        />
      </Suspense>
    </div>
  ) : (
    <DefaultSettlementBtn onClick={handleSettleNowClick} disabled={disabled} />
  );
};

SettleNowButton.propTypes = {
  disabled: PropTypes.bool,
  merchantId: PropTypes.string,
  fromWhere: PropTypes.string,
  settlementExists: PropTypes.bool,
  esOndemandSettlementEnabled: PropTypes.bool,
  showOndemandSettlementForm: PropTypes.func,
  checkIfFirstEverSettlement: PropTypes.func,
};

export default SettleNowButton;
