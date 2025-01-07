import React, { Fragment, useEffect, useReducer } from 'react';
import { Box, Spinner as BladeSpinner } from '@razorpay/blade/components';
import { useLocation, Link, Navigate } from 'react-router-dom';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import { deepClone } from 'common/utils/rzp-utils';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { Precondition } from './Precondition';
import { ProviderRules } from './ProviderRules';
import DeactivateRule from './DeactivateRule';
import { showNotification } from 'merchant_common/reducers/notifications';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Spinner from 'common/ui/Spinner';
import { CSSTransition } from 'react-transition-group';
import { compose, bindActionCreators } from 'redux';

import {
  changeRuleMode,
  fetchRule,
  fetchRules,
  reorderRules,
  updateRule,
  deleteRule,
  createRule,
} from 'merchant/reducers/navigator/details';
import ReorderRules from './ReorderRule';
import { PreconditionModel } from 'merchant/views/Optimizer/Rules/models/PreconditionModel';
import { FullPageCover } from './FullPageCover';
import { FullPageCoverHeader } from './FullPageCoverHeader';

import {
  PARAMETERS,
  SMART_ROUTER,
  RZP_GATEWAYS,
  getUnique,
  createMappedProviders,
  isExpressionValid,
  mapRulesArrayToObject,
  mapRulesObjectToArray,
  appendMid,
  setRuleMode,
  TOTAL_RULE_LIMIT,
  removeMid,
  liveRulesList,
  DEFAULT_RULE,
  getRuleStatus,
} from 'merchant/views/Optimizer/utils';
import { MappedProiders, Rule, RuleGroup } from 'merchant/views/Optimizer/types';

const reducer = (state, action) => {
  switch (action.type) {
    case 'set_redirect':
      return { ...state, redirect: action.payload };
    case 'set_map_wallet_provider':
      return { ...state, mapWalletProvider: action.payload };
    case 'set_map_currency_provider':
      return { ...state, mapCurrencyProvider: action.payload };
    case 'set_map_methods_provider':
      return { ...state, mapMethodsProvider: action.payload };
    case 'set_parameters':
      return { ...state, parameters: action.payload };
    case 'set_rule_details':
      return { ...state, ruleDetails: action.payload };
    case 'set_rule_details_fields': {
      const rule = { ...state.ruleDetails };
      const { name, value } = action.payload;
      rule[name] = value;
      return { ...state, ruleDetails: { ...rule } };
    }
    case 'set_loading':
      return { ...state, loading: action.payload };
    case 'set_update':
      return { ...state, update: action.payload };
    case 'set_steps':
      return { ...state, steps: action.payload };
    case 'set_update_steps': {
      const { index, data } = action.payload;
      const steps = { ...state.steps };
      Object.keys(steps).forEach((k) => {
        steps[k].edit = false;
      });
      steps[index] = { ...steps[index], ...data };
      return { ...state, steps };
    }
    case 'set_next_step': {
      let { index } = action.payload;
      const steps = { ...state.steps };
      if (index === 1 && state.ruleDetails?.is_default) {
        steps[index] = {
          edit: false,
          show: true,
        };
        index = 2;
      }
      steps[index] = {
        edit: false,
        show: true,
      };
      steps[index + 1] = {
        show: true,
        edit: true,
      };
      return { ...state, steps };
    }
    default:
      return state;
  }
};

