import React, { Fragment } from 'react';

import { Rule, MappedProiders } from 'merchant/views/Optimizer/types';

import { ProviderRow } from './ProviderRow';

interface ProviderRulesProps {
  rules: { [key: string]: Rule[] };
  readonly: boolean;
  parent: string;
  providers: MappedProiders[];
  update?: (rules: { [key: string]: Rule[] }) => void;
  addNewRow?: (provider_priority: number) => void;
}

export const ProviderRules = ({
  rules,
  readonly,
  parent,
  providers,
  update,
  addNewRow,
}: ProviderRulesProps): JSX.Element => {
  const deleteProviderPriority = (provider_priority) => () => {
    const rulesList = { ...rules };
    delete rulesList[provider_priority];
    if (update) {
      update(rulesList);
    }
  };

  const updateProviderRow = (provider_priority, index) => (rule: Rule) => {
    const rulesList = { ...rules };
    rulesList[provider_priority][index] = rule;
    if (update) {
      update(rulesList);
    }
  };

  const closeProviderRow = (provider_priority, index) => () => {
    const rulesList = { ...rules };
    rulesList[provider_priority].splice(index, 1);
    if (!rulesList[provider_priority].length) {
      delete rulesList[provider_priority];
    }
    if (update) {
      update(rulesList);
    }
  };

  const addProvider = (provider_priority: number) => () => {
    if (addNewRow) {
      addNewRow(provider_priority);
    }
  };

  const isParentCreateRule = readonly && parent === 'create-rule';
  const providerRowContainerClassName = `col-xs-${isParentCreateRule ? 11 : 10}${
    isParentCreateRule ? ' provider-row-container-readonly' : ' provider-row-container'
  }`;

  return readonly ? (
    <div className="panel-body">
      <div className="precondition-div">
        <div className="row">
          <div className={`col-xs-${parent === 'create-rule' ? 1 : 2}`}>
            {Object.entries(rules).map(
              ([providerPriority, providerPriorityRule]): JSX.Element => (
                <Fragment key={providerPriority}>
                  <div className="provider-priority-button-container">
                    {(providerPriorityRule as Rule[]).map((r) => (
                      <div className="row provider-priority-button" key={r?.id}>
                        <div className="col-xs-12" />
                      </div>
                    ))}
                    {readonly ? (
                      <button type="button" className="btn operator-btn priority-btn readonly">
                        PRIORITY {providerPriority}
                      </button>
                    ) : null}
                  </div>
                  <div className="provider-priority-button" />
                </Fragment>
              ),
            )}
          </div>
          <div className={providerRowContainerClassName}>
            <div className="row">
              <div className="col-xs-12">
                {Object.entries(rules).map(([providerPriority, providerPriorityRule]) => (
                  <Fragment key={providerPriority}>
                    {(providerPriorityRule as Rule[]).map((rule, index) => (
                      <ProviderRow
                        providers={providers}
                        dashed={index == (providerPriorityRule as Rule[]).length - 1}
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
                                Route transaction to priority {Number(providerPriority) + 1} if
                                success rate of priority {providerPriority} degrades
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
    <>
      {Object.keys(rules).map((provider_priority, ind) => {
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
                        onClick={deleteProviderPriority(provider_priority)}
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
                        update={updateProviderRow(provider_priority, index)}
                        onClose={closeProviderRow(provider_priority, index)}
                      />
                    ))}
                  </div>
                  {!readonly ? (
                    <div className="col-xs-12">
                      <div
                        onClick={addProvider(Number(provider_priority))}
                        className="add-expression add-provider"
                      >
                        <b className="pointer">Add Another Provider</b>
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
                            Route transaction to priority {Number(provider_priority) + 1} if success
                            rate of priority {provider_priority} degrades
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
      })}
    </>
  );
};
