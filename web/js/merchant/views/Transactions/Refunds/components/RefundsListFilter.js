import React, { useState } from 'react';
import { connect } from 'react-redux';
import { Field } from 'redux-form';

import DateRangePicker from 'common/ui/DateRangePicker';
import ListFilter from 'merchant/components/ListFilter';
import ProviderSelector from 'merchant/components/ProviderSelector';
import { handleChangeTrack } from 'merchant/views/Transactions/AnalyticsTrack';

const dateRangePresets = [
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
];

const publicStatusOptions = [
  {
    label: 'All',
    value: '',
  },
  {
    label: 'Processed',
    value: 'processed',
  },
  {
    label: 'Processing',
    value: 'processing',
  },
  {
    label: 'Failed',
    value: 'failed',
  },
];

const track = handleChangeTrack('refund');

const RefundListFilter = (props) => {
  const [provider, setProvider] = useState({ name: 'All' });
  const [date, setDate] = useState({ from: '', to: '' });
  const onDatesChange = (from, to) => {
    track({ type: 'filter', args: [] });
    setDate({
      from: from.unix(),
      to: to.unix(),
    });
  };

  const onPublicStatusChange = (...args) => {
    track({ type: 'filter', args });
  };

  const { user, terminalProviders } = props;
  return (
    <ListFilter date={date} provider={provider} setProvider={setProvider} {...props}>
      <div className="form-group list-filter-item">
        <label>Refund Id</label>
        <Field name="id" component="input" className="form-control input-sm" />
      </div>

      <div className="form-group datepicker-group">
        <label>Duration</label>
        <DateRangePicker
          defaultPreset={0}
          presets={dateRangePresets}
          onDatesChange={onDatesChange}
        />
      </div>

      <div className="form-group list-filter-item">
        <label>Payment Id</label>
        <Field name="payment_id" component="input" className="form-control input-sm" />
      </div>
      {props.rs_filter ? (
        <div className="form-group list-filter-item">
          <label>Status</label>
          <Field
            name="public_status"
            component="select"
            className="form-control input-sm"
            onChange={onPublicStatusChange}
          >
            {publicStatusOptions.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </Field>
        </div>
      ) : null}

      {user?.isSingleReconEnabled && user?.isOptimizerEnabled && terminalProviders?.length > 0 ? (
        <div className="form-group list-filter-item">
          <label>Processed by</label>
          <ProviderSelector
            name="provider"
            providers={terminalProviders}
            provider={provider}
            setProvider={setProvider}
          />
        </div>
      ) : null}

      <div className="form-group list-filter-item">
        <label>Notes</label>
        <Field name="notes" component="input" className="form-control input-sm" />
      </div>

      <div className="form-group list-filter-item count">
        <label>Count</label>
        <Field
          name="count"
          component="input"
          min={1}
          max={100}
          type="number"
          className="form-control input-sm"
        />
      </div>
    </ListFilter>
  );
};

const mapStateToProps = (state) => {
  const { config, session, navigator } = state;
  return {
    rs_filter: config.config.rs_filter,
    user: session.user,
    terminalProviders: navigator.terminalProviders,
  };
};

export default connect(mapStateToProps, null)(RefundListFilter);
