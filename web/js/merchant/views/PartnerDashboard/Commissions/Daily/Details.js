import { Component } from 'react';
import { connect } from 'react-redux';

import Amount from 'common/ui/Amount';
import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';
import FeeBreakup from 'common/ui/FeeBreakup';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

import { isPresent } from 'common/utils/rzp-utils';
import { fetchSingleDayAggregate } from 'merchant/reducers/commission';

@connect(state => ({ ...state.commAggSingleDay }), { fetchSingleDayAggregate })
export default class CommissionsDailyEntity extends Component {
  UNSAFE_componentWillMount() {
    this.fetchData(Number(this.props.timestamp));
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.timestamp !== nextProps.timestamp) {
      this.fetchData(Number(nextProps.timestamp));
    }
  }

  fetchData(timestamp) {
    this.props.fetchSingleDayAggregate(timestamp, this.props.queryType);
  }

  render() {
    const {
      loading: isLoading,
      entity,
      error,
      renderBreakups,
      ...props
    } = this.props;
    const data = entity.data;
    return (
      <div class="content-wrapper content-sm txn-details Commission--Detail">
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              Date <strong>{moment(props.timestamp, 'X').format('ll')}</strong>
            </div>
            <Alert type="error" message={error} />
            {isPresent(entity) && (
              <div class="SliderPanel__Body">
                <div class="panel-body">
                  <div class="list-group details-row-container">
                    <div class="sub-heading">
                      <strong>{props.subHeading} </strong>
                    </div>

                    {/* provide all props to renderBreakup */}
                    {renderBreakups(this.props)}

                    <div class="sub-heading">
                      <strong>Transactions</strong>
                    </div>

                    <EntityDetailRow label="Total Transaction Amount">
                      <Amount value={data.transactionVolume} currency={'INR'} />
                    </EntityDetailRow>

                    <EntityDetailRow
                      label="No. of transacting merchants"
                      value={data.activeMerchants}
                    />

                    <EntityDetailRow
                      label="No. of Transactions"
                      value={data.transactions}
                    />
                  </div>
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    );
  }
}

export function EarningsBreakup(props) {
  return (
    <div class="pair-group-item vertical">
      <div class="pair-label">{props.label}</div>
      <div class="pair-value EarningsBreakup">
        <FeeBreakup type={props.feeBreakupType}>
          <>
            <div class="EarningsBreakup--Total">
              <Amount value={props.value} currency={'INR'} />
            </div>
            <small>Total</small>
          </>
          <>
            <div class="EarningsBreakup--Components">
              <Amount value={props.value - props.tax} currency={'INR'} />
            </div>
            <small>{props.label}</small>
          </>
          <>
            <div class="EarningsBreakup--Components">
              <Amount value={props.tax} currency={'INR'} />
            </div>
            <small>GST</small>
          </>
        </FeeBreakup>
      </div>
    </div>
  );
}
