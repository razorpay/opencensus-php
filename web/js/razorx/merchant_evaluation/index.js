import { notifyError } from 'common/modal';
import Form from 'razorx/components/ui/Form';
import Field, { SelectField, SwitchField } from 'razorx/components/ui/Field';

import { rexFetch } from 'razorx/fetch';
import { AppStore } from 'razorx/store';

export default class MerchantEvaluation extends React.Component {
  state = { evaluationResult: null };

  evaluateMerchant = filters => {
    this.setState({
      evaluationResult: null,
      isFetching: true,
    });

    const payload = {
      url: 'evaluate',
      params: {
        id: filters.merchant_id,
        feature_flag: filters.feature_flag,
        environment: filters.environment,
      },
    };

    return rexFetch(payload)
      .then(res => {
        this.setState({
          isFetching: false,
          evaluationResult: res ? res.value : false,
        });
      })
      .catch(({ errors = ['Some network error'] }) => {
        this.setState({
          isFetching: false,
          evaluationResult: false,
        });

        notifyError(errors[0]);
      });
  };

  render() {
    let { isFetching, evaluationResult } = this.state;
    let result;

    if (isFetching) {
      result = <span class="dot-loader">.</span>;
    } else if (evaluationResult === false) {
      result = (
        <span>
          Oh Snap! <br /> No Results
        </span>
      );
    } else if (evaluationResult === null) {
      result = <span>Evaluate Something!</span>;
    } else if (evaluationResult) {
      result = <span class="highlight">{evaluationResult}</span>;
    }

    return (
      <div class="parent-container merchant-evaluation-container">
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
                {AppStore.environmentList.map((e, i) => (
                  <option key={i} value={e}>
                    {e}
                  </option>
                ))}
              </SelectField>
              <button class="btn btn--primary field">Evaluate</button>
            </Form>
            <div class="evaluation-result">
              <div>
                <i class="i i-flask" />
                <div class="title">RESULT</div>
                {result}
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
