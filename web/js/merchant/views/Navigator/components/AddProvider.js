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

@withRouter
@connect((state) => {
  return {
    user: state.session.user,
    activeProviders: state.navigator.terminalProviders,
  }
}, { openModal, closeModal, showNotification })
export default class AddProvider extends React.Component {
  constructor() {
    super();
    this.state = {
      redirect: null,
      provider: {
        'Provider_name': '',
        Description: '',
        Gateway: '',
        'Gateway_details': {
          'Payment Methods': [],
        },
      },
      loading: false,
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
    let params = {
      url: 'terminals/proxy/optimizer/supported_gateways',
      method: 'get',
    };

    this.setState({ loadingProviders: true });
    merchantFetch(params).then((res) => {
      if(res.success) {
        this.setState({ providers: res.data });
      }
      this.setState({ loadingProviders: false });
      this.filterProvidersOnSearch('');
    }).catch(() => {
      this.setState({ loadingProviders: false });
    });

    if (this.props.match.params.id) {
      this.setState({ loading: true });
      setTimeout(() => {
        const provider = this.props.activeProviders.filter((item) => item.Terminal_id === this.props.match.params.id)[0];
        this.setState({
          loading: false,
          isEdit: true,
          provider: provider,
          selectedProvider: provider.Gateway,
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
    const steps = this.state.steps;
    Object.keys(steps).forEach((k) => {
      steps[k].edit = false;
    });
    steps[index] = { ...steps[index], ...data };
    this.setState({ steps });
  };

  goNext = (i) => {
    let steps = { ...this.state.steps };
    steps[i] = {
      edit: false,
      show: true,
    };
    steps[i + 1] = {
      show: true,
      edit: true,
    };
    this.setState({ steps });
  };

  selectProvider = (provider) => {
    const provider_st = this.state.provider;
    provider_st.Gateway = provider;
    provider_st.Provider_name = provider;

    const { activeProviders } = this.props;
    let num = 1;
    activeProviders.forEach((item) => {
      if(provider_st.Provider_name === item.Provider_name) {
        provider_st.Provider_name = provider + "_" + num;
        num++;
      }
    });
    this.setState({ selectedProvider: provider, provider: provider_st });
  };

  viewProvider = (provider, index) => {
    const { providers } = this.state;
    return (
      <div className="col-xs-4 gateway-provider-col" key={index}>
        <div className="gateway-provider-block" onClick={() => this.selectProvider(provider)}>
          <div class="provider-img-holder">
            <img src={gatewayLogos[provider.toLowerCase()]} />
          </div>
          <div className="gateway-provider-block--details">
            <h3>{providers[provider] && providers[provider]['Gateway Name']['data_value']}</h3>
            <div className="gateway-provider-block--details--methods">
              <p
                title={providers[provider] && providers[provider]['Payment Methods']['data_value'].join(
                  ', ',
                )}
              >
                {providers[provider] && providers[provider]['Payment Methods']['data_value'].join(', ')}
              </p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  listProviders = (providers) => {
    const { isGatewaySearch, loadingProviders } = this.state;

    return (
      <>
        {loadingProviders ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <>
            {!isGatewaySearch && (
              <>
                <div className="col-xs-12 popular-gateways-header">
                  <img src="https://cdn.razorpay.com/static/assets/merchant-dash/popular_provider.svg" />
                  Popular Gateways
                </div>
                {popularGateways.map((provider, index) => (
                  this.viewProvider(provider, index)
                ))}
                <div className="col-xs-12 all-gateways-header">All Gateways</div>
              </>
            )}
            {Object.keys(providers).map((provider, index) => (
              this.viewProvider(provider, index)
            ))}
          </>
        )}
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
  }

  changeGateway = () => {
    this.setState({ selectedProvider: null });
    this.filterProvidersOnSearch('');
  }

  changeProviderDetails = (name, val) => {
    const provider = {...this.state.provider};
    provider[name] = val;

    if(name === "Provider_name") {
      let isProviderNameValid = true;
      const { activeProviders } = this.props;
      activeProviders.forEach((item) => {
        if(val === item.Provider_name && item.Terminal_id != provider.Terminal_id) {
          isProviderNameValid = false;
        }
      });
      this.setState({ isProviderNameValid });
    }

    this.setState({
      provider: provider,
    });
  }

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
      if (!provider['Gateway_details'][key] && key != 'Payment Methods' && key != 'Gateway Name') {
        isValid = false;
      } else if (provider['Gateway_details']['Payment Methods'].length === 0) {
        isValid = false;
      }
    });
    return isValid;
  };

  changeGatewayDetails = (event, item) => {
    const { provider } = this.state;
    if (event.target.type === 'checkbox') {
      const val = event.target.checked;
      if (val) {
        provider['Gateway_details']['Payment Methods'] = [
          ...provider['Gateway_details']['Payment Methods'],
          item,
        ];
      } else if (provider['Gateway_details']['Payment Methods']) {
        const index = provider['Gateway_details']['Payment Methods'].indexOf(item);
        provider['Gateway_details']['Payment Methods'] = [
          ...provider['Gateway_details']['Payment Methods'].slice(0, index),
          ...provider['Gateway_details']['Payment Methods'].slice(index + 1),
        ];
      }
    } else {
      const val = event.target.value;
      const { provider } = this.state;
      provider['Gateway_details'][item] = val;
      this.validateGatewayDetails(item);
    }
    this.setState({ provider: provider });
  };

  validateGatewayDetails = (key) => {
    const { provider, providers, selectedProvider, validationErrors, allDetailsValid } = this.state;
    const minLength = providers[selectedProvider][key]['min_length'];
    const maxLength = providers[selectedProvider][key]['max_length'];
    const checkVal = provider['Gateway_details'][key];

    const validErr = {...validationErrors}

    if ((minLength && checkVal && !(checkVal.length >= minLength)) && (!maxLength || !(checkVal.length > maxLength))) {
      if (maxLength != 0) {
        validErr[key] = `Please enter ${minLength} to ${maxLength} characters value`;
      }
      validErr[key] = `Please enter minimum ${minLength} characters value`;
      this.setState({ validationErrors: validErr });
    } else if (validErr[key]) {
      delete validErr[key];
      this.setState({ validationErrors: validErr });
    }

    if(Object.keys(validErr).length === 0) {
      this.setState({ allDetailsValid: true });
    } else if(allDetailsValid) {
      this.setState({ allDetailsValid: false });
    }
  };

  onSubmit = () => {
    const { provider, isEdit } = this.state;

    const payload = {
      ...provider,
    }

    this.setState({ isSaving: true });
    if(isEdit) {
      delete payload.Currency;
      delete payload.Gateway_acquirer;
      // PUT API
      merchantFetch({
        url: 'terminals/proxy/optimizer/mid/provider',
        method: 'put',
        data: payload,
      }).then(response => {
        if(response.success) {
          this.props.showNotification({
            type: 'success',
            message: `${payload.Provider_name} provider updated successfully.`,
            closeTimeout: 5000,
          });
          this.props.closeModal();
          this.setState({ redirect: '/optimizer/rules', isSaving: false });
        }
      }).catch(error => {
        this.props.showNotification({
          type: 'error',
          message: error.errors[0],
        });
        this.setState({ isSaving: false });
      });
    } else {
      // POST API
      merchantFetch({
        url: 'terminals/proxy/optimizer/mid/provider',
        method: 'post',
        data: payload,
      }).then(response => {
        if(response.success) {
          this.props.showNotification({
            type: 'success',
            message: `${payload.Provider_name} provider added successfully.`,
            closeTimeout: 5000,
          });
          this.props.closeModal();
          this.setState({ redirect: '/optimizer/rules', isSaving: false });
        }
      }).catch(error => {
        this.props.showNotification({
          type: 'error',
          message: error.errors[0],
        });
        this.setState({ isSaving: false });
      });
    }
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
    } = this.state;
    if (redirect) {
      return <Redirect to={redirect} />; // nosemgrep : https://semgrep.dev/s/razorpay:rzp-react-router-redirect
    }

    return (
      <FullPageCover>
        <FullPageCoverHeader>
          <div className="container add-provider-container">
            <div className="panel">
              <div className="panel-body">
                <div className="row">
                  <div className="col-xs-4">
                    <h3 className="add-provider-title">
                      {isEdit ? 'Edit' : 'Add'} Provider{' '}
                      {/* {!isEdit ? (
                        <a class={`highlight know-more`} target="_blank">
                          Know more
                          <i class="i i-external-link" />
                        </a>
                      ) : null} */}
                    </h3>
                  </div>
                  <div className="col-xs-5" />
                  <div className="col-xs-3">
                    <Link to={'/optimizer/rules'}>
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
          <div class="navigator--create-rule">
            {this.state.loading || this.state.loadingProviders ? (
              <div class="page-spinner-container">
                <Spinner />
              </div>
            ) : (
              <>
                {this.state.steps[1].show && (
                  <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                    <div
                      class={`panel gateway-list rule-detail ${
                        this.state.steps[1].edit ? 'active' : ''
                      }`}
                    >
                      <div class="panel-header">
                        <h2 class="payment-gateway-title">
                          Select Gateway
                          {!this.state.steps[1].edit && !isEdit ? (
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
                              <i className="i i-pencil-edit" /> Change Gateway
                            </button>
                          ) : (
                            <span class="pull-right step-text">
                              {' '}
                              Step 1 Out Of 3
                            </span>
                          )}
                        </h2>
                        {this.state.steps[1].edit ? (
                          <p class="desc">
                            Select a Gateway for your payment provider.
                          </p>
                        ) : null}
                      </div>
                      <div class="panel-body">
                        <Step1
                          steps={this.state.steps}
                          providers={providers}
                          selectedProvider={selectedProvider}
                          filterProvidersOnSearch={this.filterProvidersOnSearch}
                          filteredProviders={filteredProviders}
                          listProviders={this.listProviders}
                          changeGateway={this.changeGateway}
                          isEdit={isEdit}
                        />
                      </div>
                      {this.state.steps[1].edit ? (
                        <div className="panel-footer">
                          {this.nextButton(1, !selectedProvider)}
                        </div>
                      ) : null}
                    </div>
                  </CSSTransition>
                )}
                {this.state.steps[2].show || isEdit ? (
                  <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                    <div
                      class={`panel gateway-list rule-detail ${
                        this.state.steps[2].edit ? 'active' : ''
                      }`}
                    >
                      <div class="panel-header">
                        <h2 class="payment-gateway-title">
                          Provider Details
                          {!this.state.steps[2].edit ? (
                            <button
                              onClick={() => {
                                const edit = this.state.steps[2].edit;
                                this.updateStep(2, {
                                  edit: !edit,
                                });
                              }}
                              className="pull-right no-border create-rule-act"
                            >
                              {' '}
                              <i className="i i-pencil-edit" /> Edit Provider Details
                            </button>
                          ) : (
                            <span class="pull-right step-text">
                              {' '}
                              Step 2 Out Of 3
                            </span>
                          )}
                        </h2>
                        {this.state.steps[2].edit ? (
                          <p class="desc">
                            Add details and select Gateway of your payment provider.
                          </p>
                        ) : null}
                      </div>
                      <div class="panel-body">
                        <Step2
                          steps={this.state.steps}
                          provider={this.state.provider}
                          changeProviderDetails={this.changeProviderDetails}
                          isProviderNameValid={isProviderNameValid}
                        />
                      </div>
                      {this.state.steps[2].edit ? (
                        <div className="panel-footer">
                          {this.nextButton(2, !this.state.provider['Provider_name'].trim() || !isProviderNameValid || !this.state.provider.Description.trim())}
                        </div>
                      ) : null}
                    </div>
                  </CSSTransition>
                ) : null}

                {this.state.steps[3].show || isEdit ? (
                  <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                    <div class={`panel gateway-list ${this.state.steps[3].edit ? 'active' : ''}`}>
                      <div class="panel-header">
                        <h2 class="payment-gateway-title">
                          {providers[selectedProvider]['Gateway Name']['data_value']} Production API Details
                          {!this.state.steps[3].edit ? (
                            <button
                              onClick={() => {
                                const edit = this.state.steps[3].edit;
                                this.updateStep(3, {
                                  edit: !edit,
                                });
                              }}
                              className=" pull-right no-border create-rule-act"
                            >
                              {' '}
                              <i className="i i-pencil-edit" /> Edit Provider Details
                            </button>
                          ) : (
                            <span class="pull-right step-text">
                              {' '}
                              Step 3 Out Of 3
                            </span>
                          )}
                        </h2>
                        {this.state.steps[3].edit ? (
                          <p class="desc gateway-details-desc">
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

                      <div class="panel-body">
                        <Step3
                          steps={this.state.steps}
                          selectedProvider={selectedProvider}
                          providers={providers}
                          provider={this.state.provider}
                          validationErrors={validationErrors}
                          changeGatewayDetails={this.changeGatewayDetails}
                        />
                      </div>

                      {this.state.steps[3].edit && (
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
