import React from 'react';
import { connect } from 'react-redux';

import { VerticalPartition } from './CumulativeOrders';

type PropType = {
  data: [
    {
      total_order: number;
      high_risk_order: number;
      medium_risk_order: number;
      low_risk_order: number;
    },
  ];
};

const RiskLevelOrderSplitCumulative = ({ data }: PropType) => {
  return (
    <div className="cumulative manual-review-cumulative">
      <div className="order-count">
        <span className="total-color" />
        <span className="title total">Total orders</span>
        <span className="number">{data ? data[0]?.total_order ?? 0 : '--'}</span>
      </div>
      <VerticalPartition />
      <div className="order-count">
        <span className="risky-color" />
        <span className="title risky">High risk orders</span>
        <span className="number">{data ? data[0]?.high_risk_order ?? 0 : '--'}</span>
      </div>
      <VerticalPartition />
      <div className="order-count">
        <span className="medium-risk-color" />
        <span className="title safe">Medium risk orders</span>
        <span className="number">{data ? data[0]?.medium_risk_order ?? 0 : '--'}</span>
      </div>
      <VerticalPartition />
      <div className="order-count">
        <span className="safe-color" />
        <span className="title safe">Low risk orders</span>
        <span className="number">{data ? data[0]?.low_risk_order ?? 0 : '--'}</span>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  data: state.magicRTOAnalytics.manual_risk_order_split_cumulative?.data,
});

export default connect(mapStateToProps, null)(RiskLevelOrderSplitCumulative);
