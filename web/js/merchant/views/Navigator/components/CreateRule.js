import React, { Fragment } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import { deepClone } from 'common/utils/rzp-utils';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import Precondition from './Precondition';
import ProviderRules from './ProviderRules';
import { Link, Navigate } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import DeactivateRule from './DeactivateRule';
import { showNotification } from 'merchant_common/reducers/notifications';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Spinner from 'common/ui/Spinner';
import { CSSTransition } from 'react-transition-group';

import {
  changeRuleMode,
  getRule,
  fetchRules,
  reorderRules,
  updateRule,
  deleteRule,
  createRule,
} from 'merchant/reducers/navigator/details';
import { ReorderRules } from './ReorderRule';
import {
  get_unique,
  SMART_ROUTER,
  parameters,
  createMappedProviders,
  isExpressionValid,
  mapRulesArrayToObject,
  mapRulesObjectToArray,
  appendMid,
  setRuleMode,
  TOTAL_RULE_LIMIT,
  removeMid,
  total_live_rules,
  DEFAULT_RULE,
  getRuleStatus,
  rzpGateways,
} from './util';
import { PreconditionModel } from 'merchant/views/Navigator/models/PreconditionModel';
import FullPageCover from './FullPageCover';
import FullPageCoverHeader from './FullPageCoverHeader';

@connect(
  (state) => {
    return {
      ...state,
      rules: state.navigator.rules,
      rule: state.navigator.rule,
      loading: state.navigator.create_rule_loading,
      // isLoading: true,
      terminalProviders: state.navigator.terminalProviders,
    };
  },
  {
    openModal,
    showNotification,
    updateRule,
    fetchRules,
    closeModal,
    deleteRule,
    reorderRules,
    createRule,
    changeRuleMode,
  },
)
class CreateRule extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  componentDidMount() {
    document.addEventListener('keydown', this.escFunction);

    const params = deepClone(parameters);
    this.getAllWallets()
      .then((res) => {
        params.forEach((p, index, obj) => {
          if (p.name === 'Wallets') {
            if (res.wallets.length != 0) {
              p.values = res.wallets;
            } else {
              obj.splice(index, 1);
            }
          } else if (p.name === 'Currency') {
            if (res.currency.length != 0) {
              p.values = res.currency;
            } else {
              obj.splice(index, 1);
            }
          }
        });
      })
      .finally(() => this.setState({ PARAMETERS: params }));

