import { notifyError } from 'common/modal';
import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';
import { SwitchField } from 'ui/Field';

import { rexFetch } from 'admin/razorx/fetch';
import { AppStore } from 'admin/user';

export default class MerchantEvaluation extends React.Component {
  state = { evaluation_result: null };

  evaluateMerchant = filters => {
    const payload = {
      url: 'evaluate',
      params: {
        id: filters.merchant_id,
        feature_flag: filters.feature_flag,
        environment: filters.environment,
      },
    };

    return rexFetch(payload).then(data => {
      if (data) {
        this.setState({ evaluation_result: data.result });
      }
    });
  };

  render() {
    return (
      <div class="parent-container features-container">
        <div class="header">
          <span class="title">Merchant Evaluation</span>
          <SwitchField
            name="mode"
            defaultValue={AppStore.mode}
            disabledLabel="Test"
            enabledLabel="Live"
            enabledValue="live"
            disabledValue="test"
            onChange={AppStore.updateMode}
          />
        </div>
        <div class="container-group">
          <div class="list-container">
            <Form onSubmit={this.evaluateMerchant} class="filters">
              <Field name="merchant_id" label="Merchant Id" />
              <Field name="feature_flag" label="Feature Name" />

              <SelectField name="environment" label="Environment">
                <option value="production">Production</option>
                <option value="beta">Beta</option>
              </SelectField>
              <button class="btn btn--primary field">Search</button>
            </Form>
            <div>
              <b>Result:</b>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
