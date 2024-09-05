import React from 'react';
import { connect } from 'react-redux';

import Header from 'merchant/views/MagicCheckout/RTOAnalytics/containers/Header';
import RiskReportBanner from 'merchant/views/MagicCheckout/RTOAnalytics/common/RiskReportBanner';

/**
 * RTO Analytics Components will have a common Header that provides Time Range filtering.
 * Hence We are creating a wrapper component(HOC) connected with store variables to Pass
 * them as props to children. We are doing named export of the component as Connected Wrapper.
 */
const Wrapper = (props) => {
  const { Component, displayName, ...rest } = props;

  return (
    <div className="rto-magic-container">
      <div className="tab-content" style={{ marginLeft: 0 }}>
        {displayName === 'RiskReport' && rest?.isManualReviewOpted && <RiskReportBanner />}
        <Header isManualReviewOpted={rest?.isManualReviewOpted} />
        <Component {...rest} />
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  isManualReviewOpted: state.magicCheckout.cod_order_control,
  isPrepayCODOpted: state.magicCheckout.one_cc_prepay_cod_conversion,
});

export const ConnectedWrapper = connect(mapStateToProps)(Wrapper);
