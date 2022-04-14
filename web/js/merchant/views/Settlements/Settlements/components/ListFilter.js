import { useState } from 'react';
import { Field } from 'redux-form';
import ListFilter from 'merchant/components/ListFilter';
import ProviderSelector from 'merchant/components/ProviderSelector';

export default (props) => {
  const [provider, setProvider] = useState({ name: 'All' });

  const { user, terminalProviders } = props;

  return (
    <ListFilter provider={provider} setProvider={setProvider} {...props}>
      <div className="form-group list-filter-item">
        <label>Settlement Id</label>
        <Field name="id" component="input" className="form-control input-sm" />
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

      {user?.isSingleReconEnabled && user?.isOptimizerEnabled && terminalProviders?.length > 0 && (
        <div className="form-group list-filter-item">
          <label>Payment Provider</label>
          <ProviderSelector
            name="provider"
            providers={terminalProviders}
            provider={provider}
            setProvider={setProvider}
          />
        </div>
      )}
    </ListFilter>
  );
};
