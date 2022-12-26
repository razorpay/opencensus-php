import React from 'react';
import { connect } from 'react-redux';
import { withRouter, Link, Redirect } from 'react-router-dom';
import Spinner from 'common/ui/Spinner';
import { CSSTransition } from 'react-transition-group';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import FullPageCover from './FullPageCover';
import FullPageCoverHeader from './FullPageCoverHeader';
import {
  popularGateways,
  gatewayLogos,
  getSelectedProviderWithAcquirer as getSelectedProvider,
} from './util';
import { merchantFetch } from 'merchant/utils/ajax';
import { showNotification } from 'merchant_common/reducers/notifications';
import { HowToGetDetails } from './Provider/HowToGetDetails';
import { Step1, Step2, Step3 } from './AddProvider/index';
import { trackOptimizerEvents, trackAPIResults } from 'merchant/views/Navigator/track';
import { addProvider, editProvider } from 'merchant/views/Navigator/service';
import { INIT_PROVIDER_STATE, INIT_FORM_STATE } from 'merchant/views/Navigator/constants';
import { deepClone } from 'common/utils/rzp-utils';

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
  constructor(props) {
    super(props);
    this.state = {
      redirect: null,
      provider: deepClone(INIT_PROVIDER_STATE),
      terminalId: props?.match?.params?.id,
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
      filteredProviders: [],
      selectedProvider: null,
      isGatewaySearch: false,
      validationErrors: {},
      isEdit: false,
      loadingProviders: true,
      isSaving: false,
      isProviderNameValid: true,
      allDetailsValid: false,
    };
  }

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
        this.filterProvidersOnSearch('');
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

      this.setState({
        ...deepClone(INIT_FORM_STATE),
        selectedProvider: provider?.Gateway || '',
        provider,
      });
    }
  };

  filterProvidersOnSearch = (val) => {
    const { providers } = this.state;
    if (val.trim() === '') {
      this.setState({ filteredProviders: providers, isGatewaySearch: false });
    } else {
      const keys = Object.keys(providers).filter((item) =>
        item.toLowerCase().startsWith(val.toLowerCase()),
      );
      const res = {};
      keys.forEach((item) => {
        res[item] = providers[item];
      });
      this.setState({ filteredProviders: res, isGatewaySearch: true });
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

      const { activeProviders } = this.props;
      let num = 1;
      activeProviders.forEach((item) => {
        if (provider_st.Provider_name === item.Provider_name) {
          provider_st.Provider_name = `${provider}_${num}`;
          num++;
        }
      });

      // Paytm by default enable wallets
      if (
        provider === 'paytm' &&
        prevState.providers?.paytm?.['Payment Methods']?.data_value?.indexOf('wallet') !== -1
      ) {
        provider_st.Gateway_details['Payment Methods'] = ['wallet'];
        provider_st.Gateway_details.wallet_metadata = {
          wallets:
            prevState.providers?.[provider]?.['Payment Methods']?.meta_data?.wallet_metadata
              ?.wallets || [],
        };
      }

      return { selectedProvider: provider, provider: provider_st };
    });
  };

  viewProvider = (provider, index) => {
    const { providers } = this.state;
    const SELECTED_PROVIDER = providers?.[provider] || {};
    const PAYMENT_METHODS = SELECTED_PROVIDER?.['Payment Methods']?.data_value?.join(', ') || '';
    return (
      <div className="col-xs-4 gateway-provider-col" key={index}>
        <div className="gateway-provider-block" onClick={() => this.selectProvider(provider)}>
          <div className="provider-img-holder">
            <img alt={provider} src={gatewayLogos[provider?.toLowerCase()]} />
          </div>
          <div className="gateway-provider-block--details">
            <h3>{SELECTED_PROVIDER?.['Gateway Name']?.data_value}</h3>
            <div className="gateway-provider-block--details--methods">
              <p title={PAYMENT_METHODS}>{PAYMENT_METHODS}</p>
            </div>
          </div>
        </div>
      </div>
    );
  };

  listProviders = (providers) => {
    const { isGatewaySearch, loadingProviders } = this.state;

    if (loadingProviders) {
      return (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      );
    }
    return (
      <>
        {!isGatewaySearch && Object.keys(providers).length > 0 && (
          <>
            <div className="col-xs-12 popular-gateways-header">
              <img
                alt="popular"
                src="https://cdn.razorpay.com/static/assets/merchant-dash/popular_provider.svg"
              />
              <span>Popular Gateways</span>
            </div>
            {popularGateways.map((provider, index) => this.viewProvider(provider, index))}
            <div className="col-xs-12 all-gateways-header">All Gateways</div>
          </>
        )}
        {Object.keys(providers).map((provider, index) => this.viewProvider(provider, index))}
      </>
    );
  };

  nextButton = (step, isDisabled) => {
    return (
      <>
        <button
          disabled={isDisabled}
          onClick={() => {
            this.goNext(step);
          }}
          className="btn btn-primary pull-right"
        >
          Next <i className="i i-arrow-forward" />
        </button>
        <div className="clearfix" />
      </>
    );
  };

  changeGateway = () => {
    const { providers } = this.state;

    this.setState({
      selectedProvider: null,
      provider: deepClone(INIT_PROVIDER_STATE),
      filteredProviders: providers,
      isGatewaySearch: false,
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

    let isValid = true;

    Object.keys(providers[selectedProviderWithAcquirer]).forEach((key) => {
      const value = provider?.Gateway_details?.[key];

      if (!['Payment Methods', 'Gateway Name', 'TPV'].includes(key) && !value) {
        isValid = false;
      } else if (key === 'Payment Methods' && value?.length === 0) {
        isValid = false;
      }
    });

    if (
      provider?.Gateway_details?.['Payment Methods'].indexOf('wallet') !== -1 &&
      provider?.Gateway_details?.wallet_metadata?.wallets?.length <= 0
    ) {
      isValid = false;
    }

    return isValid;
  };

  changeGatewayDetails = (event, item) => {
    const { type, checked, value, name } = event.target;

    this.setState(
      (prevState) => {
        const { provider, providers } = prevState;
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
            const index = provider.Gateway_details['Payment Methods'].indexOf(item);
            provider.Gateway_details['Payment Methods'] = [
              ...provider.Gateway_details['Payment Methods'].slice(0, index),
              ...provider.Gateway_details['Payment Methods'].slice(index + 1),
            ];
            if (item === 'wallet') {
              delete provider.Gateway_details.wallet_metadata;
            }
          }
        } else if (type === 'radio' && ['tpv'].includes(name)) {
          const upiFeatures = provider.Gateway_details['UPI Features'] || {};

          provider.Gateway_details['UPI Features'] = { ...upiFeatures, [name]: Number(value) };
        } else {
          provider.Gateway_details[item] = value;
        }

        return { provider };
      },
      () => this.validateGatewayDetails(item),
    );
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

  validateGatewayDetails = (key) => {
    const { provider, providers, validationErrors } = this.state;
    const selectedProviderWithAcquirer = this.getSelectedProviderWithAcquirer();
    const { min_length: minLength, max_length: maxLength, meta_data } =
      providers?.[selectedProviderWithAcquirer]?.[key] || {};
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
    const { provider, isEdit } = this.state;
    const { closeModal, history, showNotification } = this.props;

    let res = null;

    const payload = {
      ...provider,
      Gateway: this.getSelectedProviderWithAcquirer(),
    };

    trackOptimizerEvents({
      objectName: `${isEdit ? 'update' : 'add'} provider submit`,
      actionName: 'click',
      properties: {
        gateway: payload?.Gateway,
        payment_methods: payload?.Gateway_details?.['Payment Methods'],
      },
      screen: `Optimizer ${isEdit ? 'Edit' : 'Add'} Provider`,
    });

    this.setState({ isSaving: true });

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
    const { isEdit } = this.state;
    trackOptimizerEvents({
      objectName: `${isEdit ? 'Edit' : 'Add'} Provider`,
      actionName: 'close',
      screen: `Optimizer ${isEdit ? 'Edit' : 'Add'} Provider`,
    });
  };

  render() {
    const {
      redirect,
      providers,
      filteredProviders,
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

    if (redirect) {
      return <Redirect to={redirect} />;
    }
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
                    <Link to="/optimizer/rules" onClick={this.trackEventOnClose}>
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
                          {!steps[1].edit && !isEdit ? (
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
                          selectedProvider={selectedProviderWithAcquirer}
                          filterProvidersOnSearch={this.filterProvidersOnSearch}
                          filteredProviders={filteredProviders}
                          listProviders={this.listProviders}
                          changeGateway={this.changeGateway}
                          isEdit={isEdit}
                        />
                      </div>
                      {steps[1].edit ? (
                        <div className="panel-footer">
                          {this.nextButton(1, !selectedProviderWithAcquirer)}
                        </div>
                      ) : null}
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
                      {steps?.[2]?.edit ? (
                        <div className="panel-footer">
                          {this.nextButton(
                            2,
                            !provider?.Provider_name?.trim() ||
                              !isProviderNameValid ||
                              !provider?.Description?.trim(),
                          )}
                        </div>
                      ) : null}
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
