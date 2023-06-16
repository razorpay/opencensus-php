import { useState, useEffect } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';

import CODPrepaidConfigs from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/containers/CODPrepaidConfigs';
import CODPrepaidSavedConfigs from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/containers/CODPrepaidSavedConfigs';
import Spinner from 'common/ui/Spinner';

import { showNotification as displayNotification } from 'merchant_common/reducers/notifications';
import {
  fetchPrepayCODConfigs,
  resetPrepayCODConfigs,
} from 'merchant/reducers/magicCheckout/prepayCOD/configDashboard/action';

import { analyticsTrack } from 'common/utils/analytics';

import { NOTIFICATION_MSGS } from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/constants';

import 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/CODPrepaidConfigs.styl';

const ConfigsDashboard = (props) => {
  const {
    configs,
    fetchConfigs,
    showNotification,
    resetConfigs,
    isManualReviewOpted,
    platform,
    shopId,
    user,
  } = props;

  const { isLoading, isPrepayCODEnabled, prepayCODConfigs, error } = configs;

  const [isConfigSaved, setIsConfigSaved] = useState(false);
  const [showPrepayCODToggle, setShowPrepayCODToggle] = useState(true);

  const trackMagicPrepayConfigClick = () => {
    analyticsTrack({
      objectName: `1ccMdClickedOnCODToPrePay`,
      actionName: 'clicked',
      screen: `configurations l1`,
      properties: {
        merchant_id: user?.merchant?.id,
      },
    });

    analyticsTrack({
      objectName: `1ccMdViewedCODToPrePay`,
      actionName: 'render',
      screen: `configurations l1`,
      properties: {
        merchant_id: user?.merchant?.id,
      },
    });
  };

  useEffect(() => {
    setIsConfigSaved(!!Object.entries(prepayCODConfigs).length);
  }, [prepayCODConfigs]);

  useEffect(() => {
    setShowPrepayCODToggle(isPrepayCODEnabled || Object.entries(prepayCODConfigs).length > 0);
  }, [configs]);

  useEffect(() => {
    if (fetchConfigs && !Object.entries(prepayCODConfigs).length) {
      trackMagicPrepayConfigClick();
      fetchConfigs();
    }

    return resetConfigs;
  }, [fetchConfigs]);

  if (error) {
    showNotification({
      type: 'error',
      message: NOTIFICATION_MSGS.error,
      className: 'magic-cod-prepaid-notification',
    });

    return (
      <div className="rzp-error-boundary has-raven">
        <div className="js-error-container">
          <div className="js-error-content">
            <div className="js-error-illustration m-b" />
            <div className="js-error-text">
              <p>We're sorry — something's gone wrong.</p>
              <p>Please try again after sometime or try to refresh the page.</p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (isLoading) {
    return (
      <div className="spinner-container">
        <Spinner />
      </div>
    );
  }

  return (
    <div className="prepay-cod-configs-container">
      <h3 className="container-heading">
        Reduce risk of RTO by converting COD orders to Prepaid orders.
      </h3>
      {platform === 'woocommerce' && (
        <div className="wooc-update-plugin-msg">
          Note: To use this feature, please update your Razorpay WooCommerce plugin to version 4.5.2
          or above.
        </div>
      )}
      {isPrepayCODEnabled && isConfigSaved && !error ? (
        <CODPrepaidSavedConfigs
          setIsConfigSaved={setIsConfigSaved}
          prepayCODConfigs={prepayCODConfigs}
          isManualReviewOpted={isManualReviewOpted}
        />
      ) : (
        <CODPrepaidConfigs
          setIsConfigSaved={setIsConfigSaved}
          showPrepayCODToggle={showPrepayCODToggle}
          isPrepayCODEnabled={isPrepayCODEnabled}
          prepayCODConfigs={prepayCODConfigs}
          isManualReviewOpted={isManualReviewOpted}
          platform={platform}
          shopId={shopId}
          user={user}
        />
      )}
    </div>
  );
};

const mapStateToProps = (state) => ({
  configs: state.magicPrepayCODConfigs,
  isManualReviewOpted: state.magicCheckout?.cod_order_control,
  platform: state.magic_settings?.platform,
  shopId: state.magic_settings?.shop_id,
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchConfigs: fetchPrepayCODConfigs,
      showNotification: displayNotification,
      resetConfigs: resetPrepayCODConfigs,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(ConfigsDashboard);
