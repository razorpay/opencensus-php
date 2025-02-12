import React from 'react';

import { notifyError } from 'razorx/components/Modal';
import Field, { SearchableSelectField } from 'razorx/components/ui/Field';
import Form from 'razorx/components/ui/Form';
import { splitzFetch } from 'razorx/helpers/fetch';

export default class ExperimentTester extends React.Component {
  state = {
    experiments: [],
    isFetchingExperiments: true,
    isFetching: false,
    selectedExperiment: null,
    evaluationResult: null,
    queryData: [
      {
        key: '',
        value: '',
      },
    ],
  };

  evaluateMerchant = (form) => {
    if (!this.state.selectedExperiment) {
      return null;
    }

    this.setState({
      evaluationResult: null,
      isFetching: true,
    });

    const queryData = {};

    this.state.queryData.forEach((data) => {
      queryData[data.key] = data.value;
    });

    return splitzFetch({
      url: 'evaluate.v1.EvaluateAPI/Evaluate',
      data: {
        id: form.request_id,
        experiment_id: this.state.selectedExperiment.id,
        request_data: JSON.stringify(queryData),
      },
    })
      .then((res) => {
        this.setState({
          isFetching: false,
          evaluationResult: res,
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

  componentDidMount() {
    // this.evaluateMerchant();

    splitzFetch({
      url: 'experiment.v1.ExperimentAPI/List',
      data: {
        limit: 10000,
        offset: 0,
      },
    })
      .then((experimentRes) => {
        this.setState({
          isFetchingExperiments: false,
          experiments: experimentRes.items,
        });
      })
      .catch(() => {
        this.setState({
          isFetchingExperiments: false,
        });
      });
  }

  handleSelectExperiment = ({ option }) => {
    this.setState({ selectedExperiment: option });
  };

  render() {
    const {
      isFetchingExperiments,
      isFetching,
      experiments,
      selectedExperiment,
      evaluationResult,
      queryData,
    } = this.state;
    let result;

    if (isFetchingExperiments || isFetching) {
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
      const isSampler = evaluationResult.steps.includes('sampler');
      const isExclusion = evaluationResult.steps.includes('exclusion');
      const isAudience = evaluationResult.steps.includes('audience');
      const isAssignBucket = evaluationResult.steps.includes('assign_bucket');
      const isWhitelisting = evaluationResult.steps.includes('whitelisting');

      result = (
        <div className="flex-column">
          <div>
            <div className="flex-row-item">
              <div className="label">Steps</div>
            </div>
            {/* {evaluationResult.steps.map((step) => (
                  <span className="square-pills label-semi-muted" key={step}>
                    {step}
                  </span>
                ))} */}
            <div
              className="flex-row"
              style={{ alignItems: 'center', opacity: isSampler ? 1 : 0.35 }}
            >
              <i
                style={{ height: '14px', width: '14px' }}
                className={`fa fa-${isSampler ? 'check' : 'times'}`}
              />
              <span style={{ paddingLeft: '8px' }}>Sampler</span>
            </div>
            <div
              className="flex-row"
              style={{ alignItems: 'center', opacity: isExclusion ? 1 : 0.35 }}
            >
              <i
                style={{ height: '14px', width: '14px' }}
                className={`fa fa-${isExclusion ? 'check' : 'times'}`}
              />
              <span style={{ paddingLeft: '8px' }}>Exclusion</span>
            </div>
            <div
              className="flex-row"
              style={{ alignItems: 'center', opacity: isAudience ? 1 : 0.35 }}
            >
              <i
                style={{ height: '14px', width: '14px' }}
                className={`fa fa-${isAudience ? 'check' : 'times'}`}
              />
              <span style={{ paddingLeft: '8px' }}>Audience</span>
            </div>
            <div
              className="flex-row"
              style={{ alignItems: 'center', opacity: isAssignBucket ? 1 : 0.35 }}
            >
              <i
                style={{ height: '14px', width: '14px' }}
                className={`fa fa-${isAssignBucket ? 'check' : 'times'}`}
              />
              <span style={{ paddingLeft: '8px' }}>Bucket Assigned</span>
            </div>
            <div
              className="flex-row"
              style={{ alignItems: 'center', opacity: isWhitelisting ? 1 : 0.35 }}
            >
              <i
                style={{ height: '14px', width: '14px' }}
                className={`fa fa-${isWhitelisting ? 'check' : 'times'}`}
              />
              <span style={{ paddingLeft: '8px' }}>Whitelisting</span>
            </div>
          </div>
          {evaluationResult.Reason ? (
            <div>
              <br />
              <br />
              <div className="flex-row-item">
                <div className="label">Reason</div>
              </div>
              <div className="flex-row">
                <div className="flex-row-item">{evaluationResult.Reason}</div>
              </div>
            </div>
          ) : null}
          {evaluationResult.variant ? (
            <div>
              <br />
              <br />
              <div className="label">Variant</div>
              <div className="segment pad-highlight" style={{ width: '25%' }}>
                <div>
                  <span className="square-pills label-semi-muted">
                    {evaluationResult.variant.name}
                  </span>
                </div>
                <div className="sub-segment" style={{ paddingLeft: '13px' }}>
                  {evaluationResult.variant.variables.map((variable, i) => (
                    <div key={i}>
                      <span className="label">{variable.key}: &nbsp;</span>
                      <span className="sub-segment-group">{variable.value}</span>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          ) : null}
        </div>
      );
    }

    return (
      <div className="parent-container merchant-evaluation-container">
        <div className="header">
          <span className="title">Experiment Tester</span>
        </div>
        <div className="container-group">
          <div className="list-container">
            <Form onSubmit={this.evaluateMerchant} className="filters">
              <div className="flex-column">
                <div className="flex-row" style={{ alignItems: 'flex-end' }}>
                  <Field name="request_id" label="Entity ID" />
                  <SearchableSelectField
                    disabled={isFetchingExperiments}
                    optionComponent={({ option }) => (
                      <div>
                        {option.name} - {option.id}
                      </div>
                    )}
                    placeholder="Select an experiment"
                    searchIndices={['id', 'name']}
                    label="Select Experiment"
                    trackBy="id"
                    options={experiments || []}
                    selected={selectedExperiment}
                    onChange={this.handleSelectExperiment}
                    style={{ width: '250px' }}
                  />
                  <button className="btn btn--primary field">Evaluate</button>
                </div>
                <div className="label" style={{ fontSize: '12px', fontWeight: 600, opacity: 0.6 }}>
                  REQUEST DATA
                </div>
                {queryData.map((data, index) => (
                  <React.Fragment key={index}>
                    <div className="flex-row" style={{ alignItems: 'center' }}>
                      <input
                        type="text"
                        placeholder="Key"
                        value={data.key}
                        onChange={(e) => {
                          const newData = [...queryData];
                          newData[index].key = e.target.value;
                          this.setState({
                            queryData: newData,
                          });
                        }}
                      />
                      <input
                        type="text"
                        placeholder="Value"
                        value={data.value}
                        onChange={(e) => {
                          const newData = [...queryData];
                          newData[index].value = e.target.value;
                          this.setState({
                            queryData: newData,
                          });
                        }}
                      />
                      <span
                        style={{
                          paddingLeft: '8px',
                          fontSize: '20px',
                        }}
                        className="cross"
                        onClick={() => {
                          this.setState({
                            queryData: queryData.filter((d, i) => i !== index),
                          });
                        }}
                      />
                    </div>
                  </React.Fragment>
                ))}
                <div className="flex-row">
                  <button
                    type="button"
                    className="btn btn--pill"
                    style={{ color: 'grey', marginTop: '10px' }}
                    onClick={() => {
                      this.setState({
                        queryData: [
                          ...queryData,
                          {
                            key: '',
                            value: '',
                          },
                        ],
                      });
                    }}
                  >
                    + Add Key/Value
                  </button>
                </div>
              </div>
            </Form>
            <div className="entity-container" style={{ width: '100%' }}>
              <div className="entity-details">
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
