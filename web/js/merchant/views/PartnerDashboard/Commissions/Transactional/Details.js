import { Component } from 'react';
import { connect } from 'react-redux';

import Spinner from 'common/ui/Spinner';
import Time from 'common/ui/Time';
import Definition from 'common/ui/Definition';
import Amount from 'common/ui/Amount';
import DualBreakup from 'common/ui/FeeBreakup';
import Alert from 'common/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

import { fetchCommission } from 'merchant/reducers/commission';
import { isPresent } from 'common/utils/rzp-utils';

@connect(
  state => ({
    ...state.commission,
  }),
  { fetchCommission }
)
export default class CommissionEntityContainer extends Component {
  UNSAFE_componentWillMount() {
    this.props.fetchCommission(this.props.id);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchCommission(nextProps.id);
    }
  }

  render() {
    const { loading: isLoading, entity, error, renderDetails } = this.props;
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
                    {renderDetails(entity)}

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

export function CommissionEarningBreakUp(props) {
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
