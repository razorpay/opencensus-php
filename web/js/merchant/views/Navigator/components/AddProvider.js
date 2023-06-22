import React from 'react';
import { connect } from 'react-redux';
import { withRouter, Link, Redirect } from 'react-router-dom';
import qs from 'query-string';
import { CSSTransition } from 'react-transition-group';

import Spinner from 'common/ui/Spinner';
import { deepClone } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { trackOptimizerEvents, trackAPIResults } from 'merchant/views/Navigator/track';
import { addProvider, editProvider } from 'merchant/views/Navigator/service';

import FullPageCover from './FullPageCover';
import FullPageCoverHeader from './FullPageCoverHeader';
import { HowToGetDetails } from './Provider/HowToGetDetails';
import { Step1, Step2, Step3 } from './AddProvider/index';
import { getSelectedProviderWithAcquirer as getSelectedProvider } from './util';
import {
  INIT_PROVIDER_STATE,
  INIT_FORM_STATE,
  HAS_NETBANKING_FEATURES,
  HAS_UPI_FEATURES,
  NETBANKING_FEATURES,
  UPI_FEATURES,
  SKIP_VALIDATION_KEYS,
  SKIP_PAYTM_AUTO_DEBIT_VALIDATION_KEYS,
  WALLET_AUTO_DEBIT_KEY,
  PROVIDER_KEYS,
} from 'merchant/views/Navigator/constants';

