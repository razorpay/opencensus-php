import React from "react";
import { notifyError } from 'razorx/components/Modal';
import Form from 'razorx/components/ui/Form';
import Field, { SelectField, SwitchField } from 'razorx/components/ui/Field';

import { rexFetch } from 'razorx/helpers/fetch';
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
      result = <span className="dot-loader">.</span>;
    } else if (evaluationResult === false) {
      result = (
        <span>
          Oh Snap! <br /> No Results
        </span>
      );
    } else if (evaluationResult === null) {
      result = <span>Evaluate Something!</span>;
    } else if (evaluationResult) {
      result = <span className="highlight">{evaluationResult}</span>;
    }

    return (
      <div className="parent-container merchant-evaluation-container">
        <div className="header">
          <span className="title">Merchant Evaluation</span>
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
        <div className="container-group">
          <div className="list-container">
            <Form onSubmit={this.evaluateMerchant} className="filters">
              <Field name="merchant_id" label="Merchant Id" />
              <Field name="feature_flag" label="Feature Name" />

              <SelectField name="environment" label="Environment">
                {AppStore.environmentList.map((e, i) => (
                  <option key={i} value={e}>
                    {e}
                  </option>
                ))}
              </SelectField>
              <button className="btn btn--primary field">Evaluate</button>
            </Form>
            <div className="evaluation-result">
              <div>
                <i className="i i-flask" />
                <div className="title">RESULT</div>
                {result}
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
