import React, { useState, useEffect, useReducer } from 'react';
import {
  Box,
  Badge,
  Link,
  ChevronLeftIcon,
  Card,
  CardBody,
  Text,
  Heading,
  Button,
  Divider,
  Switch,
  Spinner,
} from '@razorpay/blade/components';
import moment from 'moment';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';

import { withSplitzService } from 'common/splitz';
import { titleCase, isBlank } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';
import { gatewayLogos } from 'merchant/views/Navigator/components/util';
import {
  METHODS_MAP,
  TPV_OPTIONS,
  SEAMLESS_PROVIDERS,
  PROVIDER_KEYS,
  WALLET_AUTO_DEBIT_KEY,
  RAZORPAY_GATEWAY_KEY,
} from 'merchant/views/Navigator/constants';
import { WALLETS_MAP } from 'merchant/views/Optimizer/AddProvider/components/IntegrationTesting/constants';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { IntegrationTesting } from './components';
import {
  fetchPayments,
  fetchGatewayEnabledMethods,
  refreshGatewayEnabledMethods,
  fetchRazorpayMethodCoverage,
  updateProvider,
} from './service';
import {
  areMandatoryMethodsCovered,
  isIntegrationAuditEnabled,
  isGatewaySupportIntegrationAudit,
} from './utils';

const reducer = (state, action) => {
  switch (action.type) {
    case 'set_loading_gateways':
      return { ...state, loadingGateways: action.payload };
    case 'set_supported_gateways':
      return { ...state, supportedGateways: action.payload };
    case 'set_active_methods':
      return { ...state, activeMethods: action.payload };
    case 'set_is_updating':
      return { ...state, isUpdating: action.payload };
    case 'set_show_audit_summary_button':
      return { ...state, showAuditSummaryButton: action.payload };
    case 'set_gateway_coverage':
      return { ...state, gatewayCoverage: action.payload };
    case 'set_razorpay_coverage':
      return { ...state, razorpayCoverage: action.payload };
    case 'set_go_to_step':
      return { ...state, goToStep: action.payload };
    case 'set_should_fetch_summary':
      return { ...state, shouldFetchSummary: action.payload };
    default:
      return state;
  }
};

const initialState = {
  loadingGateways: true,
  supportedGateways: [],
  activeMethods: [],
  isUpdating: false,
  showAuditSummaryButton: false,
  gatewayCoverage: [],
  razorpayCoverage: [],
  goToStep: '',
  shouldFetchSummary: true,
};

