import { Component } from 'react';
import { connect } from 'react-redux';

import Amount from 'rzp/ui/Amount';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import FeeBreakup from 'rzp/ui/FeeBreakup';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

import { isPresent } from 'rzp/utils/rzp-utils';
import { fetchSingleDayAggregate } from 'merchant/modules/commission';

import VerticalBreakup from './VerticalBreakup';

@connect(state => ({ ...state.commAggSingleDay }), { fetchSingleDayAggregate })
export default class CommissionsDailyEntity extends Component {
  componentWillMount() {
    this.props.fetchSingleDayAggregate(Number(this.props.timestamp));
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.timestamp !== nextProps.timestamp) {
      this.props.fetchSingleDayAggregate(Number(nextProps.timestamp));
    }
  }

  render() {
    const { loading: isLoading, entity, error } = this.props;
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
              Date{' '}
              <strong>{moment(this.props.timestamp, 'X').format('ll')}</strong>
            </div>
            <Alert type="error" message={error} />
            {isPresent(entity) && (
              <div class="SliderPanel__Body">
                <div class="panel-body">
                  <div class="list-group details-row-container">
                    <div class="sub-heading">
                      <strong>Earnings</strong>
                    </div>
                    <VerticalBreakup>
                      <div class="pair-group-item vertical">
                        <div class="pair-label">Total Earnings</div>
                        <div class="pair-value font-lg">
                          <strong>
                            <Amount
                              value={getTotalEarnings(data)}
                              currency={'INR'}
                            />
                          </strong>
                        </div>
                      </div>

                      <BaseEarningsBreakup
                        earnings={data.baseEarnings}
                        tax={data.baseTax}
                      />

                      <AddOnEarningsBreakup
                        addOnEarnings={data.addonEarnings}
                        tax={data.addonTax}
                      />
                    </VerticalBreakup>

                    <div class="sub-heading">
                      <strong>Transactions</strong>
                    </div>

                    <EntityDetailRow label="Total Volume">
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

function BaseEarningsBreakup(props) {
  return (
    <EarningsBreakup
      label="Base Earnings"
      value={props.earnings}
      tax={props.tax}
      feeBreakupType="primary"
    />
  );
}

function AddOnEarningsBreakup(props) {
  return (
    <EarningsBreakup
      label="Add-on Earnings"
      value={props.addOnEarnings}
      tax={props.tax}
      feeBreakupType="warning"
    />
  );
}

function EarningsBreakup(props) {
  return (
    <div class="pair-group-item vertical">
      <div class="pair-label">{props.label}</div>
      <div class="pair-value EarningsBreakup">
        <FeeBreakup type={props.feeBreakupType}>
          <>
            <div class="EarningsBreakup--Total">
              <Amount value={props.value + props.tax} currency={'INR'} />
            </div>
            <small>Total</small>
          </>
          <>
            <div class="EarningsBreakup--Components">
              <Amount value={props.value} currency={'INR'} />
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

function getTotalEarnings(data) {
  return data.addonEarnings + data.addonTax + data.baseEarnings + data.baseTax;
}