@withRouter
@connect(
  (state) => {
    const { session, navigator } = state;
    return {
      user: session?.user,
      activeProviders: navigator?.terminalProviders,
    };
  },
  { openModal, closeModal, showNotification },
)
export default class AddProvider extends React.Component {
  state = {
    redirect: null,
    provider: deepClone(INIT_PROVIDER_STATE),
    terminalId: this.props?.match?.params?.id,
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
    },
    providers: {},
    selectedProvider: null,
    validationErrors: {},
    isEdit: false,
    loadingProviders: true,
    isSaving: false,
    isProviderNameValid: true,
    allDetailsValid: false,
  };

  componentDidMount() {
    const params = {
      url: 'terminals/proxy/optimizer/supported_gateways',
      method: 'get',
    };

    merchantFetch(params)
      .then((res) => {
        if (res?.success) {
          this.setState({ providers: res.data });
        }
      })
      .finally(() => {
        this.setState({ loadingProviders: false });
      });

    this.setInitialProvider();
  }

  componentDidUpdate(prevProps) {
    if (this.props.activeProviders?.length !== prevProps.activeProviders?.length) {
      this.setInitialProvider();
    }
  }

  setInitialProvider = () => {
    const { activeProviders } = this.props;
    const { terminalId } = this.state;

    if (terminalId && activeProviders?.length > 0) {
      const provider = activeProviders.find((item) => item?.Terminal_id === terminalId) || {};

      if (provider) {
        const { Gateway, Gateway_details } = provider;

        if (HAS_UPI_FEATURES.includes(Gateway)) {
          provider.Gateway_details.TPV = Gateway_details[UPI_FEATURES]?.tpv ?? 0;
        } else if (HAS_NETBANKING_FEATURES.includes(Gateway)) {
          provider.Gateway_details.TPV = Gateway_details[NETBANKING_FEATURES]?.tpv ?? 0;
        }

        this.setState({
          ...deepClone(INIT_FORM_STATE),
          selectedProvider: provider?.Gateway || '',
          provider,
          allDetailsValid: true,
        });
      }
    }
  };

  updateStep = (index, data) => {
    const self = this;
    return function update() {
      self.setState((prevState) => {
        const steps = prevState.steps;
        Object.keys(steps).forEach((k) => {
          steps[k].edit = false;
        });
        steps[index] = { ...steps[index], ...data };
        return { steps };
      });
    };
  };

  selectProvider = (provider) => {
    trackOptimizerEvents({
      objectName: 'gateway',
      actionName: 'select',
      properties: {
        gateway: provider,
      },
      screen: 'Optimizer Add Provider',
    });

    this.setState((prevState) => {
      const provider_st = prevState.provider;
      provider_st.Gateway = provider;
      provider_st.Provider_name = provider;

      const { activeProviders, user } = this.props;
      let num = 1;
      activeProviders.forEach((item) => {
        if (provider_st.Provider_name === item.Provider_name) {
          provider_st.Provider_name = `${provider}_${num}`;
          num++;
        }
      });

      // Paytm by default enable wallets
      if (provider === 'paytm') {
        if (user?.isPaytmAutoDebitEnabled) {
          provider_st.Gateway_details.ENABLE_AUTO_DEBIT = false;
        }
        if (prevState.providers?.paytm?.['Payment Methods']?.data_value?.indexOf('wallet') !== -1) {
          provider_st.Gateway_details['Payment Methods'] = ['wallet'];
          provider_st.Gateway_details.wallet_metadata = {
            wallets:
              prevState.providers?.[provider]?.['Payment Methods']?.meta_data?.wallet_metadata
                ?.wallets || [],
          };
        }
      }

      if ([...HAS_NETBANKING_FEATURES, ...HAS_UPI_FEATURES].includes(provider)) {
        provider_st.Gateway_details.TPV = 0;
      }

      return { selectedProvider: provider, provider: provider_st };
    });
  };

  disableStep = (step) => {
    const { providers, selectedProvider, provider, isProviderNameValid } = this.state;
    const selectedProviderWithAcquirer = this.getSelectedProviderWithAcquirer();

    // check if gateway level seamless option is enabled
    const seamlessOptionExist = providers?.[selectedProvider]?.hasOwnProperty(
      'optimizer_seamless_disabled',
    );

    // check if radio button is toggled
    const seamlessRadioValue = provider?.Gateway_details?.hasOwnProperty(
      'optimizer_seamless_disabled',
    );

    let isDisabled = false;
    switch (step) {
      case 1:
        if (!selectedProviderWithAcquirer) {
          isDisabled = true;
          break;
        }

        if (seamlessOptionExist && !seamlessRadioValue) {
          isDisabled = true;
        }
        break;
      case 2:
        if (
          !(provider?.Provider_name?.trim() && isProviderNameValid && provider?.Description?.trim())
        ) {
          isDisabled = true;
        }
        break;
      default:
        break;
    }

    return isDisabled;
  };

  goNext = (i) => {
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
    });
  };

  nextButton = (step) => {
    return (
      <div className="panel-footer">
        <button
          className="btn btn-primary pull-right"
          disabled={this.disableStep(step)}
          onClick={() => this.goNext(step)}
        >
          Next <i className="i i-arrow-forward" />
        </button>
        <div className="clearfix" />
      </div>
    );
  };

  changeGateway = () => {
    this.setState((prevState) => {
      const steps = Object.keys(prevState.steps).reduce((acc, key) => {
        acc[key] =
          key === '1'
            ? { edit: true, show: true }
            : { ...prevState.steps[key], edit: false, show: false };
        return acc;
      }, {});

      return { selectedProvider: null, provider: deepClone(INIT_PROVIDER_STATE), steps };
    });
  };

  toggleSeamless = (bool) => {
    const { isEdit, selectedProvider } = this.state;

    trackOptimizerEvents({
      screen: `Optimizer ${isEdit ? 'Update' : 'Add'} Provider`,
      objectName: 'Seamless option',
      actionName: 'select',
      properties: {
        gateway: selectedProvider,
        'Integration Type': bool ? 'Instant (beta)' : 'Server-to-Server',
      },
    });

    this.setState((prevState) => {
      const { Gateway_details, ...rest } = prevState.provider;

      return {
        provider: {
          ...rest,
          Gateway_details: {
            ...Gateway_details,
            optimizer_seamless_disabled: bool,
            ...(bool && {
              'Payment Methods': Gateway_details?.['Payment Methods'].filter((m) => m !== 'upi'),
            }),
          },
        },
      };
    });
  };

  changeProviderDetails = (name, val) => {
    this.setState((prevState) => {
      const provider = { ...prevState.provider };
      provider[name] = val;

      if (name === 'Provider_name') {
        let isProviderNameValid = true;
        const { activeProviders } = this.props;
        activeProviders?.forEach((item) => {
          if (val === item?.Provider_name && item?.Terminal_id !== provider?.Terminal_id) {
            isProviderNameValid = false;
          }
        });
        return { provider, isProviderNameValid };
      }

      return { provider };
    });
  };

  getSelectedProviderWithAcquirer = () => {
    const { providers, selectedProvider, provider } = this.state;
    return getSelectedProvider({
      providers,
      selectedProvider,
      provider,
    });
  };

  howtoGetDetails = () => {
    const { providers } = this.state;
    const selectedProviderWithAcquirer = this.getSelectedProviderWithAcquirer();
    this.props.openModal({
      size: 'large',
      component: (
        <HowToGetDetails
          providers={providers}
          selectedProvider={selectedProviderWithAcquirer}
          closeModal={this.props.closeModal}
        />
      ),
    });
  };

  checkAllValuesExist = () => {
    const { provider, providers } = this.state;
    const selectedProviderWithAcquirer = this.getSelectedProviderWithAcquirer();

    const { user } = this.props;
    let paytmAutoDebitEnabled = false;
    if (user?.isPaytmAutoDebitEnabled && selectedProviderWithAcquirer === 'paytm') {
      paytmAutoDebitEnabled = provider?.Gateway_details?.[WALLET_AUTO_DEBIT_KEY];
    }

    for (const key of Object.keys(providers[selectedProviderWithAcquirer])) {
      const value = provider?.Gateway_details?.[key];

      if (key === 'Payment Methods') {
        if (value?.length === 0) {
          return false;
        }
      } else if (
        user?.isPaytmAutoDebitEnabled &&
        selectedProviderWithAcquirer === 'paytm' &&
        key === WALLET_AUTO_DEBIT_KEY
      ) {
        if (provider?.Gateway_details?.[key]) {
          paytmAutoDebitEnabled = true;
        }
        // Paytm wallet auto debit not enabled then no need to check for the fields which are only required for wallet auto debit
      } else if (
        !(!paytmAutoDebitEnabled && SKIP_PAYTM_AUTO_DEBIT_VALIDATION_KEYS.includes(key)) &&
        !SKIP_VALIDATION_KEYS.includes(key) &&
        !value
      ) {
        return false;
      }
    }

    if (
      provider?.Gateway_details?.['Payment Methods'].indexOf('wallet') !== -1 &&
      provider?.Gateway_details?.wallet_metadata?.wallets?.length <= 0
    ) {
      return false;
    }

    return true;
  };

  changeGatewayDetails = (event, item) => {
    const { type, checked, value, id } = event.target;

    this.setState(
      (prevState) => {
        const { provider, providers, selectedProvider } = prevState;
        if (type === 'checkbox') {
          if (checked) {
            provider.Gateway_details['Payment Methods'] = [
              ...provider?.Gateway_details?.['Payment Methods'],
              item,
            ];
            if (item === 'wallet') {
              const selectedProviderWithAcquirer = this.getSelectedProviderWithAcquirer();
              provider.Gateway_details.wallet_metadata = {
                wallets: [
                  ...providers?.[selectedProviderWithAcquirer]?.['Payment Methods']?.meta_data
                    ?.wallet_metadata?.wallets,
                ],
              };
            }
          } else if (provider?.Gateway_details?.['Payment Methods']) {
            const isSodexoEnabled =
              selectedProvider === 'payu' &&
              providers?.[selectedProvider]?.hasOwnProperty(PROVIDER_KEYS.SODEXO);
            const index = provider.Gateway_details['Payment Methods'].indexOf(item);
            provider.Gateway_details['Payment Methods'] = [
              ...provider.Gateway_details['Payment Methods'].slice(0, index),
              ...provider.Gateway_details['Payment Methods'].slice(index + 1),
            ];
            if (item === 'wallet') {
              delete provider.Gateway_details.wallet_metadata;
            }
            if (item === 'card' && isSodexoEnabled) {
              provider.Gateway_details[PROVIDER_KEYS.SODEXO] = false;
            }
          }
        } else {
          provider.Gateway_details[id] = value;
        }

        return { provider };
      },
      () => this.validateGatewayDetails(id),
    );
  };

  toggleMethods = (event) => {
    const { id, checked } = event.target;
    this.setState((prevState) => {
      return {
        provider: {
          ...prevState.provider,
          Gateway_details: { ...prevState.provider.Gateway_details, [id]: checked },
        },
      };
    });
  };

  changeGatewayWallets = (event, wallet) => {
    const { checked } = event.target;
    this.setState((prevState) => {
      const { provider } = prevState;
      let prevSelectedWallets = provider?.Gateway_details?.wallet_metadata?.wallets || [];
      if (checked) {
        prevSelectedWallets = [...prevSelectedWallets, wallet];
      } else {
        const index = prevSelectedWallets.indexOf(wallet);
        prevSelectedWallets = [
          ...prevSelectedWallets.slice(0, index),
          ...prevSelectedWallets.slice(index + 1),
        ];
      }
      provider.Gateway_details.wallet_metadata.wallets = prevSelectedWallets;
      return { provider };
    });
  };

  changeEnableAutoDebitSwitch = (isChecked) => {
    this.setState((prevState) => {
      const { provider } = prevState;
      provider.Gateway_details.ENABLE_AUTO_DEBIT = isChecked;
      return { provider };
    });
  };

  validateGatewayDetails = (key) => {
    const { provider, providers, validationErrors } = this.state;
    const selectedProviderWithAcquirer = this.getSelectedProviderWithAcquirer();
    const {
      min_length: minLength,
      max_length: maxLength,
      meta_data,
    } = providers?.[selectedProviderWithAcquirer]?.[key] ?? {};
    const validationRegex = meta_data?.validation_regex;
    const checkVal = provider?.Gateway_details?.[key];
    const validErr = { ...validationErrors };

    if (checkVal) {
      if (validationRegex && !new RegExp(validationRegex).test(checkVal)) {
        validErr[key] = `Please enter valid value`;
      } else if (minLength && checkVal.length < minLength) {
        validErr[key] = `Please enter minimum ${minLength} characters value`;
      } else if (maxLength && maxLength !== 0 && checkVal.length > maxLength) {
        validErr[key] = `Please enter ${minLength} to ${maxLength} characters value`;
      } else {
        delete validErr[key];
      }
    } else {
      delete validErr[key];
    }

    this.setState({
      validationErrors: validErr,
      allDetailsValid: Object.keys(validErr).length === 0,
    });
  };

  onSubmit = async () => {
    const { provider, isEdit, selectedProvider } = this.state;
    const { closeModal, history, showNotification } = this.props;

    const Gateway_details = provider?.Gateway_details || {};

    if (Gateway_details.hasOwnProperty('TPV')) {
      const tpv = Number(Gateway_details?.TPV) ?? 0;

      if (HAS_UPI_FEATURES.includes(selectedProvider)) {
        const upiFeatures = Gateway_details?.[UPI_FEATURES] || {};

        Gateway_details[UPI_FEATURES] = { ...upiFeatures, tpv };
      } else if (HAS_NETBANKING_FEATURES.includes(selectedProvider)) {
        const netBanking = Gateway_details?.[NETBANKING_FEATURES] || {};

        Gateway_details[NETBANKING_FEATURES] = { ...netBanking, tpv };
      }

      delete Gateway_details?.TPV;
    }

    const payload = {
      ...provider,
      Gateway: this.getSelectedProviderWithAcquirer(),
      Gateway_details,
    };

    trackOptimizerEvents({
      screen: `Optimizer ${isEdit ? 'Update' : 'Add'} Provider`,
      objectName: `${isEdit ? 'update' : 'add'} provider submit`,
      actionName: 'click',
      properties: {
        gateway: payload?.Gateway,
        payment_methods: payload?.Gateway_details?.['Payment Methods'],
      },
    });

    this.setState({ isSaving: true });

    let res = null;

    try {
      if (isEdit) {
        delete payload.Currency;
        delete payload.Gateway_acquirer;

        res = await editProvider({ payload });
      } else {
        res = await addProvider({ payload });
      }

      if (res?.success) {
        closeModal();

        history.push('/optimizer/rules');

        showNotification({
          type: 'success',
          message: `${payload?.Provider_name} provider ${isEdit ? 'updated' : 'add'} successfully.`,
          closeTimeout: 5000,
        });

        trackAPIResults({
          name: `${isEdit ? 'Edit' : 'Add'} Provider`,
          properties: {
            success: true,
          },
        });
      }

      this.setState({ isSaving: false });
    } catch (error) {
      showNotification({
        type: 'error',
        message: error?.errors?.[0],
      });

      trackAPIResults({
        name: `${isEdit ? 'Edit' : 'Add'} Provider`,
        properties: {
          success: false,
          failureReason: error?.errors?.[0],
        },
      });

      this.setState({ isSaving: false });
    }
  };

  trackEventOnClose = () => {
    const { isEdit, selectedProvider, steps } = this.state;
    const currentStep = Object.keys(steps).find((key) => steps[key]?.edit);

    trackOptimizerEvents({
      objectName: `${isEdit ? 'Edit' : 'Add'} Provider`,
      actionName: 'close',
      screen: `Optimizer ${isEdit ? 'Edit' : 'Add'} Provider`,
      properties: {
        gateway: selectedProvider,
        Step: currentStep,
      },
    });
  };

  render() {
    const {
      redirect,
      providers,
      validationErrors,
      isEdit,
      isSaving,
      isProviderNameValid,
      allDetailsValid,
      loadingProviders,
      steps,
      provider,
      selectedProvider,
    } = this.state;
    const { user } = this.props;

    if (redirect) return <Redirect to={redirect} />;

    const _params = qs.parse(this.props?.location?.search);
    const onCloseLink = _params?.from ?? '/optimizer/rules';
    const selectedProviderWithAcquirer = this.getSelectedProviderWithAcquirer();

    return (
      <FullPageCover>
        <FullPageCoverHeader>
          <div className="container add-provider-container">
            <div className="panel">
              <div className="panel-body">
                <div className="row">
                  <div className="col-xs-4">
                    <h3 className="add-provider-title">{isEdit ? 'Edit' : 'Add'} Provider</h3>
                  </div>
                  <div className="col-xs-5" />
                  <div className="col-xs-3">
                    <Link to={onCloseLink} onClick={this.trackEventOnClose}>
                      <span className="pull-right add-provider-close">
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
            {loadingProviders ? (
              <div className="page-spinner-container">
                <Spinner />
              </div>
            ) : (
              <>
                {steps?.[1]?.show && (
                  <CSSTransition in appear timeout={800} classNames="slide-up">
                    <div
                      className={`panel gateway-list rule-detail${steps[1].edit ? ' active' : ''}`}
                    >
                      <div className="panel-header">
                        <h2 className="payment-gateway-title">
                          Select Gateway
                          {!steps?.[1]?.edit ? (
                            <button
                              onClick={this.updateStep(1, {
                                edit: !steps[1].edit,
                              })}
                              className="pull-right no-border create-rule-act"
                            >
                              <i className="i i-pencil-edit" /> Change Gateway
                            </button>
                          ) : (
                            <span className="pull-right step-text">Step 1 Out Of 3</span>
                          )}
                        </h2>
                        {steps[1].edit ? (
                          <p className="desc">Select a Gateway for your payment provider.</p>
                        ) : null}
                      </div>
                      <div className="panel-body">
                        <Step1
                          steps={steps}
                          providers={providers}
                          loadingProviders={loadingProviders}
                          selectedProvider={selectedProviderWithAcquirer}
                          gatewayDetails={provider?.Gateway_details}
                          selectProvider={this.selectProvider}
                          changeGateway={this.changeGateway}
                          toggleSeamless={this.toggleSeamless}
                          isEdit={isEdit}
                        />
                      </div>
                      {steps[1].edit ? this.nextButton(1) : null}
                    </div>
                  </CSSTransition>
                )}
                {steps?.[2]?.show || isEdit ? (
                  <CSSTransition in appear timeout={800} classNames="slide-up">
                    <div
                      className={`panel gateway-list rule-detail${steps[2].edit ? ' active' : ''}`}
                    >
                      <div className="panel-header">
                        <h2 className="payment-gateway-title">
                          Provider Details
                          {selectedProvider && !steps?.[2]?.edit ? (
                            <button
                              onClick={this.updateStep(2, {
                                edit: !steps[2].edit,
                              })}
                              className="pull-right no-border create-rule-act"
                            >
                              <i className="i i-pencil-edit" /> Edit Provider Details
                            </button>
                          ) : (
                            <span className="pull-right step-text">Step 2 Out Of 3</span>
                          )}
                        </h2>
                        {steps?.[2]?.edit ? (
                          <p className="desc">
                            Add details and select Gateway of your payment provider.
                          </p>
                        ) : null}
                      </div>
                      <div className="panel-body">
                        <Step2
                          steps={steps}
                          provider={provider}
                          changeProviderDetails={this.changeProviderDetails}
                          isProviderNameValid={isProviderNameValid}
                        />
                      </div>
                      {steps?.[2]?.edit ? this.nextButton(2) : null}
                    </div>
                  </CSSTransition>
                ) : null}

                {steps?.[3]?.show || isEdit ? (
                  <CSSTransition in appear timeout={800} classNames="slide-up">
                    <div className={`panel gateway-list${steps[3].edit ? ' active' : ''}`}>
                      <div className="panel-header">
                        <h2 className="payment-gateway-title">
                          {`${
                            providers?.[selectedProviderWithAcquirer]?.['Gateway Name']
                              ?.data_value || ''
                          } Production API Details`}
                          {selectedProvider && !steps?.[3]?.edit ? (
                            <button
                              onClick={this.updateStep(3, {
                                edit: !steps[3].edit,
                              })}
                              className=" pull-right no-border create-rule-act"
                            >
                              <i className="i i-pencil-edit" /> Edit Provider Details
                            </button>
                          ) : (
                            <span className="pull-right step-text">Step 3 Out Of 3</span>
                          )}
                        </h2>
                        {steps?.[3]?.edit ? (
                          <div className="desc">
                            <p className="gateway-details-desc">
                              Please make sure you <span>enter the production API details</span>{' '}
                              only and <span>NOT the Test Details</span>
                            </p>

                            <p
                              className="gateway-details-desc--how-to"
                              onClick={this.howtoGetDetails}
                            >
                              <i className="i i-help" />
                              Where do I find {selectedProviderWithAcquirer} details?
                            </p>
                          </div>
                        ) : null}
                      </div>

                      <div className="panel-body">
                        <Step3
                          isEdit={steps?.[3]?.edit}
                          selectedProvider={selectedProviderWithAcquirer}
                          providers={providers}
                          provider={provider}
                          validationErrors={validationErrors}
                          changeGatewayDetails={this.changeGatewayDetails}
                          changeGatewayWallets={this.changeGatewayWallets}
                          changeEnableAutoDebitSwitch={this.changeEnableAutoDebitSwitch}
                          user={user}
                          toggleMethods={this.toggleMethods}
                        />
                      </div>

                      {steps?.[3]?.edit && (
                        <div className="panel-footer">
                          {isSaving ? (
                            <div className="pull-right">
                              <Spinner />
                            </div>
                          ) : (
                            <button
                              onClick={this.onSubmit}
                              disabled={!(this.checkAllValuesExist() && allDetailsValid)}
                              className="btn btn-primary pull-right"
                              type="button"
                            >
                              Submit
                            </button>
                          )}
                          <div className="clearfix" />
                        </div>
                      )}
                    </div>
                  </CSSTransition>
                ) : null}
              </>
            )}
          </div>
        </div>
      </FullPageCover>
    );
  }
}
