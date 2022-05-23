import React from 'react';
import { connect } from 'react-redux';
import { withRouter, Link, Redirect } from 'react-router-dom';
import Spinner from 'common/ui/Spinner';
import { CSSTransition } from 'react-transition-group';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import FullPageCover from './FullPageCover';
import FullPageCoverHeader from './FullPageCoverHeader';
import { popularGateways, gatewayLogos } from './util';
import { merchantFetch } from 'merchant/utils/ajax';
import { showNotification } from 'merchant_common/reducers/notifications';
import { HowToGetDetails } from './Provider/HowToGetDetails';
import { Step1, Step2, Step3 } from './AddProvider/index';
import { trackOptimizerEvents, trackAPIResutls } from 'merchant/views/Navigator/track';

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
      provider: {
        Provider_name: '',
        Description: '',
        Gateway: '',
        Gateway_details: {
          'Payment Methods': [],
        },
      },
      loading: !!props?.match?.params?.id,
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

    const { match, activeProviders } = this.props;

    if (match?.params?.id) {
      setTimeout(() => {
        const provider =
          activeProviders?.filter((item) => item?.Terminal_id === match.params.id)[0] || {};
        this.setState({
          loading: false,
          isEdit: true,
          provider,
          selectedProvider: provider?.Gateway,
          steps: {
            1: {
              edit: false,
              show: true,
            },
            2: {
              edit: true,
              show: true,
            },
            3: {
              edit: false,
              show: true,
            },
          },
        });
      }, 0);
    }
  }

  filterProvidersOnSearch = (val) => {
    const { providers } = this.state;
    if (val === '') {
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
        {!isGatewaySearch && (
          <>
            <div className="col-xs-12 popular-gateways-header">
              <img
                alt="popular"
                src="https://cdn.razorpay.com/static/assets/merchant-dash/popular_provider.svg"
              />
              Popular Gateways
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
    this.setState({ selectedProvider: null });
    this.filterProvidersOnSearch('');
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

  howtoGetDetails = () => {
    const { providers, selectedProvider } = this.state;
    this.props.openModal({
      size: 'large',
      component: (
        <HowToGetDetails
          providers={providers}
          selectedProvider={selectedProvider}
          closeModal={this.props.closeModal}
        />
      ),
    });
  };

  checkAllValuesExist = () => {
    const { provider, providers } = this.state;
    let isValid = true;
    Object.keys(providers[provider.Gateway]).forEach((key) => {
      if (
        !provider?.Gateway_details?.[key] &&
        key !== 'Payment Methods' &&
        key !== 'Gateway Name'
      ) {
        isValid = false;
      } else if (provider?.Gateway_details?.['Payment Methods']?.length === 0) {
        isValid = false;
      }
    });
    return isValid;
  };

  changeGatewayDetails = (event, item) => {
    const { type, checked, value } = event.target;
    this.setState((prevState) => {
      const { provider } = prevState;
      if (type === 'checkbox') {
        const val = checked;
        if (val) {
          provider.Gateway_details['Payment Methods'] = [
            ...provider?.Gateway_details?.['Payment Methods'],
            item,
          ];
        } else if (provider?.Gateway_details?.['Payment Methods']) {
          const index = provider.Gateway_details['Payment Methods'].indexOf(item);
          provider.Gateway_details['Payment Methods'] = [
            ...provider.Gateway_details['Payment Methods'].slice(0, index),
            ...provider.Gateway_details['Payment Methods'].slice(index + 1),
          ];
        }
      } else {
        const val = value;
        provider.Gateway_details[item] = val;
        this.validateGatewayDetails(item);
      }
      return { provider };
    });
  };

  validateGatewayDetails = (key) => {
    const { provider, providers, selectedProvider, validationErrors, allDetailsValid } = this.state;
    const minLength = providers?.[selectedProvider]?.[key]?.min_length;
    const maxLength = providers?.[selectedProvider]?.[key]?.max_length;
    const checkVal = provider?.Gateway_details?.[key];

    const validErr = { ...validationErrors };

    if (
      minLength &&
      checkVal &&
      !(checkVal.length >= minLength) &&
      (!maxLength || !(checkVal.length > maxLength))
    ) {
      if (maxLength != 0) {
        validErr[key] = `Please enter ${minLength} to ${maxLength} characters value`;
      }
      validErr[key] = `Please enter minimum ${minLength} characters value`;
      this.setState({ validationErrors: validErr });
    } else if (validErr[key]) {
      delete validErr[key];
      this.setState({ validationErrors: validErr });
    }

    if (Object.keys(validErr).length === 0) {
      this.setState({ allDetailsValid: true });
    } else if (allDetailsValid) {
      this.setState({ allDetailsValid: false });
    }
  };

  onSubmit = () => {
    const { provider, isEdit } = this.state;
    const { showNotification, closeModal } = this.props;

    const payload = {
      ...provider,
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
    if (isEdit) {
      delete payload.Currency;
      delete payload.Gateway_acquirer;
      // PUT API
      merchantFetch({
        url: 'terminals/proxy/optimizer/mid/provider',
        method: 'put',
        data: payload,
      })
        .then((response) => {
          if (response?.success) {
            showNotification({
              type: 'success',
              message: `${payload?.Provider_name} provider updated successfully.`,
              closeTimeout: 5000,
            });
            closeModal();
            trackAPIResutls({
              name: 'Edit Provider',
              properties: {
                success: true,
              },
            });
            this.setState({ redirect: '/optimizer/rules', isSaving: false });
          }
        })
        .catch((error) => {
          showNotification({
            type: 'error',
            message: error?.errors?.[0],
          });
          trackAPIResutls({
            name: 'Edit Provider',
            properties: {
              success: false,
              failureReason: error?.errors?.[0],
            },
          });
          this.setState({ isSaving: false });
        });
    } else {
      // POST API
      merchantFetch({
        url: 'terminals/proxy/optimizer/mid/provider',
        method: 'post',
        data: payload,
      })
        .then((response) => {
          if (response?.success) {
            showNotification({
              type: 'success',
              message: `${payload?.Provider_name} provider added successfully.`,
              closeTimeout: 5000,
            });
            closeModal();
            trackAPIResutls({
              name: 'Add Provider',
              properties: {
                success: true,
              },
            });
            this.setState({ redirect: '/optimizer/rules', isSaving: false });
          }
        })
        .catch((error) => {
          showNotification({
            type: 'error',
            message: error?.errors?.[0],
          });
          trackAPIResutls({
            name: 'Add Provider',
            properties: {
              success: false,
              failureReason: error?.errors?.[0],
            },
          });
          this.setState({ isSaving: false });
        });
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
      selectedProvider,
      validationErrors,
      isEdit,
      isSaving,
      isProviderNameValid,
      allDetailsValid,
      loading,
      loadingProviders,
      steps,
      provider,
    } = this.state;
    if (redirect) {
      return <Redirect to={redirect} />;
    }

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
            {loading || loadingProviders ? (
              <div className="page-spinner-container">
                <Spinner />
              </div>
            ) : (
              <>
                {steps?.[1]?.show && (
                  <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
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
                          selectedProvider={selectedProvider}
                          filterProvidersOnSearch={this.filterProvidersOnSearch}
                          filteredProviders={filteredProviders}
                          listProviders={this.listProviders}
                          changeGateway={this.changeGateway}
                          isEdit={isEdit}
                        />
                      </div>
                      {steps[1].edit ? (
                        <div className="panel-footer">{this.nextButton(1, !selectedProvider)}</div>
                      ) : null}
                    </div>
                  </CSSTransition>
                )}
                {steps?.[2]?.show || isEdit ? (
                  <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                    <div
                      className={`panel gateway-list rule-detail${steps[2].edit ? ' active' : ''}`}
                    >
                      <div className="panel-header">
                        <h2 className="payment-gateway-title">
                          Provider Details
                          {!steps?.[2]?.edit ? (
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
                  <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                    <div className={`panel gateway-list${steps[3].edit ? ' active' : ''}`}>
                      <div className="panel-header">
                        <h2 className="payment-gateway-title">
                          {providers?.[selectedProvider]?.['Gateway Name']?.data_value} Production
                          API Details
                          {!steps?.[3]?.edit ? (
                            <button
                              onClick={this.updateStep(3, {
                                edit: !steps[3].edit,
                              })}
                              className=" pull-right no-border create-rule-act"
                            >
                              {' '}
                              <i className="i i-pencil-edit" /> Edit Provider Details
                            </button>
                          ) : (
                            <span className="pull-right step-text">Step 3 Out Of 3</span>
                          )}
                        </h2>
                        {steps?.[3]?.edit ? (
                          <p className="desc gateway-details-desc">
                            Please make sure you <span>enter the production API details</span> only
                            and <span>NOT the Test Details</span>
                            <p
                              className="gateway-details-desc--how-to"
                              onClick={this.howtoGetDetails}
                            >
                              <i className="i i-help" />
                              Where do I find {selectedProvider} details?
                            </p>
                          </p>
                        ) : null}
                      </div>

                      <div className="panel-body">
                        <Step3
                          steps={steps}
                          selectedProvider={selectedProvider}
                          providers={providers}
                          provider={provider}
                          validationErrors={validationErrors}
                          changeGatewayDetails={this.changeGatewayDetails}
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
