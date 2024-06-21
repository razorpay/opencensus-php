import React, { useState, useEffect } from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  Button,
  HelpCircleIcon,
  Box,
  ModalFooter,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';

import { trackOptimizerEvents, trackAPIResults } from 'merchant/views/Navigator/track';
import { FooterButtons } from 'merchant/views/Optimizer/AddProvider/components/IntegrationTesting/FooterButtons';
import { IntegrationAuditSummary } from 'merchant/views/Optimizer/AddProvider/components/IntegrationTesting/IntegrationAuditSummary';
import { PaymentTesting } from 'merchant/views/Optimizer/AddProvider/components/IntegrationTesting/PaymentTesting';
import { ProviderSettings } from 'merchant/views/Optimizer/AddProvider/components/IntegrationTesting/ProviderSettings';
import { RefundTesting } from 'merchant/views/Optimizer/AddProvider/components/IntegrationTesting/RefundTesting';
import { TestingSteps } from 'merchant/views/Optimizer/AddProvider/components/IntegrationTesting/TestingSteps';
import {
  fetchPayments,
  createRefund,
  fetchRefundDetails,
  fetchRefund,
  updateProvider,
  storeAuditData,
} from 'merchant/views/Optimizer/AddProvider/service';
import { showNotification } from 'merchant_common/reducers/notifications';

import { GoLiveConfirmation } from './GoLiveConfirmation';
import { INTEGRATION_TESTING_STEPS, AUDIT_TYPES } from './IntegrationTesting/constants';

