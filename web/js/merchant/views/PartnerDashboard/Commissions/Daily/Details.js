import { Component } from 'react';
import moment from 'moment';
import { connect } from 'react-redux';

import { withRouter } from 'common/deprecated/withRouter';
import Amount from 'common/ui/Amount';
import FeeBreakup from 'common/ui/FeeBreakup';
import Alert from 'common/ui/Forms/Alert';
import Spinner from 'common/ui/Spinner';
import { isPresent } from 'common/utils/rzp-utils';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { fetchSingleDayAggregate } from 'merchant/reducers/commission';

@connect((state) => ({ ...state.commAggSingleDay, user: state?.session?.user }), {
  fetchSingleDayAggregate,
})
class CommissionsDailyEntity extends Component {
  componentDidMount() {
    this.fetchData(Number(this.props.timestamp));
  }

  componentDidUpdate(prevProps) {
    if (this.props.timestamp !== prevProps.timestamp) {
      this.fetchData(Number(this.props.timestamp));
    }
  }

  fetchData(timestamp) {
    this.props.fetchSingleDayAggregate(timestamp, this.props.queryType);
  }

  render() {
    const { loading: isLoading, entity, error, renderBreakups, user, ...props } = this.props;
    const currency = user.merchant.currency;
    const data = entity.data;
    return (
      <div
        class="content-wrapper content-sm txn-details Commission--Detail"
        data-testid="daily-details-panel"
      >
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
                      <Amount
                        value={data.transactionVolume}
                        currency={currency}
                        testId="amount-daily-details"
                      />
                    </EntityDetailRow>

                    <EntityDetailRow
                      label="No. of transacting merchants"
                      value={data.activeMerchants}
                    />

                    <EntityDetailRow label="No. of Transactions" value={data.transactions} />
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
              <Amount value={props.value} currency={props.currency} />
            </div>
            <small>Total</small>
          </>
          <>
            <div class="EarningsBreakup--Components">
              <Amount value={props.value - props.tax} currency={props.currency} />
            </div>
            <small>{props.label}</small>
          </>
          <>
            <div class="EarningsBreakup--Components">
              <Amount value={props.tax} currency={props.currency} />
            </div>
            <small>{props.isRzpOrg ? 'GST' : 'Tax'}</small>
          </>
        </FeeBreakup>
      </div>
    </div>
  );
}

export default withRouter(CommissionsDailyEntity);
