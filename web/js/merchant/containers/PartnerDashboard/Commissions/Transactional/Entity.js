import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import Spinner from 'rzp/ui/Spinner';
import Time from 'rzp/ui/Time';
import Definition from 'rzp/ui/Definition';
import Amount from 'rzp/ui/Amount';
import DualBreakup from 'rzp/ui/FeeBreakup';
import Alert from 'rzp/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

import { fetchCommission } from 'merchant/modules/commission';
import { isPresent, capitalize } from 'rzp/utils/rzp-utils';

@withRouter
@connect(
  state => ({
    ...state.commission,
  }),
  { fetchCommission }
)
export default class CommissionEntityContainer extends Component {
  componentWillMount() {
    this.props.fetchCommission(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchCommission(nextProps.id);
    }
  }

  render() {
    const { loading: isLoading, entity, error } = this.props;
    const source = entity.source || {};
    return (
      <div class="content-wrapper content-sm txn-details Commission--Detail">
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">Commission Id: {entity.id}</div>
            <Alert type="error" message={error} />
            {isPresent(entity) && (
              <div class="SliderPanel__Body">
                <div class="panel-body">
                  <div class="list-group details-row-container">
                    {/* earnings breakup */}
                    <CommissionEarningBreakUp
                      currency={entity.currency}
                      total={entity.credit}
                      gst={entity.tax}
                      base={entity.credit - entity.tax}
                    />
                    {entity.source_type === 'payment' && (
                      <>
                        <div className="sub-heading">
                          <strong>Payment Details</strong>
                        </div>

                        <EntityDetailRow label="Affiliated Account">
                          <Definition>
                            <>{entity.merchant.name}</>
                            <>{entity.merchant.id}</>
                          </Definition>
                        </EntityDetailRow>

                        <EntityDetailRow label="Amount">
                          <Amount
                            value={source.amount}
                            currency={source.currency}
                          />
                        </EntityDetailRow>

                        <EntityDetailRow label="ID" value={source.id} />

                        <EntityDetailRow label="Created At">
                          <Time value={source.created_at} format="ll" />
                        </EntityDetailRow>
                      </>
                    )}
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

function CommissionEarningBreakUp(props) {
  return (
    <>
      <div class="sub-heading">
        <strong>Earnings from Razorpay</strong>
      </div>
      <div class="pair-group-item vertical">
        <div class="pair-value">
          <DualBreakup>
            <>
              <div class="EarningsBreakup--Total">
                <Amount value={props.total} currency={props.currency} />
              </div>
              <small>Total Earnings</small>
            </>
            <>
              <div class="EarningsBreakup--Components">
                <Amount value={props.base} currency={props.currency} />
              </div>
              <small>Base</small>
            </>
            <>
              <div class="EarningsBreakup--Components">
                <Amount value={props.gst} currency={props.currency} />
              </div>
              <small>GST</small>
            </>
          </DualBreakup>
        </div>
      </div>
    </>
  );
}