const IntegrationTesting = ({
  isModalOpen,
  closeIntegrationTestingModal,
  raiseTicket,
  gateway,
  providerId,
  integrationType,
  providerName,
  gatewayMetaData,
  gatewayCoverage,
  razorpayCoverage,
  goToStep,
  shouldFetchSummary,
  activeMethods,
  user,
  org,
  showNotification,
}) => {
  const businessName = org?.business_name;
  const [steps, setSteps] = useState(INTEGRATION_TESTING_STEPS);

  // Payment testing screen
  const [amount, setAmount] = useState('1');
  const [merchantKey, setMerchantKey] = useState('');
  const [isPaymentDone, setIsPaymentDone] = useState(false);
  const [paymentId, setPaymentId] = useState('');
  const [isPaymentSuccessfull, setIsPaymentSuccessfull] = useState(false);
  const [isWebhookFailure, setIsWebhookFailure] = useState(false);
  const [paymentError, setPaymentError] = useState('');
  const [isPaymentDetailsFetched, setIsPaymentDetailsFetched] = useState(false);

  // Refund testing screen
  const [payments, setPayments] = useState([]);
  const [isPaymentsTableLoading, setIsPaymentsTableLoading] = useState(false);
  const [isRefundDone, setIsRefundDone] = useState(false);
  const [isRefundDetialsFetched, setIsRefundDetialsFetched] = useState(false);
  const [refundResult, setRefundResult] = useState({});

  // Provider settings screen
  const [defaultMethods, setDefaultMethods] = useState({});
  const [methods, setMethods] = useState({});
  const [isGoLiveConfirmation, setIsGoLiveConfirmation] = useState(false);
  const [isUpdatingProvider, setIsUpdatingProvider] = useState(false);

  useEffect(() => {
    if (isPaymentDone && isPaymentSuccessfull && !isWebhookFailure) {
      setIsPaymentsTableLoading(true);
      // 1 sec delay requierd to fetch payments to get correct status of payment recently done on payment testing screen
      setTimeout(() => {
        fetchPayments({ count: 20, notes: providerId })
          .then((res) => {
            const capturedPayments = res.data.items?.filter((item) => item.status === 'captured');
            setPayments(capturedPayments?.slice(0, 5));
          })
          .catch((err) => {
            setPayments([]);
            showNotification({
              type: 'error',
              message: err?.errors?.[0],
              closeTimeout: 3000,
            });
          })
          .finally(() => {
            setIsPaymentsTableLoading(false);
          });
      }, 1000);
    }
  }, [isPaymentDone, isPaymentSuccessfull, isWebhookFailure]);

  const setDefaultMethodsList = () => {
    const defaultMethodsList = {};
    gatewayCoverage?.forEach((item) => {
      if (item.enabled) {
        defaultMethodsList[item.method] = true;
      }
    });
    if (defaultMethodsList.wallet) {
      defaultMethodsList.wallet_metadata = {
        wallets: gatewayMetaData?.['Payment Methods']?.meta_data?.wallet_metadata?.wallets || [],
      };
    }
    setDefaultMethods(defaultMethodsList);
    if (!goToStep) {
      setMethods(defaultMethodsList);
    }
  };

  const changeIntegrationTestingStep = ({ name, successValue, failedValue }) => {
    trackOptimizerEvents({
      objectName: 'integration testing step',
      actionName: 'change',
      properties: {
        new_step: name,
        provider_id: providerId,
      },
      screen: 'Optimizer Integration Testing',
    });
    if (name === 'provider_settings') {
      setDefaultMethodsList();
    }
    if (!name) {
      return;
    }

    setSteps((prevState) => {
      const newSteps = prevState.map((step) => {
        if (step.value === name) {
          let success = false;
          let failed = false;
          if (successValue !== undefined) {
            success = successValue;
          }
          if (failedValue !== undefined) {
            failed = failedValue;
          }
          if (successValue !== undefined || failedValue !== undefined) {
            return { ...step, success, failed };
          } else {
            return { ...step, active: true };
          }
        }
        if (successValue !== undefined || failedValue !== undefined) {
          return { ...step };
        }
        return { ...step, active: false };
      });
      return newSteps;
    });
  };

  /**
   * Block steps
   * @param {object} items array of steps
   */
  const blockSteps = (items) => {
    setSteps((prevState) => {
      const newSteps = prevState.map((step) => {
        return { ...step, blocked: items.includes(step.value) };
      });
      return newSteps;
    });
  };

  useEffect(() => {
    changeIntegrationTestingStep({ name: goToStep });
    if (goToStep === 'provider_settings') {
      blockSteps(['payment_testing', 'refund_testing', 'integration_audit_summary']);
      const enabledMethods = {};
      activeMethods?.forEach((method) => {
        enabledMethods[method] = true;
      });
      setMethods(enabledMethods);
    }
    if (goToStep === 'integration_audit_summary') {
      blockSteps(['payment_testing', 'refund_testing']);
    }
  }, [activeMethods, goToStep]);

  useEffect(() => {
    if (shouldFetchSummary) {
      fetchPayments({
        count: 5,
        notes: providerId,
      }).then((response) => {
        if (response.success) {
          const items = response?.data?.items || [];
          if (items?.length > 0) {
            const payment = items[0] || {};
            if (payment.status === 'captured' || payment.status === 'refunded') {
              setIsPaymentSuccessfull(true);
            } else {
              setPaymentError(payment.error_description);
              setIsPaymentSuccessfull(false);
            }
            if (payment.late_authorized) {
              setIsWebhookFailure(true);
            }
            setAmount(payment.amount / 100);

            const paymentsListForRefund = items?.filter((item) => item.status === 'refunded');

            if (paymentsListForRefund?.length > 0) {
              Promise.all(
                paymentsListForRefund?.map((item) => {
                  if (item.status === 'refunded') {
                    return fetchRefund(item.id);
                  }
                  return null;
                }),
              ).then((refundResponse) => {
                let refundSuccess = false;
                for (let i = 0; i < 5; i++) {
                  if (refundResponse[i]?.success) {
                    const item = refundResponse[i].data?.items?.[0];
                    if (item.gateway_data?.code === 'GATEWAY_ERROR_TRANSACTION_PENDING') {
                      setRefundResult({
                        transactionId: item.payment_id,
                        refund_success: true,
                      });
                      refundSuccess = true;
                      break;
                    }
                  }
                }
                if (!refundSuccess) {
                  setRefundResult({
                    refund_success: false,
                  });
                }
              });
            }
          }
        }
      });
      const enabledMethods = {};
      activeMethods?.forEach((method) => {
        enabledMethods[method] = true;
      });
      setMethods(enabledMethods);
    }
  }, [shouldFetchSummary]);

  const currentStep = steps.find((step) => step.active)?.value;

  const testPayment = () => {
    trackOptimizerEvents({
      objectName: 'test payment button',
      actionName: 'click',
      screen: 'Optimizer Integration Testing',
      properties: {
        amount: Number(amount),
        method: 'upi',
        api_key: merchantKey,
        provider_id: providerId,
      },
    });
    const OPTIONS = {
      key: merchantKey,
      force_terminal_id: `term_${providerId}`,
      amount: parseInt(Number(amount) * 100),
      config: {
        display: {
          blocks: {
            upi: {
              name: 'Pay With UPI QR',
              instruments: [
                {
                  method: 'upi',
                  flows: ['intent', 'qr'],
                },
              ],
            },
          },
          sequence: ['block.upi'],
          preferences: {
            show_default_blocks: false,
          },
        },
      },
      notes: {
        integration_audit: providerId,
      },
      handler: (response) => {
        trackOptimizerEvents({
          objectName: 'checkout payment',
          actionName: 'successful',
          properties: {
            payment_id: response.razorpay_payment_id,
          },
          screen: 'Optimizer Integration Testing',
        });
        setIsPaymentDone(true);
        setPaymentId(response.razorpay_payment_id);
      },
      modal: {
        ondismiss: () => {
          trackOptimizerEvents({
            objectName: 'checkout',
            actionName: 'close',
            screen: 'Optimizer Integration Testing',
          });
        },
      },
    };
    const optiRzp = new window.Razorpay(OPTIONS);
    optiRzp.open();
    optiRzp.on('payment.failed', (response) => {
      trackOptimizerEvents({
        objectName: 'checkout payment',
        actionName: 'failed',
        properties: {
          error: response.error.description,
          payment_id: response.error?.metadata?.payment_id,
        },
        screen: 'Optimizer Integration Testing',
      });
      setIsPaymentDone(true);
      setIsPaymentSuccessfull(false);
      setIsPaymentDetailsFetched(true);
      setPaymentError(response.error.description);
      setPaymentId(response.error?.metadata?.payment_id);
    });
  };

  const testAnotherPayment = () => {
    trackOptimizerEvents({
      objectName: 'test another payment',
      actionName: 'click',
      screen: 'Optimizer Integration Testing',
    });
    setIsPaymentDone(false);
    setIsPaymentSuccessfull(false);
    setIsWebhookFailure(false);
  };

  const initiateRefund = (id, amount) => {
    trackOptimizerEvents({
      objectName: 'initiate refund',
      actionName: 'click',
      properties: {
        payment_id: id,
        amount,
      },
      screen: 'Optimizer Integration Testing',
    });
    createRefund({ id, amount })
      .then((res) => {
        storeAuditData(providerId, {
          audit_type: AUDIT_TYPES.refund,
          audit_data: { id: res?.data?.id },
        });
        const timeoutId = setTimeout(() => {
          setIsRefundDetialsFetched(true);
          setIsRefundDone(true);
          changeIntegrationTestingStep({
            name: 'refund_testing',
            successValue: !!refundResult?.refund_success,
            failedValue: !refundResult?.refund_success,
          });
        }, 5000);
        const refundPolling = setInterval(() => {
          fetchRefundDetails(res.data.id).then((resp) => {
            trackAPIResults({
              name: 'fetchRefundDetails',
              properties: {
                response: resp,
              },
            });
            if (resp.data.gateway_data.code === 'GATEWAY_ERROR_TRANSACTION_PENDING') {
              clearInterval(refundPolling);
              clearTimeout(timeoutId);
              setIsRefundDetialsFetched(true);
              setRefundResult({
                transactionId: id,
                refund_success: true,
              });
              setIsRefundDone(true);
              changeIntegrationTestingStep({ name: 'refund_testing', successValue: true });
            }
          });
        }, 1000);
      })
      .catch(() => {
        setIsRefundDetialsFetched(true);
        setRefundResult({
          transactionId: id,
          refund_success: false,
        });
        setIsRefundDone(true);
        changeIntegrationTestingStep({ name: 'refund_testing', failedValue: true });
      });
  };

  const takeProviderLive = (confirmation) => {
    trackOptimizerEvents({
      objectName: 'go live button',
      actionName: 'click',
      screen: 'Optimizer Integration Testing',
      properties: {
        provider_id: providerId,
        methods,
      },
    });
    if (confirmation) {
      trackOptimizerEvents({
        objectName: 'provider go live confirmation',
        actionName: 'click',
        properties: {
          provider_id: providerId,
          methods,
        },
        screen: 'Optimizer Integration Testing',
      });
    }
    let askConfirmation = false;
    const defaultMethodsList = Object.keys(defaultMethods);
    Object.keys(methods).forEach((key) => {
      if (methods[key] === true && !defaultMethodsList.includes(key)) {
        askConfirmation = true;
      }
    });
    // If there is a confirmation from merchant update provider
    if (!askConfirmation || confirmation) {
      setIsUpdatingProvider(true);
      updateProvider({ providerId, payload: { ...methods, status: 'activated' } })
        .then((res) => {
          if (res?.success) {
            showNotification({
              type: 'success',
              message: `${providerName} is now live.`,
              closeTimeout: 3000,
            });
          }
          closeIntegrationTestingModal();
          setIsGoLiveConfirmation(false);
        })
        .catch((error) => {
          showNotification({
            type: 'error',
            message: error?.errors?.[0],
            closeTimeout: 3000,
          });
        })
        .finally(() => {
          setIsUpdatingProvider(false);
        });
    } else {
      setIsGoLiveConfirmation(true);
      trackOptimizerEvents({
        objectName: 'provider go live confirmation',
        actionName: 'ask',
        properties: {
          provider_id: providerId,
          methods,
        },
        screen: 'Optimizer Integration Testing',
      });
    }
  };

  if (isGoLiveConfirmation) {
    return (
      <GoLiveConfirmation
        isModalOpen={isGoLiveConfirmation}
        closeGoLiveConfirmationModal={() => setIsGoLiveConfirmation(false)}
        takeProviderLive={takeProviderLive}
        isUpdatingProvider={isUpdatingProvider}
      />
    );
  }

  return (
    <Modal isOpen={isModalOpen} onDismiss={closeIntegrationTestingModal} size="large" zIndex={9999}>
      <ModalHeader
        title="Optimizer Integration Testing"
        subtitle="The terminal would go live once all tests are performed and the issues resolved"
        trailing={
          <Button
            variant="tertiary"
            icon={HelpCircleIcon}
            iconPosition="left"
            onClick={raiseTicket}
          >
            HELP
          </Button>
        }
      />
      <ModalBody padding="spacing.0">
        <Box display="flex" flexDirection="row">
          <Box display="flex" flexDirection="column">
            <TestingSteps steps={steps} />
          </Box>
          <Box display="flex" flexDirection="column">
            {currentStep === 'payment_testing' && (
              <PaymentTesting
                currency={user.merchant?.currency}
                amount={amount}
                setAmount={setAmount}
                setMerchantKey={setMerchantKey}
                isPaymentDone={isPaymentDone}
                paymentId={paymentId}
                isPaymentSuccessfull={isPaymentSuccessfull}
                setIsPaymentSuccessfull={setIsPaymentSuccessfull}
                isWebhookFailure={isWebhookFailure}
                setIsWebhookFailure={setIsWebhookFailure}
                paymentError={paymentError}
                setPaymentError={setPaymentError}
                gateway={gateway}
                businessName={businessName}
                changeIntegrationTestingStep={changeIntegrationTestingStep}
                isPaymentDetailsFetched={isPaymentDetailsFetched}
                setIsPaymentDetailsFetched={setIsPaymentDetailsFetched}
                providerId={providerId}
              />
            )}
            {currentStep === 'refund_testing' && (
              <RefundTesting
                gateway={gateway}
                payments={payments}
                setPayments={setPayments}
                isPaymentsTableLoading={isPaymentsTableLoading}
                initiateRefund={initiateRefund}
                isRefundDetialsFetched={isRefundDetialsFetched}
                setIsRefundDetialsFetched={setIsRefundDetialsFetched}
                refundResult={refundResult}
                setRefundResult={setRefundResult}
                integrationType={integrationType}
              />
            )}
            {currentStep === 'integration_audit_summary' && (
              <IntegrationAuditSummary
                currency={user.merchant?.currency}
                amount={amount}
                gateway={gateway}
                integrationType={integrationType}
                paymentError={paymentError}
                isPaymentSuccessfull={isPaymentSuccessfull}
                isWebhookFailure={isWebhookFailure}
                refundResult={refundResult}
                razorpayCoverage={razorpayCoverage}
                gatewayCoverage={gatewayCoverage}
              />
            )}
            {currentStep === 'provider_settings' && (
              <ProviderSettings
                providerName={providerName}
                gatewayMetaData={gatewayMetaData}
                methods={methods}
                setMethods={setMethods}
                integrationType={integrationType}
              />
            )}
          </Box>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <FooterButtons
            currentStep={currentStep}
            steps={steps}
            isPaymentSuccessfull={!isWebhookFailure && isPaymentSuccessfull}
            isPaymentDone={isPaymentDone}
            testPayment={testPayment}
            raiseTicket={raiseTicket}
            isRefundDone={isRefundDone}
            testAnotherPayment={testAnotherPayment}
            changeIntegrationTestingStep={changeIntegrationTestingStep}
            takeProviderLive={takeProviderLive}
            isUpdatingProvider={isUpdatingProvider}
          />
        </Box>
      </ModalFooter>
    </Modal>
  );
};

const mapStateToProps = (state) => {
  const { session } = state;
  return {
    user: session?.user,
    org: session?.org,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      showNotification,
    },
    dispatch,
  );
};

export default compose(connect(mapStateToProps, mapDispatchToProps))(IntegrationTesting);
