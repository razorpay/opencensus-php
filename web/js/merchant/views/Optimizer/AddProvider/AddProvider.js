import React, { createRef } from 'react';
import { Box, Link, ChevronLeftIcon, Spinner } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { withSplitzService } from 'common/splitz';
import { deepClone } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';
import { categorizeGateways } from 'merchant/views/Navigator/components/AddProvider/util';
import { HowToGetDetails } from 'merchant/views/Navigator/components/Provider/HowToGetDetails';
import { getSelectedProviderWithAcquirer as getSelectedProvider } from 'merchant/views/Navigator/components/util';
import {
  METHODS,
  INIT_PROVIDER_STATE,
  HAS_NETBANKING_FEATURES,
  HAS_UPI_FEATURES,
  NETBANKING_FEATURES,
  UPI_FEATURES,
  INSTANT_PROVIDER_UNSUPPORTED_METHODS,
  SKIP_VALIDATION_KEYS,
  SKIP_PAYTM_AUTO_DEBIT_VALIDATION_KEYS,
  WALLET_AUTO_DEBIT_KEY,
  PROVIDER_KEYS,
  SEAMLESS_PROVIDERS,
  RAZORPAY_GATEWAY_KEY,
} from 'merchant/views/Navigator/constants';
import { addProvider, editProvider } from 'merchant/views/Navigator/service';
import { trackOptimizerEvents, trackAPIResults } from 'merchant/views/Navigator/track';
import {
  addProviderV3,
  fetchRazorpayMethodCoverage,
  updateProvider,
} from 'merchant/views/Optimizer/AddProvider/service';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import {
  SelectGateway,
  IntegrationType,
  ProviderDetails,
  ProviderConfiguration,
  PaymentMethodCoverage,
  TestingConfirmation,
  IntegrationTesting,
} from './components';
import {
  isIntegrationAuditEnabled,
  isGatewaySupportIntegrationAudit,
  areMandatoryMethodsCovered,
} from './utils';

class AddProvider extends React.Component {
  step2Ref = createRef();
  step3Ref = createRef();
  step4Ref = createRef();

