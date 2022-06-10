import React, { Fragment } from 'react';
import ProviderRow from './ProviderRow';

export default class ProviderRules extends React.Component {
  deleteProviderPriority = (provider_priority) => () => {
    const { update } = this.props;
    const rules = { ...this.props.rules };
    delete rules[provider_priority];
    if (update) {
      update(rules);
    }
  };

  updateProviderRow = (provider_priority, index) => (rule) => {
    const { update } = this.props;
    const rules = { ...this.props.rules };
    rules[provider_priority][index] = rule;
    if (update) {
      update(rules);
    }
  };

  closeProviderRow = (provider_priority, index) => () => {
    const { update } = this.props;
    const rules = { ...this.props.rules };
    rules[provider_priority].splice(index, 1);
    if (!rules[provider_priority].length) {
      delete rules[provider_priority];
    }
    if (update) {
      update(rules);
    }
  };

  addProvider = (provider_priority) => () => {
    const { addNewRow } = this.props;
    if (addNewRow) {
      addNewRow(provider_priority, 1);
    }
  };

  render() {
    const { rules, readonly, parent, providers } = this.props;
    const isParentCreateRule = readonly && parent === 'create-rule';
    const providerRowContainerClassName = `col-xs-${isParentCreateRule ? 11 : 10}${
      isParentCreateRule ? ' provider-row-container-readonly' : ' provider-row-container'
    }`;
    return readonly ? (
      <div className="panel-body">
        <div className="precondition-div">
          <div className="row">
            <div className={`col-xs-${parent === 'create-rule' ? 1 : 2}`}>
              {Object.entries(rules).map(([providerPriority, providerPriorityRule]) => (
                <Fragment key={providerPriority}>
                  <div className="provider-priority-button-container">
                    {providerPriorityRule.map((r) => (
                      <div className="row provider-priority-button" key={r?.id}>
                        <div className="col-xs-12" />
                      </div>
                    ))}
                    {readonly ? (
                      <button
                        type="button"
                        className="btn btn-primary operator-btn priority-btn readonly"
                      >
                        PRIORITY {providerPriority}
                      </button>
                    ) : null}
                  </div>
                  <div className="provider-priority-button" />
                </Fragment>
              ))}
            </div>
            <div className={providerRowContainerClassName}>
              <div className="row">
                <div className="col-xs-12">
                  {Object.entries(rules).map(([providerPriority, providerPriorityRule]) => (
                    <Fragment key={providerPriority}>
                      {providerPriorityRule.map((rule, index) => (
                        <ProviderRow
                          providers={providers}
                          dashed={index == providerPriorityRule.length - 1}
                          key={rule?.id}
                          readonly={readonly}
                          rule={rule}
                        />
                      ))}
                      {rules[Number(providerPriority) + 1] ? (
                        <div className="expression-row expression-row-readonly dashed if-tran-exp">
                          <div className="row">
                            <div className="col-xs-12 text-left">
                              <div className="text-left expression-readonly-high greyed-out">
                                <p>
                                  If transaction fails in priority {providerPriority} then fallback
                                  to priority {Number(providerPriority) + 1}
                                </p>
                              </div>
                            </div>
                          </div>
                        </div>
                      ) : null}
                    </Fragment>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    ) : (
      Object.keys(rules).map((provider_priority, ind) => {
        const isDashedPanelBody =
          rules[Number(provider_priority) + 1] && rules[Number(provider_priority) + 1].length;
        return (
          <Fragment key={provider_priority}>
            <div
              className={`panel-body${isDashedPanelBody ? ' dashed_after' : ''}${
                ind > 0 ? ' provider-row-wrapper' : ''
              }`}
            >
              <div className="precondition-div">
                <div className="row">
                  <div className="col-xs-12">
                    <h3 className="provider-h">
                      PRIORITY {provider_priority}{' '}
                      <button
                        onClick={this.deleteProviderPriority(provider_priority)}
                        className="btn btn-outline pull-right no-border create-rule-act remove-provider-priority"
                        disabled={Object.keys(rules).length < 2}
                      >
                        Remove
                      </button>
                    </h3>
                    {rules[provider_priority].map((providerPriorityRule, index) => (
                      <ProviderRow
                        key={providerPriorityRule?.id}
                        providers={providers}
                        readonly={readonly}
                        rule={providerPriorityRule}
                        update={this.updateProviderRow(provider_priority, index)}
                        onClose={this.closeProviderRow(provider_priority, index)}
                      />
                    ))}
                  </div>
                  {!readonly ? (
                    <div className="col-xs-12">
                      <div className="add-expression add-provider">
                        <b onClick={this.addProvider(provider_priority)} className="pointer">
                          Add Another Provider
                        </b>
                      </div>
                    </div>
                  ) : null}
                </div>
              </div>
            </div>
            {rules[Number(provider_priority) + 1] ? (
              <div className="panel-body transaction-fail-panel-body">
                <div className="precondition-div">
                  <div className="expression-row expression-row-readonly dashed if-transaction-dashed">
                    <div className="row">
                      <div className="col-xs-12 text-left">
                        <div className="text-left expression-readonly-high greyed-out">
                          <p className="if-tran-exp">
                            If transaction fails in priority {provider_priority} then fallback to
                            priority {Number(provider_priority) + 1}
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
    );
  }
}
