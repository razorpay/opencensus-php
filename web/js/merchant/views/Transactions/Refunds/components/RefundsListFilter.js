import React from 'react';
import ListFilter from 'merchant/components/ListFilter';
import { Field } from 'redux-form';
import { connect } from 'react-redux';
import ProviderSelector from 'merchant/components/ProviderSelector';

class RefundListFilter extends React.Component {
  state = {
    provider: { name: 'All' },
  };

  setProvider = (provider) => {
    this.setState({ provider });
  };

  render() {
    const { provider } = this.state;
    const { user, terminalProviders } = this.props;

    return (
      <ListFilter provider={provider} setProvider={this.setProvider} {...this.props}>
        <div class="form-group list-filter-item">
          <label>Refund Id</label>
          <Field name="id" component="input" class="form-control input-sm" />
        </div>

        <div class="form-group list-filter-item">
          <label>Payment Id</label>
          <Field name="payment_id" component="input" class="form-control input-sm" />
        </div>
        {this.props.rs_filter ? (
          <div class="form-group list-filter-item">
            <label>Status</label>
            <Field name="public_status" component="select" class="form-control input-sm">
              <option value="">All</option>
              <option value="processed">Processed</option>
              <option value="processing">Processing</option>
              <option value="failed">Failed</option>
            </Field>
          </div>
        ) : null}

        {user?.isSingleReconEnabled && user?.isOptimizerEnabled && terminalProviders?.length > 0 && (
          <div className="form-group list-filter-item">
            <label>Processed by</label>
            <ProviderSelector
              name="provider"
              providers={terminalProviders}
              provider={provider}
              setProvider={this.setProvider}
            />
          </div>
        )}

        <div class="form-group list-filter-item">
          <label>Notes</label>
          <Field name="notes" component="input" class="form-control input-sm" />
        </div>

        <div class="form-group list-filter-item count">
          <label>Count</label>
          <Field
            name="count"
            component="input"
            min={1}
            max={100}
            type="number"
            class="form-control input-sm"
          />
        </div>
      </ListFilter>
    );
  }
}

const mapStateToProps = (state) => {
  const { config, session, navigator } = state;
  return {
    rs_filter: config.config.rs_filter,
    user: session.user,
    terminalProviders: navigator.terminalProviders,
  };
};

export default connect(mapStateToProps, null)(RefundListFilter);