    if (this.props.match.params.id) {
      const id = this.props.match.params.id;
      getRule(id)
        .then((rule) => {
          this.setState({
            rule,
            loading: false,
            update: true,
            steps: {
              1: {
                edit: false,
                show: true,
              },
              2: {
                edit: false,
                show: true,
              },
              3: {
                edit: false,
                show: true,
              },
              4: {
                edit: true,
                show: true,
              },
            },
          });
        })
        .catch((e) => {
          this.setState({ loading: false });
          this.props.showNotification({
            type: 'error',
            message: e.errors[0],
          });
        });
    }
  }

  getAllWallets = () => {
    return new Promise((resolve) => {
      const { terminalProviders } = this.props;
      const data = [];
      const currencyData = [];
      const mapData = {};
      const mapCurrencyData = {};
      const mapMethodData = {};
      terminalProviders.forEach((provider) => {
        let key = `${provider.Gateway}_${provider.Terminal_id}`;
        if (rzpGateways.includes(provider.Gateway)) {
          key = provider.Gateway;
        }
        if (provider.Gateway_details.wallet_metadata) {
          const wallets = provider.Gateway_details.wallet_metadata?.wallets || [];
          wallets.forEach((w) => {
            if (!data.includes(w)) {
              data.push(w);
            }
          });
          mapData[key] = wallets;
        }
        provider.Currency?.forEach((c) => {
          if (!currencyData.includes(c)) {
            currencyData.push(c);
          }
        });
        mapCurrencyData[key] = provider.Currency;
        mapMethodData[key] = provider?.Gateway_details['Payment Methods'];
      });
      const walletResult = data.map((w) => ({ value: w }));
      const currencyResult = currencyData.map((c) => ({ value: c }));
      const result = {
        wallets: walletResult,
        currency: currencyResult,
      };
      this.setState({
        mapWalletProvider: mapData,
        mapCurrencyProvider: mapCurrencyData,
        mapMethodsProvider: mapMethodData,
      });
      resolve(result);
    });
  };

  updateStep(index, data) {
    this.setState((prevState) => {
      const steps = prevState.steps;
      Object.keys(steps).forEach((k) => {
        steps[k].edit = false;
      });
      steps[index] = { ...steps[index], ...data };
      return { steps };
    });
  }

  componentWillUnmount() {
    document.removeEventListener('keydown', this.escFunction);
  }

  escFunction = (event) => {
    if (event.keyCode === 27) {
      this.setState({ redirect: '/optimizer/rules' });
    }
  };

  state = {
    loading: !!this.props.match.params.id,
    update: false,
    redirect: null,
    rule: {
      name: '',
      description: '',
      precondition: new PreconditionModel(),
      rules: [],
    },
    steps: {
      1: {
        edit: true,
        show: true,
      },
      2: {
        edit: false,
        show: false,
      },
      3: {
        edit: false,
        show: false,
      },
      4: {
        edit: false,
        show: false,
      },
    },
    PARAMETERS: [],
    mapWalletProvider: {},
    mapCurrencyProvider: {},
    mapMethodsProvider: {},
  };

  deactivateRule = () => {
    return new Promise((res) => {
      this.props.openModal({
        size: 'large',
        component: (
          <DeactivateRule
            rules={total_live_rules(this.props.rules)}
            onSuccess={(e) => {
              // this.props.closeModal();
              res(e);
            }}
          />
        ),
      });
    });
  };

  get isPreconditionValid() {
    let is_valid = true;
    const precondition = this.state.rule.precondition;
    if (precondition) {
      if (precondition.type == 'logical') {
        precondition.operands.forEach((e) => {
          if (!isExpressionValid(e)) {
            is_valid = false;
          }
        });
      } else if (!isExpressionValid(precondition)) {
        is_valid = false;
      }
    }
    return is_valid;
  }

  get isProviderRulesValid() {
    const { rule } = this.state;
    let is_valid = true;
    const rules = mapRulesArrayToObject(rule.rules);
    if (rule.rules.length === 0) {
      is_valid = false;
    }
    Object.keys(rules).forEach((k) => {
      let total_load = 0;
      rules[k].forEach((r) => {
        const load = Number(r.additional_attribute[1]?.value);
        total_load += load;
        if (!(isExpressionValid(r.expression.operands[0]) && load > 0)) {
          is_valid = false;
        }
      });
      if (total_load !== 100) {
        is_valid = false;
      }
    });
    return is_valid;
  }

  get isRuleValid() {
    const { name, is_default, rules } = this.state?.rule;
    return (
      name &&
      (is_default ? true : name !== appendMid(DEFAULT_RULE)) &&
      rules.length &&
      (is_default ? true : this.isPreconditionValid) &&
      this.isProviderRulesValid
    );
  }

  isSelectedValidProvider = (MAPPED_PROVIDERS) => {
    let is_valid = true;
    const rules = mapRulesArrayToObject(this.state.rule.rules);
    Object.keys(rules).forEach((k) => {
      rules[k].forEach((r) => {
        if (
          r.expression.operands &&
          r.expression.operands[0].operands &&
          r.expression.operands[0].operands[0].value === '$provider.id'
        ) {
          const provider = r.expression.operands[0].operands[1].value;
          MAPPED_PROVIDERS.forEach((p) => {
            if (p.id === provider && p.disabled) {
              is_valid = false;
            }
          });
        }
      });
    });
    return is_valid;
  };

  addNewRow = (provider_priority) => {
    const { rule } = this.state;
    const rules = mapRulesArrayToObject(rule.rules);
    const RULES = [];
    let load = null;
    let total_load = 0;
    if (!rules[provider_priority]) {
      rules[provider_priority] = [];
    }
    rules[provider_priority].forEach((r) => {
      const c_load = Number(r.additional_attribute[1].value);
      if (typeof c_load === 'number') {
        total_load += c_load;
      }
    });
    load = 100 - total_load;
    if (load > 0) {
      rules[provider_priority].push({
        name: 'xx_xx_xx',
        expression: {
          type: 'logical',
          value: '&&',
          operands: [
            {
              type: 'comparator',
              value: '==',
              operands: [
                {
                  type: 'variable',
                  value: '$provider.id',
                  operands: null,
                },
                {
                  value: '',
                },
              ],
            },
            {
              type: 'comparator',
              value: 'in',
              operands: [
                {
                  type: 'string',
                  value: 'live',
                  operands: null,
                },
                {
                  type: 'variable',
                  value: '$payment.rule_mode',
                  operands: null,
                },
              ],
            },
          ],
        },
        additional_attribute: [
          {
            name: 'provider_priority',
            value: `${provider_priority}`,
          },
          {
            name: 'load',
            value: `${load}`,
          },
        ],
        skiponfailure: false,
      });
      Object.keys(rules).forEach((key) => {
        RULES.push(...rules[key]);
      });
      rule.rules = RULES;
      this.setState({ rule });
    }
  };

  createRule = (data) => {
    // data.rules = setRuleMode(data.rules, mode);
    return this.props.createRule(data);
  };

  reorderRules = (rules, rule) => {
    const body = {
      order: {
        ordered_names: rules.map((r) => r.name),
      },
    };
    if (rule) {
      body.rule_group_id = rule.id;
    }
    return this.props.reorderRules(body);
  };

  goNext = (i, cb) => {
    this.setState((prevState) => {
      const steps = { ...prevState.steps };
      steps[i] = {
        edit: false,
        show: true,
      };
      steps[i + 1] = {
        show: true,
        edit: true,
      };
      return { steps };
    }, cb);
  };

  reqBody = (mode) => {
    const rule = deepClone(this.state.rule);
    rule.rules.forEach((r) => {
      const random = get_unique();
      // check this for later and replace this with uuid
      r.name = `${random}_${rule.name}`;
      // r.score = this.props.rules.length + 1;
    });
    const req = {
      name: rule.name,
      precondition: rule.precondition,
      rules: setRuleMode(rule.rules, mode),
      description: rule.description,
      // outcome_type: '',
      strategy: 'default',
      // mandatory_attributes: [],
      additional_attributes: [
        {
          name: 'rule_mode',
          type: 'string',
          values: [mode],
        },
      ],
    };
    return req;
  };

  cleanRule = (rule) => {
    rule.rules.forEach((r) => {
      const random = get_unique();
      r.name = `${random}_${rule.name}`;
      r.expression.operands.forEach((o, index) => {
        if (o.operands[0].value === '$merchant.id') {
          r.expression.operands.splice(index, 1);
        }
      });
    });
    if (rule.is_default) {
      delete rule.precondition;
    }
    rule.rules = setRuleMode(rule.rules, rule.additional_attributes[0].values[0]);
    delete rule.outcome_type;
    delete rule.mandatory_attributes;
    return rule;
  };

  addPriority = () => {
    const pp = Object.keys(mapRulesArrayToObject(this.state.rule.rules)).length + 1;
    this.addNewRow(pp);
  };

  configureProvider = (param, selectedValues, mapProvider, p) => {
    let disable = true;
    selectedValues?.forEach((value) => {
      let val = value;
      if (value === 'upi_intent' || value === 'upi_collect') {
        val = 'upi';
      }
      if (mapProvider[p.id]?.includes(val)) {
        disable = false;
      }
    });
    if (disable) {
      p.disabled = true;
      p.disabled_message = `Selected ${param} is not supported on this provider.`;
    }
  };

  checkMethods = (operands, selectedWallets, selectedCurrencies, selectedMethods) => {
    let is_wallet = false;
    let is_currency = false;
    let is_method = false;
    if (operands?.length > 0) {
      if (operands[0]?.value === '$payment.optimizer_wallet') {
        is_wallet = true;
        this.findSelectedValues(operands, '$payment.optimizer_wallet', selectedWallets);
      }
      if (operands[0]?.value === '$payment.optimizer_currency') {
        is_currency = true;
        this.findSelectedValues(operands, '$payment.optimizer_currency', selectedCurrencies);
      }
      if (operands[0]?.value === '$payment.navigator_method') {
        is_method = true;
        this.findSelectedValues(operands, '$payment.navigator_method', selectedMethods);
      }
    }
    return {
      is_wallet,
      is_currency,
      is_method,
    };
  };

  findSelectedValues = (operands, checkParam, selectedValues) => {
    if (operands[0].value === checkParam && operands[1].value != '') {
      selectedValues.push(...operands[1].value.split(','));
    }
  };

  render() {
    const {
      PARAMETERS,
      rule,
      mapMethodsProvider,
      mapWalletProvider,
      mapCurrencyProvider,
      redirect,
      update,
      loading,
      steps,
    } = this.state;
    const rules = deepClone(this.props.rules);

    const { terminalProviders } = this.props;
    let MAPPED_PROVIDERS = createMappedProviders(terminalProviders);

    const selectedWallets = [];
    const selectedCurrencies = [];
    const selectedMethods = [];
    let is_smart_router = false;
    let is_wallet = false;
    let is_currency = false;
    let is_method = false;
    if (rule?.precondition) {
      if (rule?.precondition?.type === 'logical') {
        rule?.precondition?.operands?.forEach((o) => {
          const methodResult = this.checkMethods(
            o.operands,
            selectedWallets,
            selectedCurrencies,
            selectedMethods,
          );
          is_wallet = methodResult.is_wallet;
          is_currency = methodResult.is_currency;
          is_method = methodResult.is_method;
        });
      } else {
        const operands = rule?.precondition?.operands;
        const methodResult = this.checkMethods(
          operands,
          selectedWallets,
          selectedCurrencies,
          selectedMethods,
        );
        is_wallet = methodResult.is_wallet;
        is_currency = methodResult.is_currency;
        is_method = methodResult.is_method;
      }
    }

    rule.rules.forEach((rule) => {
      rule.expression.operands?.forEach((o) => {
        if (o.operands && o.operands[1].value == SMART_ROUTER) {
          is_smart_router = true;
        }
      });
    });
    if (is_wallet || is_currency || is_method) {
      MAPPED_PROVIDERS = MAPPED_PROVIDERS.map((p) => {
        if (is_method) {
          this.configureProvider('method', selectedMethods, mapMethodsProvider, p);
        }
        if (is_wallet && selectedWallets.length !== 0) {
          this.configureProvider('wallet', selectedWallets, mapWalletProvider, p);
        }
        if (is_currency && selectedCurrencies.length !== 0) {
          this.configureProvider('currency', selectedCurrencies, mapCurrencyProvider, p);
        }
        return p;
      });
    }
    if (is_smart_router) {
      PARAMETERS.forEach((p) => {
        if (p.name == 'Payment Method') {
          p.values.forEach((v) => {
            if (v.value === 'netbanking') {
              v.disabled = true;
              v.disabled_message =
                'Net Banking cannot be added as a condition with Smart Router as a provider currently.';
            }
          });
        }
      });
    }
    const isValidProvider = this.isSelectedValidProvider(MAPPED_PROVIDERS);

    if (redirect) {
      return <Navigate to={redirect} replace />;
    }

    return (
      <FullPageCover>
        <FullPageCoverHeader>
          <div className="container" style={{ hright: '56px' }}>
            <div className="panel" style={{ marginTop: '3px' }}>
              <div className="panel-body">
                <div className="row">
                  <div className="col-xs-4">
                    <h3>{update ? 'Edit' : 'Create'} Rule</h3>
                  </div>
                  <div className="col-xs-5" />
                  <div className="col-xs-3">
                    <Link to="/optimizer/rules">
                      <span
                        style={{
                          cursor: 'pointer',
                          color: 'rgba(22, 47, 86, 0.54)',
                          fontSize: '17px',
                          fontWeight: 600,
                        }}
                        className="pull-right"
                      >
                        Close <i className="i i-close" />
                      </span>
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </FullPageCoverHeader>
        <div className="container">
          <div className="navigator--create-rule">
            {loading ? (
              <div className="page-spinner-container">
                <Spinner />
              </div>
            ) : (
              <Fragment>
                {steps?.[1]?.show || update ? (
                  !rule.is_default ? (
                    <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                      <div
                        className={`panel gateway-list rule-detail ${
                          steps?.[1].edit ? 'active' : ''
                        }`}
                      >
                        <div className="panel-header">
                          <h2 className="payment-gateway-title">
                            Rule Details
                            {!steps?.[1].edit ? (
                              <button
                                onClick={() => {
                                  const edit = steps?.[1].edit;
                                  this.updateStep(1, {
                                    edit: !edit,
                                  });
                                }}
                                className="pull-right no-border create-rule-act"
                              >
                                {' '}
                                <i className="i i-pencil-edit" /> Edit Rule Details
                              </button>
                            ) : (
                              <span className="pull-right step-text"> Step 1 Out Of 4</span>
                            )}
                          </h2>
                          {steps?.[1].edit ? (
                            <p className="desc">Add a name and description for your custom rule.</p>
                          ) : null}
                        </div>
                        <div className="panel-body">
                          <div className="row">
                            <div className="col-xs-12">
                              <div className="row">
                                <div className="col-xs-2">
                                  <label for="name" style={{ textAlign: 'left' }}>
                                    Rule Name
                                  </label>
                                </div>
                                <div className="col-xs-6">
                                  {!steps?.[1].edit ? (
                                    <div className="stepper-readonly">{removeMid(rule.name)}</div>
                                  ) : (
                                    <Input
                                      id="name"
                                      className="Input--vLeft"
                                      name="name"
                                      value={removeMid(rule.name)}
                                      placeholder="Rule Name"
                                      onChange={(e) => {
                                        const value = e.target.value;
                                        this.setState((prevState) => {
                                          const rule = prevState.rule;
                                          rule.name = appendMid(value);
                                          return { rule };
                                        });
                                      }}
                                    />
                                  )}
                                </div>
                              </div>
                            </div>
                            <div className="col-xs-12">
                              <div
                                className="row"
                                style={{ marginTop: '10px', marginBottom: '15px' }}
                              >
                                <div className="col-xs-2">
                                  <label for="description" style={{ textAlign: 'left' }}>
                                    Rule Description
                                  </label>
                                </div>
                                <div className="col-xs-6">
                                  {!steps?.[1].edit ? (
                                    <div className="stepper-readonly">{rule.description}</div>
                                  ) : (
                                    <textarea
                                      id="description"
                                      className="Input--vLeft form-control"
                                      value={rule.description}
                                      name="description"
                                      placeholder="Rule Description"
                                      onChange={(e) => {
                                        const value = e.target.value;
                                        this.setState((prevState) => {
                                          const rule = prevState.rule;
                                          rule.description = value;

                                          return { rule };
                                        });
                                      }}
                                    />
                                  )}
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                        {steps?.[1].edit ? (
                          <div className="panel-footer">
                            <button
                              disabled={
                                !(removeMid(rule.name) && rule.name !== appendMid(DEFAULT_RULE))
                              }
                              onClick={() => {
                                this.goNext(1, () => {
                                  if (rule.is_default) {
                                    this.goNext(2);
                                  }
                                });
                              }}
                              className="btn btn-primary pull-right"
                            >
                              Next <i className="i i-arrow-forward" />
                            </button>
                            <div className="clearfix" />
                          </div>
                        ) : null}
                      </div>
                    </CSSTransition>
                  ) : null
                ) : null}

                {steps?.[2].show || update ? (
                  <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                    <div className={`panel gateway-list ${steps?.[2].edit ? 'active' : ''}`}>
                      <div className="panel-header">
                        <h2 className="payment-gateway-title">
                          Rule Conditions
                          {!rule.is_default ? (
                            !steps?.[2].edit ? (
                              <button
                                onClick={() => {
                                  const edit = steps?.[2].edit;
                                  this.updateStep(2, {
                                    edit: !edit,
                                  });
                                }}
                                className=" pull-right no-border create-rule-act"
                              >
                                {' '}
                                <i className="i i-pencil-edit" /> Edit Rule Conditions
                              </button>
                            ) : (
                              <span className="pull-right step-text"> Step 2 Out Of 4</span>
                            )
                          ) : null}
                        </h2>
                        {steps?.[2].edit ? (
                          <p className="desc">
                            {rule.is_default ? (
                              `Default rule will be used as a rule to route transactions that do not satisfy any other rules.`
                            ) : (
                              <span>
                                Add conditions to identify payments.{' '}
                                {/* <a class="nav-link">Learn More</a>{' '} */}
                              </span>
                            )}
                          </p>
                        ) : rule.is_default ? (
                          <p className="desc">
                            Default rule will be used as a rule to route transactions that{' '}
                            <b>do not satisfy any other rules.</b>
                          </p>
                        ) : null}
                      </div>
                      {!rule.is_default ? (
                        <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                          <div className="panel-body" style={{ paddingTop: '15px !important' }}>
                            <Precondition
                              parameters={PARAMETERS}
                              readonly={!steps?.[2].edit}
                              precondition={rule.precondition}
                              update={(precondition) => {
                                this.setState((prevState) => {
                                  const rule = prevState.rule;
                                  rule.precondition = precondition;
                                  return { rule };
                                });
                              }}
                              parent="create-rule"
                            />
                          </div>
                        </CSSTransition>
                      ) : null}

                      {steps?.[2].edit ? (
                        <div className="panel-footer">
                          <button
                            onClick={() => {
                              this.goNext(2);
                              if (rule.rules.length == 0 && !update) {
                                this.addPriority();
                              }
                            }}
                            disabled={!this.isPreconditionValid}
                            className="btn btn-primary pull-right"
                          >
                            Next <i className="i i-arrow-forward" />
                          </button>
                          <div className="clearfix" />
                        </div>
                      ) : null}
                    </div>
                  </CSSTransition>
                ) : null}

                {steps?.[3].show || update ? (
                  <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                    <div className={`panel gateway-list ${steps?.[3].edit ? 'active' : ''}`}>
                      <div className="panel-header">
                        <h2 className="payment-gateway-title">
                          Target Payment Provider
                          {!steps?.[3].edit ? (
                            <button
                              onClick={() => {
                                const edit = steps?.[3].edit;
                                this.updateStep(3, {
                                  edit: !edit,
                                });
                              }}
                              className="pull-right no-border create-rule-act"
                            >
                              {' '}
                              <i className="i i-pencil-edit" /> Edit Target Provider
                            </button>
                          ) : !rule.is_default ? (
                            <span className="pull-right step-text"> Step 3 Out Of 4</span>
                          ) : null}
                        </h2>
                        {steps?.[3].edit ? (
                          <p className="desc">
                            Add desired payment provider through which the payment has to be routed.{' '}
                            {/* <a class="nav-link">Learn More</a> */}
                          </p>
                        ) : null}
                      </div>
                      <ProviderRules
                        parent="create-rule"
                        providers={MAPPED_PROVIDERS}
                        addNewRow={(pp, rp) => this.addNewRow(pp, rp)}
                        update={(e) => {
                          this.setState((prevState) => {
                            const rule = prevState.rule;
                            const R = {};
                            Object.keys(e).forEach((k, index) => {
                              const ni = index + 1;
                              e[k].forEach((r) => {
                                r.additional_attribute[0].value = `${ni}`;
                                r.score = rule.score;
                              });
                              R[ni] = e[k];
                            });
                            const arrRules = mapRulesObjectToArray(R);
                            rule.rules = arrRules;
                            return { rule };
                          });
                        }}
                        rules={mapRulesArrayToObject(rule.rules)}
                        readonly={!steps?.[3].edit}
                      />
                      {steps?.[3].edit ? (
                        <div className="panel-body add-exp-cont">
                          <div className="row">
                            <div className="col-xs-12">
                              <div className="add-expression">
                                <b onClick={this.addPriority} className="pointer">
                                  Add Priority{' '}
                                  {!rule.is_default ? (
                                    <span>
                                      <i className="i i-info-outline add-priority-info-c" />
                                      <Popover
                                        theme="dark"
                                        align="bottom"
                                        // parentQuerySelector={`.Modal--confirm`}
                                      >
                                        <PopoverBody>
                                          <div>
                                            If transaction fails in a priority then it will fallback
                                            to lower priority.{' '}
                                          </div>
                                        </PopoverBody>
                                      </Popover>
                                    </span>
                                  ) : null}
                                </b>
                              </div>
                            </div>
                          </div>
                        </div>
                      ) : null}
                      {steps?.[3].edit ? (
                        <div className="panel-footer">
                          <button
                            onClick={() => {
                              this.goNext(3);
                            }}
                            className="btn btn-primary pull-right"
                            disabled={!this.isProviderRulesValid || !isValidProvider}
                          >
                            Next <i className="i i-arrow-forward" />
                          </button>
                          <div className="clearfix" />
                        </div>
                      ) : null}
                    </div>
                  </CSSTransition>
                ) : null}
                {steps?.[4].show || update ? (
                  <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                    <div
                      style={{ paddingRight: '28px', paddingLeft: '28px', marginBottom: '80px' }}
                      className={`panel gateway-list ${steps?.[4].edit ? 'active' : ''}`}
                    >
                      <div className="panel-header">
                        <h2 className="payment-gateway-title" style={{ marginLeft: 0 }}>
                          Confirm Rule
                        </h2>
                        <p className="desc" style={{ marginLeft: 0 }}>
                          Publish the rule or save as draft to publish later.{' '}
                          {/* <a className="nav-link"> Learn More</a>{' '} */}
                        </p>
                      </div>
                      {steps?.[4].edit ? (
                        <div
                          className="panel-footer"
                          style={{
                            borderTop: '1px solid #e3dede',
                            paddingRight: 0,
                            paddingTop: '30px',
                          }}
                        >
                          {!update ? (
                            <Fragment>
                              <button
                                onClick={() => {
                                  let rule = this.reqBody('test');
                                  const score = rules.length + 1;
                                  rule.rules.forEach((r) => {
                                    r.score = score;
                                  });
                                  let promise;
                                  // if (true) {
                                  if (total_live_rules(rules).length >= TOTAL_RULE_LIMIT) {
                                    promise = this.deactivateRule().then((deactivated_rule) => {
                                      return this.props
                                        .changeRuleMode(deactivated_rule.id, 'test')
                                        .then(() => {
                                          rules.forEach((r, i) => {
                                            if (r.id == deactivated_rule.id) {
                                              rules.splice(i, 1);
                                            }
                                          });
                                          this.props.closeModal();
                                          return this.createRule(rule);
                                        });
                                    });
                                  } else {
                                    promise = this.createRule(rule);
                                  }
                                  promise
                                    .then((respRule) => {
                                      rule = { ...respRule, current: true };
                                      this.props.openModal({
                                        size: 'large',
                                        component: (
                                          <ReorderRules
                                            rules={[...rules.filter((r) => r.id !== rule.id), rule]}
                                            onSuccess={(orderedRules) => {
                                              this.props
                                                .reorderRules(orderedRules, rule)
                                                .then(() => {
                                                  this.props.showNotification({
                                                    type: 'success',
                                                    message: 'Rule made live successully',
                                                    closeTimeout: 5000,
                                                  });
                                                  this.props.closeModal();
                                                  this.setState({ redirect: '/optimizer/rules' });
                                                });
                                            }}
                                            onClose={() => {
                                              this.context
                                                .confirm({
                                                  header: 'Are you sure you want to close?',
                                                  message: `Rule will not be published live.`,
                                                  affirmativeLabel: 'Confirm',
                                                  abortLabel: 'Cancel',
                                                })
                                                .then(() => {
                                                  // return Promise.resolve(rule);
                                                  return this.props.deleteRule(rule.id);
                                                })
                                                .then(() => {
                                                  this.props.closeModal();
                                                });
                                            }}
                                          />
                                        ),
                                      });
                                    })
                                    .catch((e) => {
                                      this.props.showNotification({
                                        type: 'error',
                                        message: e.errors[0],
                                      });
                                    });
                                }}
                                disabled={!this.isRuleValid || this.props.loading}
                                className="btn btn-primary pull-right"
                              >
                                {!this.props.loading ? 'Publish Rule' : 'Please Wait..'}
                              </button>
                              <button
                                style={{ marginRight: '10px' }}
                                onClick={() => {
                                  const rule = this.reqBody('test');
                                  const score = rules.length + 1;
                                  this.context
                                    .confirm({
                                      header: 'Are you sure want to save rule as draft?',
                                      message: `Rule will be saved in draft at ${score} priority.`,
                                      affirmativeLabel: `Yes, Publish`,
                                      abortLabel: 'Cancel',
                                    })
                                    .then(() => {
                                      rule.rules.forEach((r) => {
                                        r.score = score;
                                      });
                                      return this.createRule(rule);
                                    })
                                    .then(() => {
                                      this.props.showNotification({
                                        type: 'success',
                                        message: 'Rule saved as draft successully',
                                        closeTimeout: 5000,
                                      });
                                      this.setState({ redirect: '/optimizer/rules' });
                                    })
                                    .catch((e) => {
                                      this.props.showNotification({
                                        type: 'error',
                                        message: e.errors[0],
                                      });
                                    });
                                }}
                                disabled={!this.isRuleValid}
                                className="btn btn-outline pull-right no-border create-rule-act"
                              >
                                Save as Draft
                              </button>
                            </Fragment>
                          ) : (
                            <Fragment>
                              <button
                                onClick={() => {
                                  const rule = this.cleanRule(this.state.rule);
                                  rule.rules.forEach((r) => {
                                    const temp = r.name.split('_');
                                    temp.pop();
                                    temp.pop();
                                    temp.push(rule.name);
                                    r.name = temp.join('_');
                                  });
                                  this.context
                                    .confirm({
                                      header: 'Are you sure you want to publish edits?',
                                      message: null,
                                      affirmativeLabel: `Yes, Publish`,
                                      abortLabel: 'Cancel',
                                    })
                                    .then(() => {
                                      return this.props.updateRule(rule);
                                    })
                                    .then(() => {
                                      this.props.showNotification({
                                        type: 'success',
                                        message: 'Rule has been updated successully',
                                        closeTimeout: 5000,
                                      });
                                      let promise;
                                      if (
                                        total_live_rules(rules).length >= TOTAL_RULE_LIMIT &&
                                        getRuleStatus(rule) === 'test'
                                      ) {
                                        promise = this.deactivateRule().then((deactivated_rule) => {
                                          return this.props
                                            .changeRuleMode(deactivated_rule.id, 'test')
                                            .then(() => {
                                              rules.forEach((r, i) => {
                                                if (r.id == deactivated_rule.id) {
                                                  rules.splice(i, 1);
                                                }
                                              });
                                              return this.props.fetchRules();
                                            });
                                        });
                                      } else {
                                        promise = Promise.resolve();
                                      }
                                      return promise;
                                    })
                                    .then(() => {
                                      const index = rules.findIndex((r) => r.id == rule.id);
                                      rule.current = true;
                                      rules[index] = rule;
                                      if (!rule.is_default) {
                                        this.props.openModal({
                                          size: 'large',
                                          component: (
                                            <ReorderRules
                                              rules={rules}
                                              onSuccess={(orderedRules) => {
                                                let args;
                                                if (getRuleStatus(rule) === 'test') {
                                                  args = [orderedRules, rule];
                                                } else {
                                                  args = [orderedRules];
                                                }
                                                this.props
                                                  .reorderRules(...args)
                                                  .then(() => {
                                                    this.props.closeModal();
                                                    this.props.showNotification({
                                                      type: 'success',
                                                      message: 'Rule made live successfully',
                                                      closeTimeout: 5000,
                                                    });
                                                    this.setState({
                                                      redirect: '/optimizer/rules',
                                                    });
                                                  })
                                                  .then(() =>
                                                    getRule(rule.id).then((R) =>
                                                      this.setState({ rule: R }),
                                                    ),
                                                  )
                                                  .catch((e) => {
                                                    this.props.showNotification({
                                                      type: 'error',
                                                      message: e.errors[0],
                                                    });
                                                  });
                                              }}
                                              onClose={this.props.closeModal}
                                            />
                                          ),
                                        });
                                      } else {
                                        this.props.showNotification({
                                          type: 'success',
                                          message: 'Rule has been updated successully',
                                          closeTimeout: 5000,
                                        });
                                        this.setState({ redirect: '/optimizer/rules' });
                                      }
                                    })
                                    .catch((e) => {
                                      this.props.showNotification({
                                        type: 'error',
                                        message: e.errors[0],
                                      });
                                    });
                                }}
                                disabled={!this.isRuleValid}
                                className="btn btn-primary pull-right"
                              >
                                {getRuleStatus(rule) === 'test' ? `Publish Rule` : `Publish Edits`}
                              </button>
                              {getRuleStatus(rule) === 'live' ? (
                                !rule.is_default ? (
                                  <button
                                    style={{ marginRight: '10px' }}
                                    onClick={() => {
                                      this.context.confirm({
                                        header: 'Are you sure want to deactivate this rule?',
                                        message: () => (
                                          <p style={{ marginBottom: '15px' }}>
                                            Rule will be saved in draft after deactivation .
                                          </p>
                                        ),
                                        affirmativeLabel: 'Yes, Deactivate',
                                        affirmativePendingLabel: 'Deactivating...',
                                        abortLabel: 'Cancel',
                                        action: () => {
                                          this.props
                                            .changeRuleMode(rule.id, 'test')
                                            .then(() => {
                                              return getRule(rule.id);
                                            })
                                            .then(() => this.props.fetchRules())
                                            .then((respRules) => {
                                              this.setState((prevState) => {
                                                const findRule = respRules.find(
                                                  (r) => r.id == prevState.rule.id,
                                                );
                                                return { rule: findRule };
                                              });
                                              this.props.showNotification({
                                                type: 'success',
                                                message: 'Rule deactivated successully',
                                                closeTimeout: 5000,
                                              });
                                              this.setState({
                                                redirect: '/optimizer/rules',
                                              });
                                            })
                                            .catch((e) => {
                                              this.props.showNotification({
                                                type: 'error',
                                                message: e.errors[0],
                                              });
                                            });
                                        },
                                      });
                                    }}
                                    disabled={this.props.loading}
                                    className="btn btn-outline pull-right no-border create-rule-act"
                                  >
                                    Deactivate Rule
                                  </button>
                                ) : null
                              ) : (
                                <button
                                  style={{ marginRight: '10px' }}
                                  onClick={() => {
                                    const rule = this.cleanRule(this.state.rule);
                                    rule.rules.forEach((r) => {
                                      const temp = r.name.split('_');
                                      temp.pop();
                                      temp.pop();
                                      temp.push(rule.name);
                                      r.name = temp.join('_');
                                    });
                                    this.context
                                      .confirm({
                                        header: 'Are you sure want to update draft rule?',
                                        // message: `Rule will be saved in draft at ${score} priority.`,
                                        affirmativeLabel: `Yes, Update`,
                                        abortLabel: 'Cancel',
                                      })
                                      .then(() => {
                                        return this.props.updateRule(rule);
                                      })
                                      .then(() => {
                                        this.props.showNotification({
                                          type: 'success',
                                          message: 'Draft rule has been updated',
                                          closeTimeout: 5000,
                                        });
                                        this.setState({ redirect: '/optimizer/rules' });
                                      })
                                      .catch((e) => {
                                        this.props.showNotification({
                                          type: 'error',
                                          message: e.errors[0],
                                        });
                                      });
                                  }}
                                  disabled={this.props.loading}
                                  className="btn btn-outline pull-right no-border create-rule-act"
                                >
                                  Save as Draft
                                </button>
                              )}
                            </Fragment>
                          )}
                          <div className="clearfix" />
                        </div>
                      ) : null}
                    </div>
                  </CSSTransition>
                ) : null}
              </Fragment>
            )}
          </div>
        </div>
      </FullPageCover>
    );
  }
}

export default withRouter(CreateRule);