const ProviderView = (props) => {
  const [state, dispatch] = useReducer(reducer, initialState);
  const { activeProviders, loadingProviders, user, splitz } = props;
  const {
    supportedGateways,
    gatewayCoverage,
    razorpayCoverage,
    goToStep,
    activeMethods,
    loadingGateways,
    isUpdating,
    showAuditSummaryButton,
    shouldFetchSummary,
  } = state;

  const selectedProviderId = props.location.pathname.split('/').pop();
  const provider = activeProviders?.find((provider) => provider.Terminal_id === selectedProviderId);

  const integrationAuditFlowEnabled =
    isIntegrationAuditEnabled(splitz) && isGatewaySupportIntegrationAudit(provider?.Gateway);

  const {
    'UPI Features': upiFeatures,
    'Netbanking Features': netbankingFeatures,
    'Payment Methods': paymentMethods,
    optimizer_seamless_disabled,
    [WALLET_AUTO_DEBIT_KEY]: walletAutoDebit,
    Recurring,
    Sodexo,
  } = provider?.Gateway_details || {};

  const [showIntegrationAuditModal, setShowIntegrationAuditModal] = useState(false);

  useEffect(() => {
    const params = {
      url: 'terminals/proxy/optimizer/supported_gateways',
      method: 'get',
    };
    if (supportedGateways?.length <= 0) {
      merchantFetch(params)
        .then((res) => {
          if (res?.success) {
            dispatch({ type: 'set_supported_gateways', payload: res?.data });
          }
        })
        .finally(() => {
          dispatch({ type: 'set_loading_gateways', payload: false });
        });
    }

    // Fetch payments to check if there are any payments made
    fetchPayments({
      count: 5,
      notes: selectedProviderId,
    }).then((response) => {
      if (response?.success) {
        if (response?.data?.items?.length > 0) {
          dispatch({ type: 'set_show_audit_summary_button', payload: true });
        }
      }
    });

    if (razorpayCoverage?.length <= 0) {
      // Fetch razorpay method coverage
      fetchRazorpayMethodCoverage().then((response) => {
        if (response?.success) {
          dispatch({ type: 'set_razorpay_coverage', payload: response?.data });
        }
      });
    }

    if (gatewayCoverage?.length <= 0) {
      // Fetch gateway coverage
      fetchGatewayEnabledMethods(selectedProviderId).then((response) => {
        if (response?.success) {
          dispatch({ type: 'set_gateway_coverage', payload: response?.data?.methods });
        }
      });
    }
  }, []);

  useEffect(() => {
    const allMethods = [...paymentMethods];
    if (Recurring) {
      allMethods.push('recurring');
    }
    if (Sodexo) {
      allMethods.push('sodexo');
    }
    dispatch({ type: 'set_active_methods', payload: allMethods });
  }, [provider, paymentMethods, Recurring]);

  const providerDetails = Object.entries(provider?.Gateway_details || {});

  const wallets = provider?.Gateway_details?.wallet_metadata?.wallets || [];
  const walletsNames = wallets.map((wallet) => WALLETS_MAP[wallet] || titleCase(wallet));
  const seamlessOptionExist =
    SEAMLESS_PROVIDERS?.includes(provider?.Gateway) &&
    provider?.Gateway_details?.hasOwnProperty('optimizer_seamless_disabled');
  const isRecurringEnabled = provider?.Gateway_details?.hasOwnProperty('Recurring');

  let strPaymentMethods = paymentMethods?.map((method) => METHODS_MAP[method])?.join(', ') ?? '';
  const isSodexoEnabled =
    provider?.Gateway_details?.hasOwnProperty(PROVIDER_KEYS.SODEXO) &&
    provider?.Gateway_details?.Sodexo;

  if (isSodexoEnabled) {
    strPaymentMethods += ', Sodexo';
  }

  const isPaytmAutoDebitEnabled = provider?.Gateway === 'paytm' && !!user?.isPaytmAutoDebitEnabled;

  const gatewayName = provider?.Gateway === RAZORPAY_GATEWAY_KEY ? 'Razorpay' : provider?.Gateway;

  const IGNORE_FIELDS = [
    'Payment Methods',
    'UPI Features',
    'Netbanking Features',
    'optimizer_seamless_disabled',
    PROVIDER_KEYS.SODEXO,
    WALLET_AUTO_DEBIT_KEY,
    PROVIDER_KEYS.RECURRING,
    PROVIDER_KEYS.GATEWAY_ACQUIRER,
  ];
  const ignoreFields =
    !isPaytmAutoDebitEnabled || !walletAutoDebit
      ? IGNORE_FIELDS.concat('CLIENT_KEY', 'CLIENT_SECRET')
      : IGNORE_FIELDS;

  const gatewayMethods =
    supportedGateways?.[provider?.Gateway?.toLowerCase()]?.['Payment Methods']?.data_value ?? [];
  const providerStatus = provider?.Status;

  const handleGoBack = () => {
    const { location, history } = props;
    const { prevPath = '' } = location?.state ?? {};

    if (prevPath) {
      history.goBack();
    }
    history.push('/optimizer/rules');
  };

  const TPVDetails = ({ tpv }) => {
    if (!isBlank(tpv)) {
      return (
        <>
          <Divider />
          <Box display="flex" gap="spacing.4">
            <Box display="flex" gap="spacing.2" alignItems="center" width="200px">
              <Text>TPV</Text>
            </Box>
            <Box display="flex" gap="spacing.2" alignItems="center">
              <Text>{TPV_OPTIONS[tpv]}</Text>
            </Box>
          </Box>
        </>
      );
    }
    return null;
  };

  const updateMethods = ({ isChecked, value }) => {
    dispatch({ type: 'set_is_updating', payload: true });
    let payload = { [value]: isChecked };
    if (value === 'wallet') {
      payload = {
        ...payload,
        wallet_metadata: {
          wallets:
            supportedGateways?.[provider?.Gateway?.toLowerCase()]?.['Payment Methods']?.meta_data
              ?.wallet_metadata?.wallets || [],
        },
      };
    }
    updateProvider({ providerId: provider.Terminal_id, payload })
      .then((res) => {
        dispatch({
          type: 'set_active_methods',
          payload: res?.data?.Gateway_details?.['Payment Methods'] || activeMethods,
        });
        showNotification({
          type: 'success',
          message: 'Payment method updated successfully',
          closeTimeout: 3000,
        });
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err?.errors?.[0],
          closeTimeout: 3000,
        });
      })
      .finally(() => {
        dispatch({ type: 'set_is_updating', payload: false });
      });
  };

  const closeIntegrationAuditModal = () => {
    setShowIntegrationAuditModal(false);
  };

  const raiseTicket = () => {
    closeIntegrationAuditModal();
    document.dispatchEvent(new CustomEvent('create-ticket', { detail: { id: 'tickets' } }));
  };

  const viewIntegrationAuditResults = () => {
    dispatch({ type: 'set_should_fetch_summary', payload: true });
    dispatch({ type: 'set_go_to_step', payload: 'integration_audit_summary' });
    setShowIntegrationAuditModal(true);
  };

  const viewDetailedProviderSettings = () => {
    dispatch({ type: 'set_should_fetch_summary', payload: false });
    dispatch({ type: 'set_go_to_step', payload: 'provider_settings' });
    setShowIntegrationAuditModal(true);
  };

  const restartIntegrationTesting = async () => {
    const response = await refreshGatewayEnabledMethods(provider?.Terminal_id);
    if (response?.success) {
      dispatch({ type: 'set_gateway_coverage', payload: response?.data?.methods });
    }

    const mandatoryMethods =
      supportedGateways?.[provider?.Gateway?.toLowerCase()]?.[PROVIDER_KEYS.MANDATORY_METHODS]
        ?.data_value;
    const isGatewayCoverageMissing = !areMandatoryMethodsCovered(
      mandatoryMethods,
      response?.data?.methods,
    );
    const isRazorpayCoverageMissing = !areMandatoryMethodsCovered(
      mandatoryMethods,
      razorpayCoverage,
    );
    const showMethodCoverage = isGatewayCoverageMissing || isRazorpayCoverageMissing;

    if (!showMethodCoverage) {
      dispatch({ type: 'set_should_fetch_summary', payload: false });
      dispatch({ type: 'set_go_to_step', payload: 'payment_testing' });
      setShowIntegrationAuditModal(true);
    } else {
      props.history.push({
        pathname: `/optimizer/update-provider/${provider?.Terminal_id}?for=coverage`,
        state: {
          gatewayCoverage: response?.data?.methods,
          razorpayCoverage,
        },
      });
    }
  };

  const editProvider = () => {
    props.history.push(`/optimizer/update-provider/${provider?.Terminal_id}`);
  };

  return (
    <Box
      display="flex"
      flexDirection="column"
      gap={{ base: 'spacing.4', m: '14px' }}
      padding={{ base: ['25px', 'spacing.4'], m: ['spacing.6', 'spacing.7'] }}
    >
      <Link variant="button" icon={ChevronLeftIcon} iconPosition="left" onClick={handleGoBack}>
        Go back
      </Link>
      {showIntegrationAuditModal && (
        <IntegrationTesting
          isModalOpen={showIntegrationAuditModal}
          closeIntegrationTestingModal={closeIntegrationAuditModal}
          raiseTicket={raiseTicket}
          gateway={provider?.Gateway}
          providerId={provider?.Terminal_id}
          integrationType={
            provider?.Gateway_details?.optimizer_seamless_disabled ? 'instant' : 's2s'
          }
          providerName={provider?.Provider_name}
          gatewayMetaData={supportedGateways?.[provider?.Gateway?.toLowerCase()]}
          gatewayCoverage={gatewayCoverage}
          razorpayCoverage={razorpayCoverage}
          goToStep={goToStep}
          shouldFetchSummary={shouldFetchSummary}
          activeMethods={activeMethods}
        />
      )}
      <Box display="flex" flexDirection="row">
        <Box display="flex" flexDirection="column" width="58%">
          <Card borderRadius="medium" elevation="none">
            <CardBody>
              {loadingProviders ? (
                <Spinner marginLeft="spacing.5" marginTop="spacing.5" />
              ) : (
                <>
                  <Box display="flex" flexDirection="row">
                    <Heading weight="semibold">{provider?.Provider_name}</Heading>
                    <Badge
                      color={providerStatus === 'activated' ? 'positive' : 'notice'}
                      marginLeft="spacing.3"
                    >
                      {providerStatus === 'activated' ? 'LIVE' : 'PENDING'}
                    </Badge>
                  </Box>
                  <Text as="p">
                    Created on {moment.unix(provider?.created_at).format('ddd MMM D YYYY, h:ma')}
                  </Text>
                </>
              )}
            </CardBody>
          </Card>
          <Card borderRadius="medium" elevation="none" marginTop="spacing.5" padding="spacing.0">
            <CardBody>
              <Box
                display="flex"
                alignItems="center"
                backgroundColor="surface.background.gray.moderate"
                padding={['spacing.5', 'spacing.6']}
              >
                <Heading weight="regular">Details</Heading>
                <Box marginLeft="auto">
                  <Button onClick={editProvider}>Edit Details</Button>
                </Box>
              </Box>
              {loadingProviders ? (
                <Spinner marginLeft="spacing.10" marginTop="spacing.10" marginBottom="spacing.10" />
              ) : (
                <Box
                  display="flex"
                  flexDirection="column"
                  gap="spacing.5"
                  padding={['spacing.5', 'spacing.6']}
                  backgroundColor="surface.background.gray.intense"
                >
                  <Box display="flex" gap="spacing.4">
                    <Box display="flex" gap="spacing.2" alignItems="center" minWidth="200px">
                      <Text>Description</Text>
                    </Box>
                    <Box display="flex" gap="spacing.2" alignItems="center">
                      <Text>{provider?.Description}</Text>
                    </Box>
                  </Box>
                  <Divider />
                  <Box display="flex" gap="spacing.4">
                    <Box display="flex" gap="spacing.2" alignItems="center" minWidth="200px">
                      <Text>Gateway</Text>
                    </Box>
                    <Box display="flex" gap="spacing.4" alignItems="center">
                      <img
                        src={gatewayLogos[gatewayName?.toLowerCase()]}
                        alt={gatewayName}
                        height={20}
                      />
                      <Text>{gatewayName}</Text>
                    </Box>
                  </Box>
                  {strPaymentMethods !== '' && (
                    <>
                      <Divider />
                      <Box display="flex" gap="spacing.4">
                        <Box display="flex" gap="spacing.2" alignItems="center" minWidth="200px">
                          <Text>Methods enabled</Text>
                        </Box>
                        <Box display="flex" gap="spacing.2" alignItems="center">
                          <Text>{strPaymentMethods}</Text>
                        </Box>
                      </Box>
                    </>
                  )}
                  {walletsNames?.length > 0 && (
                    <>
                      <Divider />
                      <Box display="flex" gap="spacing.4">
                        <Box display="flex" gap="spacing.2" alignItems="center" minWidth="200px">
                          <Text>Wallets enabled</Text>
                        </Box>
                        <Box display="flex" gap="spacing.2" alignItems="center">
                          <Text>{walletsNames.join(', ')}</Text>
                        </Box>
                      </Box>
                    </>
                  )}
                  {isPaytmAutoDebitEnabled && (
                    <>
                      <Divider />
                      <Box display="flex" gap="spacing.4">
                        <Box display="flex" gap="spacing.2" alignItems="center" minWidth="200px">
                          <Text>Wallet auto-debit Enabled</Text>
                        </Box>
                        <Box display="flex" gap="spacing.2" alignItems="center">
                          <Text>{walletAutoDebit ? 'Yes' : 'No'}</Text>
                        </Box>
                      </Box>
                    </>
                  )}
                  {upiFeatures?.tpv ? (
                    <TPVDetails tpv={netbankingFeatures?.tpv} />
                  ) : netbankingFeatures?.tpv ? (
                    <TPVDetails tpv={netbankingFeatures?.tpv} />
                  ) : null}
                  {seamlessOptionExist && (
                    <>
                      <Divider />
                      <Box display="flex" gap="spacing.4">
                        <Box display="flex" gap="spacing.2" alignItems="center" minWidth="200px">
                          <Text>Integration type</Text>
                        </Box>
                        <Box display="flex" gap="spacing.2" alignItems="center">
                          <Text>
                            {optimizer_seamless_disabled ? 'Instant (beta)' : 'Server-to-Server'}
                          </Text>
                        </Box>
                      </Box>
                    </>
                  )}
                  {isRecurringEnabled && (
                    <>
                      <Divider />
                      <Box display="flex" gap="spacing.4">
                        <Box display="flex" gap="spacing.2" alignItems="center" minWidth="200px">
                          <Text>Recurring</Text>
                        </Box>
                        <Box display="flex" gap="spacing.2" alignItems="center">
                          <Text>{Recurring ? 'Enabled' : 'Disabled'}</Text>
                        </Box>
                      </Box>
                    </>
                  )}
                  {providerDetails.map(([key, values], index) => {
                    if (!ignoreFields.includes(key) && !key.includes('metadata')) {
                      return (
                        <React.Fragment key={index}>
                          <Divider />
                          <Box display="flex" gap="spacing.4">
                            <Box
                              display="flex"
                              gap="spacing.2"
                              alignItems="center"
                              minWidth="200px"
                            >
                              <Text>{titleCase(key)}</Text>
                            </Box>
                            <Box display="flex" gap="spacing.2" alignItems="center">
                              <Text>{values || '**********'}</Text>
                            </Box>
                          </Box>
                        </React.Fragment>
                      );
                    }
                    return null;
                  })}
                </Box>
              )}
            </CardBody>
          </Card>
        </Box>
        <Box display="flex" flexDirection="column" marginLeft="spacing.5" width="40%">
          <Card borderRadius="medium" elevation="none" height="100%">
            <CardBody height="100%">
              <Heading weight="regular" size="medium">
                {providerStatus === 'activated' ? 'Method' : 'Provider'} settings
              </Heading>
              {providerStatus === 'activated' && (
                <Box display="flex" flexDirection="column" marginTop="spacing.6">
                  {loadingGateways ? (
                    <Spinner
                      marginLeft="spacing.5"
                      marginTop="spacing.11"
                      marginBottom="spacing.10"
                    />
                  ) : (
                    gatewayMethods?.map((method) => (
                      <Box display="flex" gap="spacing.4" key={method} marginBottom="spacing.5">
                        <Box display="flex" gap="spacing.2" alignItems="center" width="150px">
                          <Text weight="semibold">{METHODS_MAP[method]}</Text>
                        </Box>
                        <Box as="label" display="flex" gap="spacing.2" alignItems="center">
                          <Switch
                            accessibilityLabel={METHODS_MAP[method]}
                            size="medium"
                            name={method}
                            value={method}
                            isChecked={activeMethods?.includes(method)}
                            onChange={updateMethods}
                            isDisabled={
                              (method === 'sodexo' && !activeMethods?.includes('card')) ||
                              isUpdating
                            }
                          />
                          <Text>{activeMethods?.includes(method) ? 'Enabled' : 'Disabled'}</Text>
                        </Box>
                      </Box>
                    ))
                  )}
                </Box>
              )}
              {integrationAuditFlowEnabled && showAuditSummaryButton && (
                <Button
                  variant="secondary"
                  onClick={viewIntegrationAuditResults}
                  marginTop="spacing.7"
                  display="block"
                  isFullWidth={true}
                >
                  View integration audit results
                </Button>
              )}
              {providerStatus === 'activated' && (
                <Button
                  variant="secondary"
                  onClick={viewDetailedProviderSettings}
                  marginTop="spacing.5"
                  display="block"
                  isFullWidth={true}
                >
                  View detailed provider settings
                </Button>
              )}
              {integrationAuditFlowEnabled && (
                <Button
                  variant="secondary"
                  onClick={restartIntegrationTesting}
                  marginTop="spacing.5"
                  display="block"
                  isFullWidth={true}
                >
                  Restart integration testing
                </Button>
              )}
            </CardBody>
          </Card>
        </Box>
      </Box>
    </Box>
  );
};

const mapStateToProps = (state) => {
  const { session, navigator } = state;
  return {
    user: session?.user,
    org: session?.org,
    activeProviders: navigator?.terminalProviders,
    loadingProviders: navigator?.providers_loading,
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
)(ProviderView);