const initialState = {
  loading: false,
  update: false,
  redirect: null,
  ruleDetails: {
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
  parameters: [],
  mapWalletProvider: {},
  mapCurrencyProvider: {},
  mapMethodsProvider: {},
};

const CreateRule = (props, context): JSX.Element => {
  const location = useLocation();

  const {
    rulesList,
    ruleLoading,
    ruleDetailLoading,
    terminalProviders,
    openModal,
    showNotification,
    updateRule,
    fetchRule,
    fetchRules,
    closeModal,
    deleteRule,
    reorderRules: reorderRulesAction,
    createRule: createRuleAction,
    changeRuleMode,
  } = props;

  const [state, dispatch] = useReducer(reducer, initialState);
  const {
    ruleDetails,
    parameters,
    mapMethodsProvider,
    mapWalletProvider,
    mapCurrencyProvider,
    redirect,
    update,
    loading,
    steps,
  } = state;

  const escFunction = (event) => {
    if (event.keyCode === 27) {
      dispatch({ type: 'set_redirect', payload: '/optimizer/rules' });
    }
  };

  const getAllWallets = (): Promise<{
    wallets: { value: string }[];
    currency: { value: string }[];
  }> => {
    return new Promise((resolve) => {
      const data: string[] = [];
      const currencyData: string[] = [];
      const mapData: { [key: string]: string[] } = {};
      const mapCurrencyData: { [key: string]: string[] } = {};
      const mapMethodData: { [key: string]: string[] } = {};

      terminalProviders.forEach((provider) => {
        let key = `${provider.Gateway}_${provider.Terminal_id}`;
        if (RZP_GATEWAYS.includes(provider.Gateway)) {
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
      dispatch({ type: 'set_map_wallet_provider', payload: mapData });
      dispatch({ type: 'set_map_currency_provider', payload: mapCurrencyData });
      dispatch({ type: 'set_map_methods_provider', payload: mapMethodData });
      resolve(result);
    });
  };

  useEffect(() => {
    const params = deepClone(PARAMETERS);
    getAllWallets()
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
      .finally(() => {
        dispatch({ type: 'set_parameters', payload: params });
      });
  }, [terminalProviders]);

  useEffect(() => {
    document.addEventListener('keydown', escFunction);

    const ruleId = location.pathname.split('/').pop();
    if (ruleId && ruleId !== 'create-rule') {
      fetchRule(ruleId)
        .then((rule) => {
          dispatch({ type: 'set_rule_details', payload: rule });
          dispatch({ type: 'set_update', payload: true });
          const newSteps = {
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
          };
          dispatch({ type: 'set_steps', payload: newSteps });
        })
        .catch((e) => {
          showNotification({
            type: 'error',
            message: e.errors[0],
          });
        })
        .finally(() => {
          dispatch({ type: 'set_loading', payload: false });
        });
    }
    return () => {
      document.removeEventListener('keydown', escFunction);
    };
  }, []);

  const updateStep = (index: number, data: { [key: string]: boolean }) => {
    dispatch({ type: 'set_update_steps', payload: { index, data } });
  };

  const deactivateRule = (): Promise<any> => {
    return new Promise((res) => {
      openModal({
        size: 'large',
        component: (
          <DeactivateRule
            ruleGroups={liveRulesList(rulesList)}
            onSuccess={(e) => {
              res(e);
            }}
          />
        ),
      });
    });
  };

  const isPreconditionValid = (): boolean => {
    let isValid = true;
    const precondition = ruleDetails?.precondition;
    if (precondition) {
      if (precondition?.type == 'logical') {
        precondition.operands?.forEach((e) => {
          if (!isExpressionValid(e)) {
            isValid = false;
          }
        });
      } else if (!isExpressionValid(precondition)) {
        isValid = false;
      }
    }
    return isValid;
  };

  const isProviderRulesValid = (): boolean => {
    let isValid = true;
    const rules = mapRulesArrayToObject(ruleDetails.rules);
    if (ruleDetails.rules.length === 0) {
      isValid = false;
    }
    Object.keys(rules).forEach((k) => {
      let total_load = 0;
      rules[k].forEach((r) => {
        const load = Number(r.additional_attribute[1]?.value);
        total_load += load;
        if (!(isExpressionValid(r.expression.operands[0]) && load > 0)) {
          isValid = false;
        }
      });
      if (total_load !== 100) {
        isValid = false;
      }
    });
    return isValid;
  };

  const isRuleValid = () => {
    const { name, is_default, rules } = ruleDetails;
    return (
      name &&
      (is_default ? true : name !== appendMid(DEFAULT_RULE)) &&
      rules.length &&
      (is_default ? true : isPreconditionValid()) &&
      isProviderRulesValid()
    );
  };

  const isSelectedValidProvider = (mappedProviders: MappedProiders[]): boolean => {
    let isValid = true;
    const rules = mapRulesArrayToObject(ruleDetails.rules);
    Object.keys(rules).forEach((k) => {
      rules[k].forEach((r) => {
        if (
          r.expression.operands &&
          r.expression.operands[0].operands &&
          r.expression.operands[0].operands[0].value === '$provider.id'
        ) {
          const provider = r.expression.operands[0].operands[1].value;
          mappedProviders.forEach((p) => {
            if (p.id === provider && p.disabled) {
              isValid = false;
            }
          });
        }
      });
    });
    return isValid;
  };

  const addNewRow = (provider_priority: number) => {
    const rule = { ...ruleDetails };
    const rules = mapRulesArrayToObject(rule.rules);
    const newRules: any[] = [];
    let load = 100;
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
                  type: 'string',
                  operands: null,
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
        skip_on_failure: false,
      });
      Object.keys(rules).forEach((key) => {
        newRules.push(...rules[key]);
      });
      rule.rules = newRules;
      dispatch({ type: 'set_rule_details', payload: rule });
    }
  };

  const createRule = (data) => {
    return createRuleAction(data);
  };

  const goNext = (index: number) => {
    dispatch({ type: 'set_next_step', payload: { index } });
  };

  const reqBody = (mode: string) => {
    const rule = deepClone(ruleDetails);
    rule.rules.forEach((r) => {
      const random = getUnique();
      r.name = `${random}_${rule.name}`;
    });
    const req = {
      id: '0',
      name: rule.name,
      precondition: rule.precondition,
      rules: setRuleMode(rule.rules, mode),
      description: rule.description,
      strategy: 'default',
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

  const cleanRule = (rule) => {
    rule.rules.forEach((r) => {
      const random = getUnique();
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

  const addPriority = () => {
    const pp = Object.keys(mapRulesArrayToObject(ruleDetails.rules)).length + 1;
    addNewRow(pp);
  };

  const configureProvider = (
    param: string,
    selectedValues: string[],
    mapProvider: MappedProiders[],
    p: MappedProiders,
  ) => {
    let isDisable = true;
    selectedValues?.forEach((value) => {
      let val = value;
      if (value === 'upi_intent' || value === 'upi_collect') {
        val = 'upi';
      }
      if (mapProvider[p.id]?.includes(val)) {
        isDisable = false;
      }
    });
    if (isDisable) {
      p.disabled = true;
      p.disabled_message = `Selected ${param} is not supported on this provider.`;
    }
  };

  const findSelectedValues = (operands, checkParam: string, selectedValues: string[]) => {
    if (operands[0].value === checkParam && operands[1].value != '') {
      selectedValues.push(...operands[1].value.split(','));
    }
  };

  const checkMethods = (
    operands,
    selectedWallets: string[],
    selectedCurrencies: string[],
    selectedMethods: string[],
  ): {
    isWallet: boolean;
    isCurrency: boolean;
    isMethod: boolean;
  } => {
    let isWallet = false;
    let isCurrency = false;
    let isMethod = false;
    if (operands?.length > 0) {
      if (operands[0]?.value === '$payment.optimizer_wallet') {
        isWallet = true;
        findSelectedValues(operands, '$payment.optimizer_wallet', selectedWallets);
      }
      if (operands[0]?.value === '$payment.optimizer_currency') {
        isCurrency = true;
        findSelectedValues(operands, '$payment.optimizer_currency', selectedCurrencies);
      }
      if (operands[0]?.value === '$payment.navigator_method') {
        isMethod = true;
        findSelectedValues(operands, '$payment.navigator_method', selectedMethods);
      }
    }
    return {
      isWallet,
      isCurrency,
      isMethod,
    };
  };

  const changeRuleFields = (e) => {
    const { name, value } = e.target;
    let val = value;
    if (name === 'name') {
      val = appendMid(value);
    }

    dispatch({ type: 'set_rule_details_fields', payload: { name, value: val } });
  };

  const changeRules = (rules: { [key: string]: Rule[] }) => {
    const rule = { ...ruleDetails };
    const newRule: { [key: string]: Rule[] } = {};
    Object.keys(rules).forEach((k, index) => {
      const ni = index + 1;
      rules[k].forEach((r) => {
        r.additional_attribute[0].value = `${ni}`;
        r.score = rule.score;
      });
      newRule[ni] = rules[k];
    });
    const value: Rule[] = mapRulesObjectToArray(newRule);

    dispatch({ type: 'set_rule_details_fields', payload: { name: 'rules', value } });
  };

  const rules: RuleGroup[] = deepClone(rulesList);

  let mappedProviders: MappedProiders[] = createMappedProviders(terminalProviders);

  const selectedWallets = [];
  const selectedCurrencies = [];
  const selectedMethods = [];
  let isSmartRouter = false;
  let isWallet = false;
  let isCurrency = false;
  let isMethod = false;
  if (ruleDetails?.precondition) {
    if (ruleDetails?.precondition?.type === 'logical') {
      ruleDetails?.precondition?.operands?.forEach((o) => {
        const methodResult = checkMethods(
          o.operands,
          selectedWallets,
          selectedCurrencies,
          selectedMethods,
        );
        isWallet = isWallet || methodResult.isWallet;
        isCurrency = isCurrency || methodResult.isCurrency;
        isMethod = isMethod || methodResult.isMethod;
      });
    } else {
      const operands = ruleDetails?.precondition?.operands;
      const methodResult = checkMethods(
        operands,
        selectedWallets,
        selectedCurrencies,
        selectedMethods,
      );
      isWallet = isWallet || methodResult.isWallet;
      isCurrency = isCurrency || methodResult.isCurrency;
      isMethod = isMethod || methodResult.isMethod;
    }
  }

  ruleDetails.rules.forEach((rule) => {
    rule.expression.operands?.forEach((o) => {
      if (o.operands && o.operands[1].value == SMART_ROUTER) {
        isSmartRouter = true;
      }
    });
  });
  if (isWallet || isCurrency || isMethod) {
    mappedProviders = mappedProviders.map((p) => {
      if (isMethod) {
        configureProvider('method', selectedMethods, mapMethodsProvider, p);
      }
      if (isWallet && selectedWallets.length !== 0) {
        configureProvider('wallet', selectedWallets, mapWalletProvider, p);
      }
      if (isCurrency && selectedCurrencies.length !== 0) {
        configureProvider('currency', selectedCurrencies, mapCurrencyProvider, p);
      }
      return p;
    });
  }
  if (isSmartRouter) {
    parameters.forEach((p) => {
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
  const isValidProvider = isSelectedValidProvider(mappedProviders);

  const publishRule = () => {
    let rule = reqBody('test');
    const score = rules.length + 1;
    rule.rules.forEach((r) => {
      r.score = score;
    });
    let promise;
    // if (true) {
    if (liveRulesList(rules).length >= TOTAL_RULE_LIMIT) {
      promise = deactivateRule().then((deactivated_rule) => {
        return changeRuleMode(deactivated_rule.id, 'test').then(() => {
          rules.forEach((r, i) => {
            if (r.id == deactivated_rule.id) {
              rules.splice(i, 1);
            }
          });
          closeModal();
          return createRule(rule);
        });
      });
    } else {
      promise = createRule(rule);
    }
    promise
      .then((respRule) => {
        rule = { ...respRule, current: true };
        openModal({
          size: 'large',
          component: (
            <ReorderRules
              ruleGroups={[...rules.filter((r) => r.id !== rule.id), rule]}
              onSuccess={(orderedRules) => {
                reorderRulesAction(orderedRules, rule).then(() => {
                  showNotification({
                    type: 'success',
                    message: 'Rule made live successully',
                    closeTimeout: 5000,
                  });
                  closeModal();
                  dispatch({
                    type: 'set_redirect',
                    payload: '/optimizer/rules',
                  });
                });
              }}
              onClose={() => {
                context
                  .confirm({
                    header: 'Are you sure you want to close?',
                    message: `Rule will not be published live.`,
                    affirmativeLabel: 'Confirm',
                    abortLabel: 'Cancel',
                  })
                  .then(() => {
                    return deleteRule(rule.id);
                  })
                  .then(() => {
                    closeModal();
                  });
              }}
            />
          ),
        });
      })
      .catch((e) => {
        showNotification({
          type: 'error',
          message: e.errors[0],
        });
      });
  };

  const saveDraftRule = () => {
    const rule = reqBody('test');
    const score = rules.length + 1;
    context
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
        return createRule(rule);
      })
      .then(() => {
        showNotification({
          type: 'success',
          message: 'Rule saved as draft successully',
          closeTimeout: 5000,
        });
        dispatch({
          type: 'set_redirect',
          payload: '/optimizer/rules',
        });
      })
      .catch((e) => {
        showNotification({
          type: 'error',
          message: e.errors[0],
        });
      });
  };

  const publishSavedRule = () => {
    const rule = cleanRule(ruleDetails);
    rule.rules.forEach((r) => {
      const temp = r.name.split('_');
      temp.pop();
      temp.pop();
      temp.push(rule.name);
      r.name = temp.join('_');
    });
    context
      .confirm({
        header: 'Are you sure you want to publish edits?',
        message: null,
        affirmativeLabel: `Yes, Publish`,
        abortLabel: 'Cancel',
      })
      .then(() => {
        return updateRule(rule);
      })
      .then(() => {
        showNotification({
          type: 'success',
          message: 'Rule has been updated successully',
          closeTimeout: 5000,
        });
        let promise;
        if (liveRulesList(rules).length >= TOTAL_RULE_LIMIT && getRuleStatus(rule) === 'test') {
          promise = deactivateRule().then((deactivated_rule) => {
            return changeRuleMode(deactivated_rule.id, 'test').then(() => {
              rules.forEach((r, i) => {
                if (r.id == deactivated_rule.id) {
                  rules.splice(i, 1);
                }
              });
              return fetchRules();
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
          openModal({
            size: 'large',
            component: (
              <ReorderRules
                ruleGroups={rules}
                onSuccess={(orderedRules) => {
                  let args;
                  if (getRuleStatus(rule) === 'test') {
                    args = [orderedRules, rule];
                  } else {
                    args = [orderedRules];
                  }
                  reorderRulesAction(...args)
                    .then(() => {
                      closeModal();
                      showNotification({
                        type: 'success',
                        message: 'Rule made live successfully',
                        closeTimeout: 5000,
                      });
                      dispatch({
                        type: 'set_redirect',
                        payload: '/optimizer/rules',
                      });
                    })
                    .catch((e) => {
                      showNotification({
                        type: 'error',
                        message: e.errors[0],
                      });
                    });
                }}
                onClose={closeModal}
              />
            ),
          });
        } else {
          showNotification({
            type: 'success',
            message: 'Rule has been updated successully',
            closeTimeout: 5000,
          });
          dispatch({
            type: 'set_redirect',
            payload: '/optimizer/rules',
          });
        }
      })
      .catch((e) => {
        showNotification({
          type: 'error',
          message: e.errors[0],
        });
      });
  };

  const handleDeactivateRule = () => {
    context.confirm({
      header: 'Are you sure want to deactivate this rule?',
      message: () => (
        <p style={{ marginBottom: '15px' }}>Rule will be saved in draft after deactivation .</p>
      ),
      affirmativeLabel: 'Yes, Deactivate',
      affirmativePendingLabel: 'Deactivating...',
      abortLabel: 'Cancel',
      action: () => {
        changeRuleMode(ruleDetails.id, 'test')
          .then(() => {
            return fetchRule(ruleDetails.id);
          })
          .then(() => fetchRules())
          .then((respRules) => {
            const findRule = respRules.find((r) => r.id == ruleDetails.id);
            dispatch({
              type: 'set_rule_details',
              payload: findRule,
            });
            showNotification({
              type: 'success',
              message: 'Rule deactivated successully',
              closeTimeout: 5000,
            });
            dispatch({
              type: 'set_redirect',
              payload: '/optimizer/rules',
            });
          })
          .catch((e) => {
            showNotification({
              type: 'error',
              message: e.errors[0],
            });
          });
      },
    });
  };

  const updateSavedRuleAsDraft = () => {
    const rule = cleanRule(ruleDetails);
    rule.rules.forEach((r) => {
      const temp = r.name.split('_');
      temp.pop();
      temp.pop();
      temp.push(rule.name);
      r.name = temp.join('_');
    });
    context
      .confirm({
        header: 'Are you sure want to update draft rule?',
        // message: `Rule will be saved in draft at ${score} priority.`,
        affirmativeLabel: `Yes, Update`,
        abortLabel: 'Cancel',
      })
      .then(() => {
        return updateRule(rule);
      })
      .then(() => {
        showNotification({
          type: 'success',
          message: 'Draft rule has been updated',
          closeTimeout: 5000,
        });
        dispatch({
          type: 'set_redirect',
          payload: '/optimizer/rules',
        });
      })
      .catch((e) => {
        showNotification({
          type: 'error',
          message: e.errors[0],
        });
      });
  };

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
      {ruleDetailLoading ? (
        <Box>
          <BladeSpinner
            color="primary"
            size="xlarge"
            accessibilityLabel="loader"
            marginLeft="50%"
            marginTop="10%"
          />
        </Box>
      ) : (
        <div className="container">
          <div className="navigator--create-rule">
            {loading ? (
              <div className="page-spinner-container">
                <Spinner center={false} />
              </div>
            ) : (
              <Fragment>
                {steps?.[1]?.show || update ? (
                  !ruleDetails.is_default ? (
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
                                  updateStep(1, {
                                    edit: !steps?.[1].edit,
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
                                  <label style={{ textAlign: 'left' }}>Rule Name</label>
                                </div>
                                <div className="col-xs-6">
                                  {!steps?.[1].edit ? (
                                    <div className="stepper-readonly">
                                      {removeMid(ruleDetails.name)}
                                    </div>
                                  ) : (
                                    <Input
                                      id="name"
                                      className="Input--vLeft"
                                      name="name"
                                      value={removeMid(ruleDetails.name)}
                                      placeholder="Rule Name"
                                      onChange={changeRuleFields}
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
                                  <label style={{ textAlign: 'left' }}>Rule Description</label>
                                </div>
                                <div className="col-xs-6">
                                  {!steps?.[1].edit ? (
                                    <div className="stepper-readonly">
                                      {ruleDetails.description}
                                    </div>
                                  ) : (
                                    <textarea
                                      id="description"
                                      className="Input--vLeft form-control"
                                      value={ruleDetails.description}
                                      name="description"
                                      placeholder="Rule Description"
                                      onChange={changeRuleFields}
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
                                !(
                                  removeMid(ruleDetails.name) &&
                                  ruleDetails.name !== appendMid(DEFAULT_RULE)
                                )
                              }
                              onClick={() => {
                                goNext(1);
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
                          {!ruleDetails.is_default ? (
                            !steps?.[2].edit ? (
                              <button
                                onClick={() => {
                                  updateStep(2, { edit: !steps?.[2].edit });
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
                            {ruleDetails.is_default ? (
                              `Default rule will be used as a rule to route transactions that do not satisfy any other rules.`
                            ) : (
                              <span>
                                Add conditions to identify payments.{' '}
                                {/* <a class="nav-link">Learn More</a>{' '} */}
                              </span>
                            )}
                          </p>
                        ) : ruleDetails.is_default ? (
                          <p className="desc">
                            Default rule will be used as a rule to route transactions that{' '}
                            <b>do not satisfy any other rules.</b>
                          </p>
                        ) : null}
                      </div>
                      {!ruleDetails.is_default ? (
                        <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                          <div className="panel-body" style={{ paddingTop: '15px !important' }}>
                            <Precondition
                              parameters={parameters}
                              readonly={!steps?.[2].edit}
                              precondition={ruleDetails.precondition}
                              update={(precondition) => {
                                changeRuleFields({
                                  target: {
                                    name: 'precondition',
                                    value: precondition,
                                  },
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
                              goNext(2);
                              if (ruleDetails.rules.length == 0 && !update) {
                                addPriority();
                              }
                            }}
                            disabled={!isPreconditionValid()}
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
                                updateStep(3, { edit: !steps?.[3].edit });
                              }}
                              className="pull-right no-border create-rule-act"
                            >
                              {' '}
                              <i className="i i-pencil-edit" /> Edit Target Provider
                            </button>
                          ) : !ruleDetails.is_default ? (
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
                        providers={mappedProviders}
                        addNewRow={(pp) => addNewRow(pp)}
                        update={(rules) => changeRules(rules)}
                        rules={mapRulesArrayToObject(ruleDetails.rules)}
                        readonly={!steps?.[3].edit}
                      />
                      {steps?.[3].edit ? (
                        <div className="panel-body add-exp-cont">
                          <div className="row">
                            <div className="col-xs-12">
                              <div className="add-expression">
                                <b onClick={addPriority} className="pointer">
                                  Add Priority{' '}
                                  {!ruleDetails.is_default ? (
                                    <span>
                                      <i className="i i-info-outline add-priority-info-c" />
                                      <Popover theme="dark" align="bottom">
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
                            onClick={() => goNext(3)}
                            className="btn btn-primary pull-right"
                            disabled={!isProviderRulesValid() || !isValidProvider}
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
                                onClick={publishRule}
                                disabled={!isRuleValid() || ruleLoading}
                                className="btn btn-primary pull-right"
                              >
                                {!ruleLoading ? 'Publish Rule' : 'Please Wait..'}
                              </button>
                              <button
                                style={{ marginRight: '10px' }}
                                onClick={saveDraftRule}
                                disabled={!isRuleValid()}
                                className="btn btn-outline pull-right no-border create-rule-act"
                              >
                                Save as Draft
                              </button>
                            </Fragment>
                          ) : (
                            <Fragment>
                              <button
                                onClick={publishSavedRule}
                                disabled={!isRuleValid()}
                                className="btn btn-primary pull-right"
                              >
                                {getRuleStatus(ruleDetails) === 'test'
                                  ? `Publish Rule`
                                  : `Publish Edits`}
                              </button>
                              {getRuleStatus(ruleDetails) === 'live' ? (
                                !ruleDetails.is_default ? (
                                  <button
                                    style={{ marginRight: '10px' }}
                                    onClick={handleDeactivateRule}
                                    disabled={ruleLoading}
                                    className="btn btn-outline pull-right no-border create-rule-act"
                                  >
                                    Deactivate Rule
                                  </button>
                                ) : null
                              ) : (
                                <button
                                  style={{ marginRight: '10px' }}
                                  onClick={updateSavedRuleAsDraft}
                                  disabled={ruleLoading}
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
      )}
    </FullPageCover>
  );
};

CreateRule.contextTypes = {
  confirm: Function,
};

const mapStateToProps = (state) => {
  const { rules, terminalProviders, create_rule_loading, rule_detail_loading } = state.navigator;
  return {
    rulesList: rules,
    ruleLoading: create_rule_loading,
    ruleDetailLoading: rule_detail_loading,
    terminalProviders,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      openModal,
      showNotification,
      updateRule,
      fetchRule,
      fetchRules,
      closeModal,
      deleteRule,
      reorderRules,
      createRule,
      changeRuleMode,
    },
    dispatch,
  );
};

export default compose<any>(connect(mapStateToProps, mapDispatchToProps))(CreateRule);
