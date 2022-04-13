import React, { Fragment } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import { deepClone } from 'common/utils/rzp-utils';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import Precondition from './Precondition';
import ProviderRules from './ProviderRules';
import { withRouter, Link, Redirect } from 'react-router-dom';
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
import { PreconditionModel } from '../models/PreconditionModel';
import FullPageCover from './FullPageCover';
import FullPageCoverHeader from './FullPageCoverHeader';

@withRouter
@connect(
  (state) => {
    return {
      ...state,
      rules: state.navigator.rules,
      rule: state.navigator.rule,
      loading: state.navigator.create_rule_loading,
      // isLoading: true,
      providers: state.navigator.providers,
      terminalProviders: state.navigator.terminalProviders,
      user: state.session.user,
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
export default class CreateRule extends React.Component {
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
      });
      const walletResult = data.map((w) => ({ value: w }));
      const currencyResult = currencyData.map((c) => ({ value: c }));
      const result = {
        wallets: walletResult,
        currency: currencyResult,
      };
      this.setState({ mapWalletProvider: mapData, mapCurrencyProvider: mapCurrencyData });
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
    let is_valid = true;
    const rules = mapRulesArrayToObject(this.state.rule.rules);
    if (this.state.rule.rules.length == 0) {
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
    return (
      this.state.rule.name &&
      (this.state.rule.is_default ? true : this.state.rule.name !== appendMid(DEFAULT_RULE)) &&
      this.state.rule.rules.length &&
      (this.state.rule.is_default ? true : this.isPreconditionValid) &&
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
    const rule = this.state.rule;
    const rules = mapRulesArrayToObject(this.state.rule.rules);
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
    selectedValues.forEach((v) => {
      if (mapProvider[p.id]?.includes(v)) {
        disable = false;
      }
    });
    if (disable) {
      p.disabled = true;
      p.disabled_message = `Selected ${param} is not supported on this provider.`;
    }
  };

  checkMethods = (operands, selectedWallets, selectedCurrencies) => {
    let is_netbanking = false;
    let is_wallet = false;
    let is_currency = false;
    if (operands[1].value.includes('netbanking')) {
      is_netbanking = true;
    }
    if (operands[1].value.includes('wallet') || operands[0].value === '$payment.optimizer_wallet') {
      is_wallet = true;
      this.findSelectedValues(operands, '$payment.optimizer_wallet', selectedWallets);
    }
    if (operands[0].value === '$payment.optimizer_currency') {
      is_currency = true;
      this.findSelectedValues(operands, '$payment.optimizer_currency', selectedCurrencies);
    }
    return {
      is_netbanking,
      is_wallet,
      is_currency,
    };
  };

  findSelectedValues = (operands, checkParam, selectedValues) => {
    if (operands[0].value === checkParam && operands[1].value != '') {
      selectedValues.push(...operands[1].value.split(','));
    }
  };

  render() {
    if (this.state.redirect) {
      return <Redirect to={this.state.redirect} />; // nosemgrep : https://semgrep.dev/s/razorpay:rzp-react-router-redirect
    }

    const { PARAMETERS } = this.state;
    const rules = deepClone(this.props.rules);

    const { user, providers, terminalProviders } = this.props;
    let MAPPED_PROVIDERS = createMappedProviders(
      user.isAddProviderEnabled,
      providers,
      terminalProviders,
    );

    const selectedWallets = [];
    const selectedCurrencies = [];
    let is_netbanking = false;
    let is_smart_router = false;
    let is_wallet = false;
    let is_currency = false;
    if (this.state.rule.precondition) {
      if (this.state.rule.precondition.type === 'logical') {
        this.state.rule.precondition.operands.forEach((o) => {
          const methodResult = this.checkMethods(o.operands, selectedWallets, selectedCurrencies);
          is_netbanking = methodResult.is_netbanking;
          is_wallet = methodResult.is_wallet;
          is_currency = methodResult.is_currency;
        });
      } else {
        const operands = this.state.rule.precondition.operands;
        const methodResult = this.checkMethods(operands, selectedWallets, selectedCurrencies);
        is_netbanking = methodResult.is_netbanking;
        is_wallet = methodResult.is_wallet;
        is_currency = methodResult.is_currency;
      }
    }

    this.state.rule.rules.forEach((rule) => {
      rule.expression.operands?.forEach((o) => {
        if (o.operands && o.operands[1].value == SMART_ROUTER) {
          is_smart_router = true;
        }
      });
    });
    if (is_netbanking || is_wallet || is_currency) {
      MAPPED_PROVIDERS = MAPPED_PROVIDERS.map((p) => {
        if (p.id == SMART_ROUTER && (!is_currency || is_wallet || is_netbanking)) {
          p.disabled = true;
          if (is_wallet) {
            p.disabled_message = 'Wallet is not supported on Smart Router.';
          } else {
            p.disabled_message =
              'Smart Router cannot be added as a provider for conditions with Net Banking currently.';
          }
        } else if (p.id != SMART_ROUTER) {
          if (is_wallet && selectedWallets.length != 0) {
            const { mapWalletProvider } = this.state;
            this.configureProvider('wallet', selectedWallets, mapWalletProvider, p);
          }
          if (is_currency && selectedCurrencies.length != 0) {
            const { mapCurrencyProvider } = this.state;
            this.configureProvider('currency', selectedCurrencies, mapCurrencyProvider, p);
          }
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

    return (
      <FullPageCover>
        <FullPageCoverHeader>
          <div className="container" style={{ hright: '56px' }}>
            <div className="panel" style={{ marginTop: '3px' }}>
              <div className="panel-body">
                <div className="row">
                  <div className="col-xs-4">
                    <h3>
                      {this.state.update ? 'Edit' : 'Create'} Rule{' '}
                      {/* {!this.state.update ? (
                        <a
                          class={`highlight know-more`}
                          target="_blank"
                          style={{ marginLeft: '10px', borderColor: '#EBEFF0', fontSize: '13px' }}
                        >
                          Know more
                          <i class="i i-external-link" style={{ marginLeft: '5px' }} />
                        </a>
                      ) : null} */}
                    </h3>
                  </div>
                  <div className="col-xs-5" />
                  <div className="col-xs-3">
                    <Link to={'/optimizer/rules'}>
                      <span
                        style={{
                          cursor: 'pointer',
                          color: 'rgba(22, 47, 86, 0.54)',
                          fontSize: '17px',
                          fontWeight: 600,
                        }}
                        class="pull-right"
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
          <div class="navigator--create-rule">
            {this.state.loading ? (
              <div class="page-spinner-container">
                <Spinner />
              </div>
            ) : (
              <Fragment>
                {this.state.steps[1].show || this.state.update ? (
                  !this.state.rule.is_default ? (
                    <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                      <div
                        class={`panel gateway-list rule-detail ${
                          this.state.steps[1].edit ? 'active' : ''
                        }`}
                      >
                        <div class="panel-header">
                          <h2 class="payment-gateway-title">
                            Rule Details
                            {!this.state.steps[1].edit ? (
                              <button
                                onClick={() => {
                                  const edit = this.state.steps[1].edit;
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
                              <span class="pull-right step-text" style={{ fontSize: '16px' }}>
                                {' '}
                                Step 1 Out Of 4
                              </span>
                            )}
                          </h2>
                          {this.state.steps[1].edit ? (
                            <p class="desc">Add a name and description for your custom rule.</p>
                          ) : null}
                        </div>
                        <div class="panel-body">
                          <div class="row">
                            <div className="col-xs-12">
                              <div className="row">
                                <div className="col-xs-2">
                                  <label for="name" style={{ textAlign: 'left' }}>
                                    Rule Name
                                  </label>
                                </div>
                                <div className="col-xs-6">
                                  {!this.state.steps[1].edit ? (
                                    <div class="stepper-readonly">
                                      {removeMid(this.state.rule.name)}
                                    </div>
                                  ) : (
                                    <Input
                                      id="name"
                                      class="Input--vLeft"
                                      name="name"
                                      value={removeMid(this.state.rule.name)}
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
                                  {!this.state.steps[1].edit ? (
                                    <div class="stepper-readonly">
                                      {this.state.rule.description}
                                    </div>
                                  ) : (
                                    <textarea
                                      id="description"
                                      class="Input--vLeft form-control"
                                      value={this.state.rule.description}
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
                        {this.state.steps[1].edit ? (
                          <div className="panel-footer">
                            <button
                              disabled={
                                !(
                                  removeMid(this.state.rule.name) &&
                                  this.state.rule.name !== appendMid(DEFAULT_RULE)
                                )
                              }
                              onClick={() => {
                                this.goNext(1, () => {
                                  if (this.state.rule.is_default) {
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

                {this.state.steps[2].show || this.state.update ? (
                  <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                    <div class={`panel gateway-list ${this.state.steps[2].edit ? 'active' : ''}`}>
                      <div class="panel-header">
                        <h2 class="payment-gateway-title">
                          Rule Conditions
                          {!this.state.rule.is_default ? (
                            !this.state.steps[2].edit ? (
                              <button
                                onClick={() => {
                                  const edit = this.state.steps[2].edit;
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
                              <span class="pull-right step-text" style={{ fontSize: '16px' }}>
                                {' '}
                                Step 2 Out Of 4
                              </span>
                            )
                          ) : null}
                        </h2>
                        {this.state.steps[2].edit ? (
                          <p class="desc">
                            {this.state.rule.is_default ? (
                              `Default rule will be used as a rule to route transactions that do not satisfy any other rules.`
                            ) : (
                              <span>
                                Add conditions to identify payments.{' '}
                                {/* <a class="nav-link">Learn More</a>{' '} */}
                              </span>
                            )}
                          </p>
                        ) : this.state.rule.is_default ? (
                          <p class="desc">
                            Default rule will be used as a rule to route transactions that{' '}
                            <b>do not satisfy any other rules.</b>
                          </p>
                        ) : null}
                      </div>
                      {!this.state.rule.is_default ? (
                        <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                          <div class="panel-body" style={{ paddingTop: '15px !important' }}>
                            <Precondition
                              parameters={PARAMETERS}
                              readonly={!this.state.steps[2].edit}
                              precondition={this.state.rule.precondition}
                              update={(precondition) => {
                                this.setState((prevState) => {
                                  const rule = prevState.rule;
                                  rule.precondition = precondition;
                                  return { rule };
                                });
                              }}
                              parent={'create-rule'}
                            />
                          </div>
                        </CSSTransition>
                      ) : null}

                      {this.state.steps[2].edit ? (
                        <div className="panel-footer">
                          <button
                            onClick={() => {
                              this.goNext(2);
                              if (this.state.rule.rules.length == 0 && !this.state.update) {
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

                {this.state.steps[3].show || this.state.update ? (
                  <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                    <div class={`panel gateway-list ${this.state.steps[3].edit ? 'active' : ''}`}>
                      <div class="panel-header">
                        <h2 class="payment-gateway-title">
                          Target Payment Provider
                          {!this.state.steps[3].edit ? (
                            <button
                              onClick={() => {
                                const edit = this.state.steps[3].edit;
                                this.updateStep(3, {
                                  edit: !edit,
                                });
                              }}
                              className="pull-right no-border create-rule-act"
                            >
                              {' '}
                              <i className="i i-pencil-edit" /> Edit Target Provider
                            </button>
                          ) : !this.state.rule.is_default ? (
                            <span class="pull-right step-text" style={{ fontSize: '16px' }}>
                              {' '}
                              Step 3 Out Of 4
                            </span>
                          ) : null}
                        </h2>
                        {this.state.steps[3].edit ? (
                          <p class="desc">
                            Add desired payment provider through which the payment has to be routed.{' '}
                            {/* <a class="nav-link">Learn More</a> */}
                          </p>
                        ) : null}
                      </div>
                      <ProviderRules
                        parent={'create-rule'}
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
                        rules={mapRulesArrayToObject(this.state.rule.rules)}
                        readonly={!this.state.steps[3].edit}
                      />
                      {this.state.steps[3].edit ? (
                        <div class="panel-body add-exp-cont" style={{ padding: '0px 20px 6px' }}>
                          <div class="row">
                            <div className="col-xs-12">
                              <div class="add-expression" style={{ color: '#2B83EA' }}>
                                <b onClick={this.addPriority} class="pointer">
                                  Add Priority{' '}
                                  {!this.state.rule.is_default ? (
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
                      {this.state.steps[3].edit ? (
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
                {this.state.steps[4].show || this.state.update ? (
                  <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                    <div
                      style={{ paddingRight: '28px', paddingLeft: '28px', marginBottom: '80px' }}
                      class={`panel gateway-list ${this.state.steps[4].edit ? 'active' : ''}`}
                    >
                      <div class="panel-header">
                        <h2 class="payment-gateway-title" style={{ marginLeft: 0 }}>
                          Confirm Rule
                        </h2>
                        <p class="desc" style={{ marginLeft: 0 }}>
                          Publish the rule or save as draft to publish later.{' '}
                          {/* <a className="nav-link"> Learn More</a>{' '} */}
                        </p>
                      </div>
                      {this.state.steps[4].edit ? (
                        <div
                          className="panel-footer"
                          style={{
                            borderTop: '1px solid #e3dede',
                            paddingRight: 0,
                            paddingTop: '30px',
                          }}
                        >
                          {!this.state.update ? (
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
                                                    getRule(this.state.rule.id).then((R) =>
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
                                {getRuleStatus(this.state.rule) === 'test'
                                  ? `Publish Rule`
                                  : `Publish Edits`}
                              </button>
                              {getRuleStatus(this.state.rule) === 'live' ? (
                                !this.state.rule.is_default ? (
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
                                            .changeRuleMode(this.state.rule.id, 'test')
                                            .then(() => {
                                              return getRule(this.state.rule.id);
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
