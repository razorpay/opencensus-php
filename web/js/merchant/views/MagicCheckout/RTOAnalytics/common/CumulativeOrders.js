import { connect } from 'react-redux';

export const VerticalPartition = () => {
  return (
    <div className="vertical-partition">
      <p>
        <i className="vup" />
      </p>
      <p>
        <i className="arrow right" />
      </p>
      <p>
        <i className="vdown" />
      </p>
    </div>
  );
};

const CumulativeOrders = ({ data }) => {
  return (
    <div className="cumulative">
      <div className="order-count">
        <span className="total-color" />
        <span className="title total">Total users</span>
        <span className="number">{data ? data[0]?.total_order ?? 0 : '--'}</span>
      </div>
      <VerticalPartition />
      <div className="order-count">
        <span className="risky-color" />
        <span className="title risky">Risky users</span>
        <span className="number">{data ? data[0]?.risky_order ?? 0 : '--'}</span>
      </div>
      <VerticalPartition />
      <div className="order-count">
        <span className="safe-color" />
        <span className="title safe">Safe users</span>
        <span className="number">{data ? data[0]?.safe_order ?? 0 : '--'}</span>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  data: state.magicRTOAnalytics.order_split_cumulative.data,
});

export default connect(mapStateToProps)(CumulativeOrders);
