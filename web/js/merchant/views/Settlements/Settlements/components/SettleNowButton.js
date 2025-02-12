import React, { lazy, Suspense, useState, useEffect } from 'react';
import { connect } from 'react-redux';
import Button from 'common/new-ui/Button';
import { getItem } from 'common/utils/localStorage';
import SettleNowLottie from 'merchant/helpers/lottieConfigs/SettleNow.json';
import SettleNowLottieHover from 'merchant/helpers/lottieConfigs/SettleNowHover.json';
import { trackAnimatedSettleBtnImpressions } from 'merchant/views/Settlements/Settlements/ga';
import settleNowIcon from '../../../../../../icons/merchant/settle-now-thunder.svg';
import PropTypes from 'prop-types';
import { trackSettleNowClicked } from 'merchant/views/Settlements/trackEvents';
import { merchantFetch } from 'merchant/utils/ajax';
import { noop } from 'common/utils/rzp-utils';

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
  user,
  disabled: ondemandDisabled,
  merchantId,
  fromWhere,
  settlementExists,
  esOndemandSettlementEnabled,
  showOndemandSettlementForm,
  checkIfFirstEverSettlement,
}) => {
  const [disabled, setDisabled] = useState(ondemandDisabled);
  const [hoverOnSettleButton, setHoverOnSettleButton] = useState(false);

  /**
   * This to stop disabling the settle now if user is live on
   * Route Ondemand Settlements and have a valid balance > 0
   */
  useEffect(() => {
    if (user.isOndemandRouteSettlementsEnabled && ondemandDisabled) {
      merchantFetch({
        url: 'capital_es/service/early_settlements/ondemand/route_settlement_balance',
        mode: 'live',
        method: 'get',
        headers: {
          'Content-Type': 'application/json',
        },
      })
        .then((res) => {
          if (res.success) {
            const balance = parseInt(res?.data?.balance || 0, 10);
            if (balance > 0) {
              setDisabled(false);
            }
          }
        })
        .catch(noop);
    } else {
      setDisabled(ondemandDisabled);
    }
  }, [user.isOndemandRouteSettlementsEnabled, ondemandDisabled]);

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
      className="settle-btn settle-now--desktop"
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

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(SettleNowButton);
