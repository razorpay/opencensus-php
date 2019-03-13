import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import Spinner from 'rzp/ui/Spinner';
import Time from 'rzp/ui/Time';
import Definition from 'rzp/ui/Definition';
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
    return (
      <div class="content-wrapper content-sm txn-details">
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
                    {/* merchant details */}
                    <EntityDetailRow label="Affiliate Account">
                      <Definition>
                        <>{entity.merchant.name}</>
                        <>{entity.merchant.id}</>
                      </Definition>
                    </EntityDetailRow>

                    <div className="pair-group-item">
                      <strong>Transactions</strong>
                    </div>

                    <EntityDetailRow
                      label="Type"
                      value={capitalize(entity.source_type)}
                    />

                    <EntityDetailRow label="Created At">
                      <Time value={entity.created_at} format="ll" />
                    </EntityDetailRow>
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