  state = {
    isEdit: false, // to indicate whether the form is in Edit mode (true) or Add mode (false).
    currentStep: 1,
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
      5: {
        edit: false,
        show: false,
      },
    },
    provider: deepClone(INIT_PROVIDER_STATE),
    terminalId: this.props?.match?.params?.id,
    providers: {},
    selectedProvider: null,
    loadingProviders: true,
    isProviderNameValid: true,
    categorizedProviders: {},
    hasSeamlessOption: false,
    allDetailsValid: false,
    validationErrors: {},
    isSaving: false,
    showMethodCoverage: false,
    gatewayCoverage: [],
    razorpayCoverage: [],
    isGatewayCoverageMissing: false,
    isRazorpayCoverageMissing: false,
    isTestingConfirmationModalOpen: false,
    isStartIntegrationTesting: false,
    providerId: null,
    updateProviderData: {},
  };

  componentDidMount() {
    const { splitz } = this.props;
    const { abExperiments } = splitz || { abExperiments: undefined };
    const params = {
      url: 'terminals/proxy/optimizer/supported_gateways',
      method: 'get',
    };
    merchantFetch(params)
      .then((res) => {
        if (res?.success) {
          const categorizedProviders = categorizeGateways(res?.data, abExperiments);
          this.setState({ providers: res?.data, categorizedProviders });
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
    const { activeProviders, splitz } = this.props;
    const { terminalId } = this.state;

    if (terminalId && activeProviders?.length > 0) {
      const provider = activeProviders.find((item) => item?.Terminal_id === terminalId) || {};

      if (provider) {
        const { Gateway, Gateway_details } = provider;
        const updateBlocked =
          isIntegrationAuditEnabled(splitz) && isGatewaySupportIntegrationAudit(Gateway);
        // check if gateway has seamless option enabled
        const hasSeamlessOption =
          SEAMLESS_PROVIDERS?.includes(Gateway) &&
          Gateway_details?.hasOwnProperty(PROVIDER_KEYS.SEAMLESS_KEY);
        const hasAccountTypeOption =
          Gateway === RAZORPAY_GATEWAY_KEY &&
          Gateway_details?.hasOwnProperty(PROVIDER_KEYS.GATEWAY_ACQUIRER);

        const paymentMethods = Gateway_details?.['Payment Methods'] ?? [];
        const hasNBMethod = paymentMethods.includes('netbanking');
        const hasUPIMethod = paymentMethods.includes('upi');

        if (hasUPIMethod && HAS_UPI_FEATURES.includes(Gateway)) {
          provider.Gateway_details.TPV = Gateway_details[UPI_FEATURES]?.tpv ?? 0;
        } else if (hasNBMethod && HAS_NETBANKING_FEATURES.includes(Gateway)) {
          provider.Gateway_details.TPV = Gateway_details[NETBANKING_FEATURES]?.tpv ?? 0;
        } else {
          provider.Gateway_details.TPV = 0;
        }

        const { search, state } = this.props.location;
        const locationParams = new Proxy(new URLSearchParams(search), {
          get: (searchParams, prop) => searchParams.get(prop),
        });
        let showMethodCoverage = false;
        let gatewayCoverage = [];
        let razorpayCoverage = [];
        let newSteps = {
          1: {
            edit: false,
            show: true,
          },
          2: {
            edit: hasSeamlessOption && !updateBlocked,
            show: hasSeamlessOption || hasAccountTypeOption,
          },
          3: {
            edit: !hasSeamlessOption || updateBlocked,
            show: true,
          },
          4: {
            edit: false,
            show: true,
          },
        };
        if (locationParams.for === 'coverage') {
          showMethodCoverage = true;
          gatewayCoverage = state?.gatewayCoverage;
          razorpayCoverage = state?.razorpayCoverage;
          newSteps = {
            1: {
              edit: false,
              show: true,
            },
            2: {
              edit: false,
              show: hasSeamlessOption || hasAccountTypeOption,
            },
            3: {
              edit: false,
              show: true,
            },
            4: {
              edit: false,
              show: true,
            },
            5: {
              edit: false,
              show: true,
            },
          };
        }

        this.setState({
          isEdit: true,
          currentStep: hasSeamlessOption ? 2 : 3,
          steps: newSteps,
          selectedProvider: Gateway || '',
          provider,
          hasSeamlessOption,
          allDetailsValid: true,
          showMethodCoverage,
          gatewayCoverage,
          razorpayCoverage,
        });
      }
    }
  };

  // Case insensitive check
  isProviderNameUnique = (name, activeProviders) =>
    activeProviders.some(
      ({ Provider_name }) => Provider_name?.toLowerCase() === name?.toLowerCase(),
    );

  generateUniqueProviderName(selectedProvider, activeProviders) {
    let uniqueName = selectedProvider;
    let num = 1;

    // Check if the provider name already exists in the activeProviders array.
    while (this.isProviderNameUnique(uniqueName, activeProviders)) {
      // If a name conflict is found, append a unique number (e.g., _1, _2, ...) to the original name.
      uniqueName = `${selectedProvider}_${num}`;
      num++; // Increment the counter for the next unique name.
    }

    return uniqueName;
  }

  // function to check TPV support
  checkTPVSupport(selectedProvider, providers) {
    return (
      providers?.[selectedProvider]?.hasOwnProperty('TPV') &&
      [...HAS_NETBANKING_FEATURES, ...HAS_UPI_FEATURES].includes(selectedProvider)
    );
  }

  selectProvider = (selectedProvider) => {
    trackOptimizerEvents({
      objectName: 'gateway',
      actionName: 'select',
      properties: {
        gateway: selectedProvider,
      },
      screen: 'Optimizer Add Provider',
    });

    this.setState(
      (prevState) => {
        const { user, activeProviders } = this.props;
        const { providers, provider } = prevState;
        const newProviderObj = { ...provider };
        newProviderObj.Gateway = selectedProvider;
        const providerName =
          selectedProvider === RAZORPAY_GATEWAY_KEY ? 'razorpay' : selectedProvider;
        // This logic ensures that Provider_name is unique among activeProviders.
        newProviderObj.Provider_name = this.generateUniqueProviderName(
          providerName,
          activeProviders,
        );

        // check if provider gateway supports TPV
        const isTPVSupported = this.checkTPVSupport(selectedProvider, providers);
        if (isTPVSupported) {
          newProviderObj.Gateway_details.TPV = 0;
        }

        // specific checks for paytm provider
        if (selectedProvider === 'paytm') {
          if (user?.isPaytmAutoDebitEnabled) {
            newProviderObj.Gateway_details.ENABLE_AUTO_DEBIT = false;
          }
          if (providers?.paytm?.['Payment Methods']?.data_value?.indexOf('wallet') !== -1) {
            // enable wallets method by default for paytm
            newProviderObj.Gateway_details['Payment Methods'] = ['wallet'];
            newProviderObj.Gateway_details.wallet_metadata = {
              wallets:
                providers?.[selectedProvider]?.['Payment Methods']?.meta_data?.wallet_metadata
                  ?.wallets || [],
            };
          }
        }

        return { selectedProvider, provider: newProviderObj };
      },
      () => this.goNext(),
    );
  };

  changeGateway = () => {
    this.setState((prevState) => {
      const updatedSteps = Object.keys(prevState.steps).reduce((steps, key) => {
        steps[key] =
          key === '1'
            ? { edit: true, show: true }
            : { ...prevState.steps[key], edit: false, show: false };
        return steps;
      }, {});

      return {
        currentStep: 1,
        steps: updatedSteps,
        selectedProvider: null,
        provider: deepClone(INIT_PROVIDER_STATE),
      };
    });
  };

  onEditClick = (currentStep) => {
    this.setState((prevState) => {
      const newSteps = { ...prevState.steps };

      // Set edit to true for the clicked step, and false for all other steps
      Object.keys(newSteps).forEach(
        (stepKey) => (newSteps[stepKey].edit = stepKey === currentStep.toString()),
      );

      return { currentStep, steps: newSteps };
    });
  };

  goNext = () => {
    this.setState(
      (prevState) => {
        const { currentStep, steps, providers, selectedProvider } = prevState;
        let nextStep = currentStep + 1;
        // check if gateway has seamless option enabled
        const hasSeamlessOption =
          SEAMLESS_PROVIDERS?.includes(selectedProvider) &&
          providers?.[selectedProvider]?.hasOwnProperty(PROVIDER_KEYS.SEAMLESS_KEY);
        // check if account type required for gateway acquirer
        const hasAccountTypeOption =
          selectedProvider === RAZORPAY_GATEWAY_KEY &&
          providers?.[selectedProvider]?.hasOwnProperty(PROVIDER_KEYS.GATEWAY_ACQUIRER);

        if (nextStep === 2 && !(hasSeamlessOption || hasAccountTypeOption)) {
          nextStep += 1; // Skip step 2 if gateway doesn't support seamless integration or account type
        }

        steps[currentStep].edit = false;

        if (steps[nextStep]) {
          steps[nextStep].edit = true;
          steps[nextStep].show = true;
        }

        return { currentStep: nextStep, steps, hasSeamlessOption };
      },
      () => {
        const { currentStep } = this.state;
        const refs = { 2: this.step2Ref, 3: this.step3Ref, 4: this.step4Ref };

        if (currentStep >= 2 && currentStep <= 4) {
          const targetRef = refs[currentStep]; // Reference to the target element
          if (targetRef && targetRef?.current) {
            // Scroll to the calculated position with smooth behavior
            window.scrollTo({
              top: targetRef.current.offsetTop - 16, // Calculate the scroll position relative to the document
              behavior: 'smooth',
            });
          }
        }
      },
    );
  };

  disableStep = (step) => {
    const { providers, selectedProvider, provider, isProviderNameValid } = this.state;
    const selectedProviderWithAcquirer = this.getSelectedProviderWithAcquirer();

    // check if gateway has seamless option enabled
    const seamlessOptionExist =
      SEAMLESS_PROVIDERS?.includes(selectedProviderWithAcquirer) &&
      providers?.[selectedProvider]?.hasOwnProperty('optimizer_seamless_disabled');

    // check if radio button is toggled
    const seamlessRadioValue = provider?.Gateway_details?.hasOwnProperty(
      'optimizer_seamless_disabled',
    );

    // check if account type is selected for gateway acquirer
    const isAccountTypeNotSelected =
      selectedProviderWithAcquirer === RAZORPAY_GATEWAY_KEY &&
      !provider?.Gateway_details?.['Gateway Acquirer'];

    // step3 validation
    const isProviderNameEmpty = (provider?.Provider_name || '').trim();
    const isProviderDescriptionEmpty = (provider?.Description || '').trim();
    const isStep3Valid = !(
      isProviderNameValid &&
      isProviderNameEmpty &&
      isProviderDescriptionEmpty
    );

    let isDisabled = false;

    switch (step) {
      case 1:
        if (!selectedProviderWithAcquirer) {
          isDisabled = true;
        }
        break;
      case 2:
        if (seamlessOptionExist && !seamlessRadioValue) {
          isDisabled = true;
        }
        if (isAccountTypeNotSelected) {
          isDisabled = true;
        }
        break;
      case 3:
        if (isStep3Valid) {
          isDisabled = true;
        }
        break;
      default:
        break;
    }

    return isDisabled;
  };

  toggleIntegrationType = ({ value }) => {
    const { isEdit, selectedProvider } = this.state;

    trackOptimizerEvents({
      screen: `Optimizer ${isEdit ? 'Update' : 'Add'} Provider`,
      objectName: 'Seamless option',
      actionName: 'select',
      properties: {
        gateway: selectedProvider,
        'Integration Type': value ? 'Instant (beta)' : 'Server-to-Server',
      },
    });

    this.setState((prevState) => {
      const { Gateway_details, ...rest } = prevState.provider;

      return {
        provider: {
          ...rest,
          Gateway_details: {
            ...Gateway_details,
            optimizer_seamless_disabled: value,
            ...(value && {
              'Payment Methods': Gateway_details?.['Payment Methods'].filter(
                (method) =>
                  !INSTANT_PROVIDER_UNSUPPORTED_METHODS[selectedProvider]?.includes(method),
              ),
            }),
          },
        },
      };
    });
  };

  changeProviderDetails = ({ name, value }) => {
    this.setState((prevState) => {
      const provider = { ...prevState.provider };
      provider[name] = value;

      if (name === 'Provider_name') {
        let isProviderNameValid = true;
        const { activeProviders } = this.props;
        activeProviders?.forEach((item) => {
          // Case insensitive check
          if (
            value?.toLowerCase() === item?.Provider_name?.toLowerCase() &&
            (item?.Provider_name === 'razorpay' || item?.Terminal_id !== provider?.Terminal_id)
          ) {
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

  checkAllValuesExist = () => {
    const { provider, providers } = this.state;

    const selectedProviderWithAcquirer = this.getSelectedProviderWithAcquirer();
    const { user, splitz } = this.props;
    let paytmAutoDebitEnabled = false;
    if (user?.isPaytmAutoDebitEnabled && selectedProviderWithAcquirer === 'paytm') {
      paytmAutoDebitEnabled = provider?.Gateway_details?.[WALLET_AUTO_DEBIT_KEY];
    }

    // Do paymnet method value check only if gateway does not support integration audit
    const doPaymentMethodsValidation = !(
      isIntegrationAuditEnabled(splitz) &&
      isGatewaySupportIntegrationAudit(selectedProviderWithAcquirer)
    );

    if (!doPaymentMethodsValidation) {
      return true;
    }

    for (const key of Object.keys(providers[selectedProviderWithAcquirer])) {
      const value = provider?.Gateway_details?.[key];

      if (key === 'Payment Methods' && doPaymentMethodsValidation) {
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

  changeGatewayDetails = (event) => {
    const { name, value, isChecked } = event;

    this.setState(
      (prevState) => {
        const { provider, providers, selectedProvider } = prevState;
        const { Gateway_details } = provider;
        const providerItem = providers?.[selectedProvider];
        const isRecurringEnabled = providerItem?.hasOwnProperty(PROVIDER_KEYS.RECURRING);

        if (name === 'Payment Methods') {
          if (isChecked) {
            provider.Gateway_details['Payment Methods'] = [
              ...(provider?.Gateway_details?.['Payment Methods'] || []),
              value,
            ];
            if (value === 'wallet') {
              const selectedProviderWithAcquirer = this.getSelectedProviderWithAcquirer();
              const selectedProviderWallets =
                providers?.[selectedProviderWithAcquirer]?.['Payment Methods']?.meta_data
                  ?.wallet_metadata?.wallets;

              provider.Gateway_details.wallet_metadata = {
                wallets: [...(selectedProviderWallets || [])],
              };
            }
          } else {
            const paymentMethods = provider?.Gateway_details?.['Payment Methods'];

            if (paymentMethods) {
              // remove method if isChecked is false
              provider.Gateway_details['Payment Methods'] = paymentMethods.filter(
                (method) => method !== value,
              );

              const isSodexoEnabled =
                selectedProvider === 'payu' &&
                providerItem?.hasOwnProperty(PROVIDER_KEYS.SODEXO) &&
                !Gateway_details?.optimizer_seamless_disabled;

              if (value === 'card' && isSodexoEnabled) {
                provider.Gateway_details[PROVIDER_KEYS.SODEXO] = false;
              }

              if (value === 'wallet') {
                delete provider.Gateway_details.wallet_metadata;
              }

              const noCardOrUPI = provider.Gateway_details['Payment Methods'].every(
                (method) => method !== METHODS.CARD && method !== METHODS.UPI,
              );

              if (noCardOrUPI && isRecurringEnabled) {
                delete provider.Gateway_details[PROVIDER_KEYS.RECURRING];
              }
            }
          }
        } else if (name === 'Sodexo') {
          provider.Gateway_details[name] = isChecked;
        } else if (value === 'Recurring') {
          provider.Gateway_details[value] = isChecked;
        } else {
          provider.Gateway_details[name] = value;
        }

        return { provider };
      },
      () => this.validateGatewayDetails(name),
    );
  };

  changeGatewayWallets = ({ values }) => {
    this.setState((prevState) => {
      const { provider } = prevState;
      const updatedProvider = {
        ...provider,
        Gateway_details: {
          ...provider.Gateway_details,
          wallet_metadata: {
            ...provider.Gateway_details.wallet_metadata,
            wallets: values,
          },
        },
      };

      return { provider: updatedProvider };
    });
  };

  changeEnableAutoDebitSwitch = (isChecked) => {
    this.setState((prevState) => {
      const { provider } = prevState;
      provider.Gateway_details.ENABLE_AUTO_DEBIT = isChecked;
      return { provider };
    });
  };

  howtoGetDetails = () => {
    const { providers } = this.state;
    const { openModal, closeModal } = this.props;
    const selectedProviderWithAcquirer = this.getSelectedProviderWithAcquirer();
    openModal({
      size: 'large',
      component: (
        <HowToGetDetails
          providers={providers}
          selectedProvider={selectedProviderWithAcquirer}
          closeModal={closeModal}
        />
      ),
    });
  };

  /**
   * Check if mandatory methods are covered by gateway and razorpay
   * If mandatory methods are not covered in any gateway or razorpay show the method coverage step
   * If mandatory methods are covered on both gateway and razorpay open the testing confirmation modal
   * @param {result} result data from gateway and razorpay method coverage
   */
  findCoverage = (result) => {
    const { providers } = this.state;
    const mandatoryMethods =
      providers?.[this.getSelectedProviderWithAcquirer()]?.[PROVIDER_KEYS.MANDATORY_METHODS]
        ?.data_value;

    const gatewayCoverage = result[0].data?.gateway_methods?.methods;
    const razorpayCoverage = result[1].data;
    const isGatewayCoverageMissing = !areMandatoryMethodsCovered(mandatoryMethods, gatewayCoverage);
    const isRazorpayCoverageMissing = !areMandatoryMethodsCovered(
      mandatoryMethods,
      razorpayCoverage,
    );
    const showMethodCoverage = isGatewayCoverageMissing || isRazorpayCoverageMissing;

    let isTestingConfirmationModalOpen = false;
    if (showMethodCoverage) {
      this.goNext();
    } else {
      isTestingConfirmationModalOpen = true;
    }
    trackOptimizerEvents({
      objectName: 'method coverage',
      actionName: 'found',
      properties: {
        gateway: this.getSelectedProviderWithAcquirer(),
        mandatory_methods: mandatoryMethods,
        gateway_coverage: gatewayCoverage,
        razorpay_coverage: razorpayCoverage,
      },
      screen: 'Optimizer Integration Testing',
    });
    this.setState({
      showMethodCoverage,
      gatewayCoverage,
      razorpayCoverage,
      isTestingConfirmationModalOpen,
      isGatewayCoverageMissing,
      isRazorpayCoverageMissing,
    });
  };

  onSubmit = async () => {
    const { provider, isEdit, selectedProvider } = this.state;
    const { history, showNotification, splitz } = this.props;

    const Gateway_details = { ...provider?.Gateway_details };
    const paymentMethods = Gateway_details?.['Payment Methods'] ?? [];
    const hasNBMethod = paymentMethods.includes('netbanking');
    const hasUPIMethod = paymentMethods.includes('upi');
    const tpv = Number(Gateway_details?.TPV) ?? 0;

    if (Gateway_details.hasOwnProperty('TPV')) {
      if (hasUPIMethod && HAS_UPI_FEATURES.includes(selectedProvider)) {
        const upiFeatures = Gateway_details?.[UPI_FEATURES] || {};
        Gateway_details[UPI_FEATURES] = { ...upiFeatures, tpv };
      } else {
        delete Gateway_details[UPI_FEATURES];
      }

      if (hasNBMethod && HAS_NETBANKING_FEATURES.includes(selectedProvider)) {
        const netBankingFeatures = Gateway_details?.[NETBANKING_FEATURES] || {};
        Gateway_details[NETBANKING_FEATURES] = { ...netBankingFeatures, tpv };
      } else {
        delete Gateway_details[NETBANKING_FEATURES];
      }

      delete Gateway_details?.TPV;
    }

    const payload = {
      ...provider,
      Gateway: this.getSelectedProviderWithAcquirer(),
      Gateway_details,
    };

    const integrationAuditFlow =
      isIntegrationAuditEnabled(splitz) && isGatewaySupportIntegrationAudit(payload?.Gateway);

    if (integrationAuditFlow) {
      trackOptimizerEvents({
        objectName: 'test integration button',
        actionName: 'click',
        properties: {
          gateway: payload?.Gateway,
        },
        screen: 'Optimizer Integration Testing',
      });
    } else {
      trackOptimizerEvents({
        screen: `Optimizer ${isEdit ? 'Update' : 'Add'} Provider`,
        objectName: `${isEdit ? 'update' : 'add'} provider submit`,
        actionName: 'click',
        properties: {
          gateway: payload?.Gateway,
          payment_methods: payload?.Gateway_details?.['Payment Methods'],
        },
      });
    }

    this.setState({ isSaving: true });

    let res = null;

    try {
      if (isEdit) {
        delete payload.Currency;
        delete payload.Gateway_acquirer;

        if (integrationAuditFlow) {
          const { updateProviderData } = this.state;
          res = await updateProvider({
            providerId: payload?.Terminal_id,
            payload: updateProviderData,
          });
        } else {
          res = await editProvider({ payload });
        }
        // Later update with patch request for integration audit flow
      } else if (integrationAuditFlow) {
        // V3 API for provider addition which will create provider in pending state initially
        const result = await Promise.all([
          addProviderV3({ payload }),
          fetchRazorpayMethodCoverage(),
        ]);
        res = result[0].data;
        this.setState({ providerId: res?.Terminal_id, isSaving: false });
        this.findCoverage(result);
        return;
      } else {
        res = await addProvider({ payload });
      }

      if (res?.success) {
        showNotification({
          type: 'success',
          message: `${payload?.Provider_name} provider ${isEdit ? 'updated' : 'add'} successfully.`,
          closeTimeout: 3000,
        });

        trackAPIResults({
          name: `${isEdit ? 'Edit' : 'Add'} Provider`,
          properties: { success: true },
        });

        history.push('/optimizer/rules');
      }

      this.setState({ isSaving: false });
    } catch (error) {
      showNotification({
        type: 'error',
        message: error?.errors?.[0],
        closeTimeout: 3000,
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

  handleGoBack = () => {
    const { location, history } = this.props;
    const { prevPath = '' } = location?.state ?? {};
    this.trackEventOnClose();

    if (prevPath) {
      history.goBack();
    }
    history.push('/optimizer/rules');
  };

  closeTestingConfirmationModal = () => {
    this.setState({ isTestingConfirmationModalOpen: false });
  };

  startIntegrationTesting = () => {
    const { provider } = this.state;
    trackOptimizerEvents({
      objectName: 'self serve integration testing',
      actionName: 'selected',
      properties: {
        gateway: this.getSelectedProviderWithAcquirer(),
        integration_type: provider?.Gateway_details?.optimizer_seamless_disabled
          ? 'instant'
          : 's2s',
      },
      screen: 'Optimizer Integration Testing',
    });
    this.setState({ isTestingConfirmationModalOpen: false, isStartIntegrationTesting: true });
  };

  closeIntegrationTestingModal = () => {
    this.setState({ isStartIntegrationTesting: false });
  };

  raiseTicket = () => {
    const { provider } = this.state;
    trackOptimizerEvents({
      objectName: 'raise ticket',
      actionName: 'click',
      properties: {
        gateway: this.getSelectedProviderWithAcquirer(),
        integration_type: provider?.Gateway_details?.optimizer_seamless_disabled
          ? 'instant'
          : 's2s',
      },
      screen: 'Optimizer Integration Testing',
    });
    this.closeTestingConfirmationModal();
    this.closeIntegrationTestingModal();
    document.dispatchEvent(new CustomEvent('create-ticket', { detail: { id: 'tickets' } }));
  };

  updateProviderDetails = (data) => {
    this.changeProviderDetails(data);
    const { name, value } = data;
    this.setState((prevState) => {
      const { updateProviderData } = prevState;

      return { updateProviderData: { ...updateProviderData, [name]: value } };
    });
  };

  checkUpdateV3FlowValidDetails = () => {
    const { updateProviderData } = this.state;
    let valid = true;
    Object.values(updateProviderData).forEach((value) => {
      if (!value) {
        valid = false;
      }
    });
    return valid;
  };

  render() {
    const { user, splitz, org } = this.props;

    const {
      isEdit,
      steps,
      providers,
      isProviderNameValid,
      loadingProviders,
      provider,
      categorizedProviders,
      hasSeamlessOption,
      allDetailsValid,
      validationErrors,
      isSaving,
      showMethodCoverage,
      gatewayCoverage,
      razorpayCoverage,
      isGatewayCoverageMissing,
      isRazorpayCoverageMissing,
      isTestingConfirmationModalOpen,
      isStartIntegrationTesting,
      providerId,
    } = this.state;

    const selectedProviderWithAcquirer = this.getSelectedProviderWithAcquirer();
    const hasAccountTypeOption =
      selectedProviderWithAcquirer === RAZORPAY_GATEWAY_KEY &&
      providers?.[selectedProviderWithAcquirer]?.hasOwnProperty(PROVIDER_KEYS.GATEWAY_ACQUIRER);

    const allAvailableMethods =
      providers?.[selectedProviderWithAcquirer]?.['Payment Methods']?.data_value;

    const updateV3Flow =
      isEdit &&
      isIntegrationAuditEnabled(splitz) &&
      isGatewaySupportIntegrationAudit(selectedProviderWithAcquirer);

    return (
      <Box
        display="flex"
        flexDirection="column"
        gap={{ base: 'spacing.4', m: '14px' }}
        padding={{ base: ['25px', 'spacing.4'], m: ['spacing.6', 'spacing.7'] }}
      >
        <Link
          variant="button"
          icon={ChevronLeftIcon}
          iconPosition="left"
          onClick={this.handleGoBack}
        >
          Go back
        </Link>

        {isStartIntegrationTesting && (
          <IntegrationTesting
            isModalOpen={isStartIntegrationTesting}
            closeIntegrationTestingModal={this.closeIntegrationTestingModal}
            raiseTicket={this.raiseTicket}
            gateway={selectedProviderWithAcquirer}
            providerId={providerId}
            integrationType={
              provider?.Gateway_details?.optimizer_seamless_disabled ? 'instant' : 's2s'
            }
            providerName={provider?.Provider_name}
            gatewayMetaData={providers?.[selectedProviderWithAcquirer]}
            gatewayCoverage={gatewayCoverage}
            razorpayCoverage={razorpayCoverage}
          />
        )}

        {isTestingConfirmationModalOpen && (
          <TestingConfirmation
            isModalOpen={isTestingConfirmationModalOpen}
            closeTestingConfirmationModal={this.closeTestingConfirmationModal}
            startIntegrationTesting={this.startIntegrationTesting}
            raiseTicket={this.raiseTicket}
          />
        )}

        {loadingProviders ? (
          <Box display="flex" alignItems="center" justifyContent="center">
            <Spinner size="xlarge" label="Loading" />
          </Box>
        ) : (
          <Box display="flex" flexDirection="column" gap="spacing.5">
            {steps?.[1]?.show && (
              <SelectGateway
                isEdit={isEdit}
                steps={steps}
                isFormEdit={steps?.[1]?.edit ?? false}
                providers={providers}
                selectedProvider={selectedProviderWithAcquirer}
                categorizedProviders={categorizedProviders}
                gatewayDetails={provider?.Gateway_details}
                hasSeamlessOption={hasSeamlessOption}
                hasAccountTypeOption={hasAccountTypeOption}
                selectProvider={this.selectProvider}
                changeGateway={this.changeGateway}
                onEditClick={this.onEditClick}
              />
            )}
            {steps?.[2]?.show && (
              <Box ref={this.step2Ref}>
                <IntegrationType
                  isEdit={isEdit}
                  steps={steps}
                  isFormEdit={steps?.[2]?.edit ?? false}
                  providers={providers}
                  selectedProvider={selectedProviderWithAcquirer}
                  gatewayDetails={provider?.Gateway_details}
                  toggleIntegrationType={this.toggleIntegrationType}
                  changeGatewayDetails={this.changeGatewayDetails}
                  validateStep={this.disableStep}
                  onNextClick={this.goNext}
                  onEditClick={this.onEditClick}
                  updateV3Flow={updateV3Flow}
                />
              </Box>
            )}
            {steps?.[3]?.show && (
              <Box ref={this.step3Ref}>
                <ProviderDetails
                  isEdit={isEdit}
                  steps={steps}
                  isFormEdit={steps?.[3]?.edit ?? false}
                  selectedProvider={selectedProviderWithAcquirer}
                  provider={provider}
                  isProviderNameValid={isProviderNameValid}
                  hasSeamlessOption={hasSeamlessOption || hasAccountTypeOption}
                  changeProviderDetails={this.changeProviderDetails}
                  validateStep={this.disableStep}
                  onNextClick={this.goNext}
                  onEditClick={this.onEditClick}
                  updateV3Flow={updateV3Flow}
                  updateProviderDetails={this.updateProviderDetails}
                />
              </Box>
            )}
            {steps?.[4]?.show && (
              <Box ref={this.step4Ref}>
                <ProviderConfiguration
                  isEdit={isEdit}
                  isFormEdit={steps?.[4]?.edit ?? false}
                  providers={providers}
                  selectedProvider={selectedProviderWithAcquirer}
                  provider={provider}
                  isPaytmAutoDebitEnabled={user.isPaytmAutoDebitEnabled}
                  validationErrors={validationErrors}
                  isSubmitting={isSaving}
                  hasSeamlessOption={hasSeamlessOption || hasAccountTypeOption}
                  changeGatewayDetails={this.changeGatewayDetails}
                  changeGatewayWallets={this.changeGatewayWallets}
                  changeEnableAutoDebitSwitch={this.changeEnableAutoDebitSwitch}
                  onLinkClick={this.howtoGetDetails}
                  onEditClick={this.onEditClick}
                  isSubmitDisabled={
                    updateV3Flow
                      ? !(this.checkUpdateV3FlowValidDetails() && isProviderNameValid)
                      : !(this.checkAllValuesExist() && allDetailsValid)
                  }
                  onSubmit={this.onSubmit}
                  splitz={splitz}
                />
              </Box>
            )}
            {showMethodCoverage && (
              <Box ref={this.step5Ref}>
                <PaymentMethodCoverage
                  isEdit={isEdit}
                  isFormEdit={false}
                  selectedProvider={selectedProviderWithAcquirer}
                  methods={allAvailableMethods}
                  gatewayCoverage={gatewayCoverage}
                  razorpayCoverage={razorpayCoverage}
                  businessName={org.business_name}
                  isGatewayCoverageMissing={isGatewayCoverageMissing}
                  isRazorpayCoverageMissing={isRazorpayCoverageMissing}
                />
              </Box>
            )}
          </Box>
        )}
      </Box>
    );
  }
}

const mapStateToProps = (state) => {
  const { session, navigator } = state;
  return {
    user: session?.user,
    org: session?.org,
    activeProviders: navigator?.terminalProviders,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      openModal,
      closeModal,
      showNotification,
    },
    dispatch,
  );
};

export default compose(
  withSplitzService,
  connect(mapStateToProps, mapDispatchToProps),
)(withRouter(AddProvider));
