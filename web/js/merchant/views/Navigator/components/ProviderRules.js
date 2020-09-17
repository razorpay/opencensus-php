import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import { Fragment } from 'react';
import Input, { Description, Label } from 'common/new-ui/Input';
import Field, {
  TextAreaField,
  SwitchField,
  SelectField,
  SearchableSelectField,
} from 'razorx/components/ui/Field';
import { PowerSelect } from 'react-power-select';

import { titleCase } from 'common/utils/rzp-utils';
import Select from './Select';
import SelectConfig from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/SelectConfig';
import ProviderRow from './ProviderRow';

export default class ProviderRules extends React.Component {
  render() {
    const rules = this.props.rules;
    return (
      <Fragment>
        {this.props.readonly ? (
          <div class="panel-body">
            <div className="precondition-div">
              <div className="row">
                <div className={`col-xs-${this.props.parent === 'create-rule' ? 1 : 2}`}>
                  {Object.keys(rules).map((pp, indexM) => {
                    return (
                      <Fragment>
                        <div
                          key={indexM}
                          style={{
                            position: 'relative',
                          }}
                        >
                          {rules[pp].map((r, ind) => {
                            return (
                              <div
                                style={{
                                  height: this.props.openedFrom == 'rule-detail' ? '56px' : '44px',
                                }}
                                className="row"
                                key={ind}
                              >
                                <div className="col-xs-12" />
                              </div>
                            );
                          })}
                          {this.props.readonly ? (
                            <button
                              style={{
                                left: !this.props.readonly ? 'auto' : '20px',
                                width: '80px',
                              }}
                              className={`btn btn-primary operator-btn priority-btn readonly`}
                            >
                              PRIORITY {pp}
                            </button>
                          ) : null}
                        </div>
                        <div
                          style={{
                            height: this.props.openedFrom == 'rule-detail' ? '56px' : '44px',
                          }}
                        />
                      </Fragment>
                    );
                  })}
                </div>
                <div
                  style={{
                    paddingLeft:
                      this.props.readonly && this.props.parent == 'create-rule' ? '30px' : 'auto',
                  }}
                  className={`col-xs-${
                    this.props.readonly ? (this.props.parent === 'create-rule' ? 11 : 10) : 10
                  }`}
                >
                  <div className="row">
                    <div className="col-xs-12">
                      {Object.keys(rules).map((pp, indexT) => {
                        return (
                          <Fragment key={indexT}>
                            {rules[pp].map((rule, index) => {
                              return (
                                <ProviderRow
                                  providers={this.props.providers}
                                  dashed={index == rules[pp].length - 1}
                                  key={index}
                                  readonly={this.props.readonly}
                                  rule={rule}
                                />
                              );
                            })}
                            {rules[Number(pp) + 1] ? (
                              <div class="expression-row expression-row-readonly dashed if-tran-exp">
                                <div className="row">
                                  <div className="col-xs-12 text-left">
                                    <div class="text-left expression-readonly-high greyed-out">
                                      <p>
                                        If transaction fails in priority {pp} then fallback to
                                        priority {Number(pp) + 1}
                                      </p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            ) : null}
                          </Fragment>
                        );
                      })}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        ) : (
          Object.keys(rules).map((provider_priority, ind) => {
            return (
              <Fragment key={ind}>
                <div
                  class={`panel-body ${
                    this.props.rules[Number(provider_priority) + 1] &&
                    this.props.rules[Number(provider_priority) + 1].length
                      ? 'dashed_after'
                      : ''
                  }`}
                  style={{ marginTop: ind > 0 ? '-20px' : null }}
                >
                  <div className="precondition-div">
                    <div class="row">
                      <div className="col-xs-12">
                        <h3 class="provider-h">
                          PRIORITY {provider_priority}{' '}
                          <button
                            onClick={() => {
                              const rules = { ...this.props.rules };
                              delete rules[provider_priority];
                              if (this.props.update) {
                                this.props.update(rules);
                              }
                            }}
                            style={{
                              background: 'transparent',
                              marginTop: '-10px',
                            }}
                            className="btn btn-outline pull-right no-border create-rule-act"
                            disabled={Object.keys(this.props.rules).length < 2}
                          >
                            Remove
                          </button>
                        </h3>
                        {this.props.rules[provider_priority].map((e, index) => {
                          return (
                            <ProviderRow
                              key={index}
                              providers={this.props.providers}
                              readonly={this.props.readonly}
                              rule={e}
                              update={(rule) => {
                                const rules = { ...this.props.rules };
                                rules[provider_priority][index] = rule;
                                if (this.props.update) {
                                  this.props.update(rules);
                                }
                              }}
                              onClose={() => {
                                const rules = { ...this.props.rules };
                                rules[provider_priority].splice(index, 1);
                                if (!rules[provider_priority].length) {
                                  delete rules[provider_priority];
                                }
                                if (this.props.update) {
                                  this.props.update(rules);
                                }
                              }}
                            />
                          );
                        })}
                      </div>
                      {!this.props.readonly ? (
                        <div className="col-xs-12">
                          <div
                            class="add-expression"
                            style={{ color: '#2B83EA', marginTop: '18px' }}
                          >
                            <b
                              onClick={() => {
                                if (this.props.addNewRow) {
                                  this.props.addNewRow(provider_priority, 1);
                                }
                              }}
                              class="pointer"
                            >
                              Add Another Provider
                            </b>
                          </div>
                        </div>
                      ) : null}
                    </div>
                  </div>
                </div>
                {rules[Number(provider_priority) + 1] ? (
                  <div class="panel-body transaction-fail-panel-body">
                    <div className="precondition-div">
                      <div class="expression-row expression-row-readonly dashed if-transaction-dashed">
                        <div className="row">
                          <div className="col-xs-12 text-left">
                            <div class="text-left expression-readonly-high greyed-out">
                              <p class="if-tran-exp">
                                If transaction fails in priority {provider_priority} then fallback
                                to priority {Number(provider_priority) + 1}
                              </p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                ) : null}
              </Fragment>
            );
          })
        )}
      </Fragment>
    );
  }
}
