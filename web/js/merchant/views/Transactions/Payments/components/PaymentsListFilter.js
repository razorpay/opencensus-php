import ListFilter from 'merchant/components/ListFilter';
import DateRangePicker from 'common/ui/DateRangePicker';
import { Field } from 'redux-form';
import { useState } from 'react';
import ProviderSelector from 'merchant/components/ProviderSelector';
import { handleChangeTrack } from 'merchant/views/Transactions/AnalyticsTrack';
import ShowWhen from 'merchant/components/ShowWhen';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';

const dateRangePresets = [
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
];
const track = handleChangeTrack('payment');

export default ({ showBatchIdFilter, ...props }) => {
  const [date, setDate] = useState({ from: '', to: '' });
  const onDatesChange = (from, to) => {
    track({ type: 'filter', args: [] });
    setDate({
      from: from.unix(),
      to: to.unix(),
    });
  };

  const [provider, setProvider] = useState({ name: 'All', value: '', gateway: '' });

  return (
    <ListFilter date={date} provider={provider} setProvider={setProvider} {...props}>
      <div class="form-group list-filter-item">
        <label>Payment Id</label>
        <Field
          name="id"
          component="input"
          class="form-control input-sm"
          onChange={(...args) => {
            track({ type: 'search', args });
          }}
        />
      </div>

      <div className="form-group datepicker-group">
        <label>Duration</label>
        {/* For m-web we want to show only one month to support mweb view */}
        <DateRangePicker presets={dateRangePresets} onDatesChange={onDatesChange} />
      </div>

      {/* used in emndate payments */}
      {showBatchIdFilter && (
        <div class="form-group list-filter-item">
          <label>Batch Id</label>
          <Field
            name="batch_id"
            component="input"
            class="form-control input-sm"
            onChange={(...args) => {
              track({ type: 'search', args });
            }}
          />
        </div>
      )}

      <div class="form-group list-filter-item">
        <label>Status</label>
        <Field
          name="status"
          component="select"
          class="form-control input-sm"
          onChange={(...args) => {
            track({ type: 'filter', args });
          }}
        >
          <option value="">All</option>
          <option value="authorized">Authorized</option>
          <option value="captured">Captured</option>
          <option value="refunded">Refunded</option>
          <option value="failed">Failed</option>
        </Field>
      </div>

      <div class="form-group list-filter-item">
        <label>Email</label>
        <Field
          name="email"
          component="input"
          type="email"
          class="form-control input-sm"
          onChange={(...args) => {
            track({ type: 'search', args });
          }}
        />
      </div>

      {props.user?.isSingleReconEnabled &&
        props.user?.isOptimizerEnabled &&
        props.terminalProviders &&
        props.terminalProviders.length > 0 && (
          <div className="form-group list-filter-item">
            <label>Processed by</label>
            <ProviderSelector
              name="terminal_id"
              providers={props.terminalProviders}
              provider={provider}
              setProvider={setProvider}
            />
          </div>
        )}

      <div class="form-group list-filter-item">
        <label>Notes</label>
        <Field
          name="notes"
          component="input"
          class="form-control input-sm"
          onChange={(...args) => {
            track({ type: 'search', args });
          }}
        />
      </div>

      <ShowWhen
        additionalCondition={(usr) =>
          !usr.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.BankReferenceId)
        }
      >
        <div class="form-group list-filter-item">
          <label>Bank Reference Number</label>
          <Field
            name="va_transaction_id"
            component="input"
            class="form-control input-sm"
            onChange={(...args) => {
              track({ type: 'search', args });
            }}
          />
        </div>
      </ShowWhen>

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

      {props.addonAfter}
    </ListFilter>
  );
};
