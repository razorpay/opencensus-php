import { Component } from 'react';

import Amount from 'rzp/ui/Amount';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import FeeBreakup from 'rzp/ui/FeeBreakup';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

import { isPresent } from 'rzp/utils/rzp-utils';

export default class CommissionsDailyEntity extends Component {
  render() {
    const { loading: isLoading, entity, error } = this.props;
    return (
      <div class="content-wrapper content-sm txn-details Commission--Detail">
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              {/* Date <strong>{moment(entity.date, 'X').format('ll')}</strong> */}
              Date <strong>{moment('1554731556', 'X').format('ll')}</strong>
            </div>
            <Alert type="error" message={error} />
            {(true || isPresent(entity)) && (
              <div class="SliderPanel__Body">
                <div class="panel-body">
                  <div class="list-group details-row-container">
                    <div className="pair-group-item">
                      <strong>Earnings</strong>
                    </div>
                    <div className="pair-group-item">
                      <div className="pair-label">Total Earnings</div>
                    </div>
                    <div className="pair-group-item">
                      <div className="pair-value">
                        <strong>
                          <Amount value={1300} currency={'INR'} />
                        </strong>
                      </div>
                    </div>

                    <BaseEarningsBreakup earnings={12000} tax={1800} />

                    <AddOnEarningsBreakup addOnEarnings={1200} tax={200} />

                    <div className="pair-group-item m-t">
                      <strong>Transactions</strong>
                    </div>

                    <EntityDetailRow label="Total Volume">
                      <Amount value={2374500} currency={'INR'} />
                    </EntityDetailRow>

                    <EntityDetailRow
                      label="No. of transacting merchants"
                      value={5}
                    />

                    <EntityDetailRow label="No. of Transactions" value={12} />
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
    />
  );
}

function AddOnEarningsBreakup(props) {
  return (
    <EarningsBreakup
      label="Add-on Earnings"
      value={props.addOnEarnings}
      tax={props.tax}
    />
  );
}

function EarningsBreakup(props) {
  return (
    <>
      <div class="pair-group-item">
        <div class="pair-label">{props.label}</div>
      </div>
      <div class="pair-group-item EarningsBreakup">
        <FeeBreakup>
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
    </>
  );
}
