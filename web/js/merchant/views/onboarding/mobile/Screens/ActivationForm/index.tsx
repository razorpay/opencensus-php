import React, { useState, useEffect, useRef } from 'react';
import styled from 'styled-components';
import startCase from 'lodash/startCase';
import View from '@razorpay/blade-old/src/atoms/View';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import Checkbox from '@razorpay/blade-old/src/atoms/Checkbox';
import Button from '@razorpay/blade-old/src/atoms/Button';
import Link from '@razorpay/commander-shield/src/shared/Link';
import { FullPageLoader } from 'common/components/Loader';
import { withRouter, RouteComponentProps } from 'react-router-dom';
import {
  isL1Submitted,
  getPoiVerificationStatus,
  hasSelectedBlacklistCategory,
  isUnregisteredBusiness,
  checkIfDedupe,
} from '../../services/utils';
import { Tabs, Tab } from 'common/components/Tabs';
import { useActivationFormState } from '../../context/store';
import useActivation from '../../hooks/useActivation';
import useBusinessCategory from '../../hooks/useBusinessCategory';
import BankDetails from '../../BankDetails';
import ContactDetails from '../../ContactDetails';
import BusinessOverview from '../../BusinessOverview';
import BusinessDetails from '../../BusinessDetails';
import DocumentUpload from '../../DocumentUpload';
import SaveAndExitModal from '../../SaveAndExitModal';
import FAQs from '../../FAQs/FAQs';
import { ActivationModal, ModalTypeT } from '../../ActivationModals';
import { useApp } from 'common/context/App';
import { getMode, switchMode } from 'common/services/mode';
import useTrackEvents from 'merchant/hooks/useTrackEvents';

type NextTextT = 'Submit And Verify' | 'Submit KYC' | 'Next';

const StyledFooter = styled(View)`
  box-sizing: border-box;
  width: 100%;
  position: fixed;
  bottom: 0;
  padding: 16px;
  background-color: ${({ theme }) => theme.colors.background['200']};
  border-top: 1px solid rgba(22, 47, 86, 0.1);
`;

const StyledActivationForm = styled(View)`
  min-height: 100vh;
  background-color: ${({ theme }) => theme.colors.background[400]};
`;

const StyledHeader = styled(View)`
  background-color: ${({ theme }) => theme.colors.background[200]};
`;

const AcknowledgementFooter = styled(View)`
  padding: 4px 20px 2px;
`;

const Seprator = styled.hr`
  margin: 6px 0 8px;
`;
const CheckBoxStyle = styled(View)`
  display: inline-block;
  margin-right: 2.5px;
`;

const TnCLink = styled(Link)`
  bottom: 6.55px;
`;

const ActivationForm: React.FC<RouteComponentProps> = ({ history }) => {
  const { status: activationStatus, data, postData, refetch } = useActivation();
  const { user, experiments } = useApp();
  const [status, businessCategoriesData] = useBusinessCategory('');
  const isContactDetailsCompleted = useActivationFormState(
    (state) => state.isContactDetailsCompleted,
  );
  const isBusinessOverviewCompleted = useActivationFormState(
    (state) => state.isBusinessOverviewCompleted,
  );
  const isBusinessDetailsCompleted = useActivationFormState(
    (state) => state.isBusinessDetailsCompleted,
  );
  const isBankAndCompanyDetailsCompleted = useActivationFormState(
    (state) => state.isBankAndCompanyDetailsCompleted,
  );
  const isDocumentsUploadCompleted = useActivationFormState(
    (state) => state.isDocumentsUploadCompleted,
  );
  const isL1Acknowledge = useActivationFormState((state) => state.is_l1_acknowledge);
  const setL1Acknowledge = useActivationFormState((state) => state.setL1Acknowledge);
  const autoScrollRef = useRef<HTMLDivElement>(null);

  const isTestMode = getMode(user.current) === 'test';
  const setIsOpen = useActivationFormState((state) => state.setIsFAQOpen);
  const activeTabId = useActivationFormState((state) => state.active_tab_id);
  const setActiveTabId = useActivationFormState((state) => state.setActiveTabId);
  const [isSaveAndExitModalOpen, setIsSaveAndExitModalOpen] = useState(false);
  const [isModalOpen, setIsModalOpen] = useState<boolean>(false);
  const [isApiCalling, setIsApiCalling] = useState<boolean>(false);
  const [modalType, setModalType] = useState<ModalTypeT>('');

  const isEmailVerificationRequired =
    (experiments.isEmailMandatoryOnL1 || experiments.isEmailNonMandatoryOnL1) &&
    !user.user?.signup_via_email;

  const trackEvents = useTrackEvents();

  const sendSegmentEvents = (isFormCloseAction) => {
    const isL2Form = isL1Submitted(data?.activation_form_milestone);
    const objectName = isL2Form ? 'L2 form' : 'L1 form';
    const actionName = isFormCloseAction ? 'close' : 'load';
    const activationType = isL2Form ? 'kyc' : 'act';
    trackEvents({
      objectName,
      actionName,
      screen: 'home page',
      eventAction: 'success',
      properties: {
        result: 'success',
      },
      activationType,
      isLJReqiuired: false,
    });
  };

  useEffect(() => {
    trackEvents({
      objectName: 'Activation Tab',
      actionName: 'Loaded',
      screen: 'home page',
      properties: {
        tab: startCase(activeTabId),
      },
      toCleverTap: true,
    });
  }, [activeTabId]);

  useEffect(() => {
    if (data) {
      const isFormCloseAction = false;
      sendSegmentEvents(isFormCloseAction);
    }
    return () => {
      if (data) {
        const isFormCloseAction = true;
        sendSegmentEvents(isFormCloseAction);
      }
    };
  }, [data]);

  const canShowTncCheckbox = autoScrollRef.current;

  useEffect(() => {
    if (
      autoScrollRef.current &&
      activeTabId === 'business_details' &&
      isBusinessDetailsCompleted &&
      data
    ) {
      const thresholdToScroll = 1300;
      const top = autoScrollRef.current.getBoundingClientRect().top;
      // merchant should not scroll instantly.
      setTimeout(() => {
        if (autoScrollRef.current && top < thresholdToScroll) {
          autoScrollRef.current.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
            inline: 'end',
          });
        }
      }, 800);
    }
  }, [data, activeTabId, isBusinessDetailsCompleted, canShowTncCheckbox]);

  if (activationStatus === 'loading') {
    return <FullPageLoader />;
  }

  if (activationStatus === 'error') {
    return <div>Something went wrong</div>;
  }

  const {
    activation_form_milestone,
    can_submit,
    submitted,
    locked,
    poi_verification_status,
    activation_status,
  } = data;

  const isInstantActivationEnabled = experiments.isInstantActivationEnabled;

  const merchantDedupeStatus = checkIfDedupe({ ...data, isInstantActivationEnabled });
  const isDedupe = merchantDedupeStatus === 'blocked';

  const isBlackListCategory =
    status === 'success' && hasSelectedBlacklistCategory(data, businessCategoriesData);

  const isCurrentTabActive = isEmailVerificationRequired
    ? activeTabId === 'contact_details'
    : activeTabId === 'business_details';

  const submitL1 = () => {
    setIsApiCalling(true);
    trackEvents({
      objectName: 'L1 Form',
      actionName: 'Submitted',
      screen: 'home page',
      toCleverTap: true,
    });
    postData({ activation_form_milestone: 'L1' })
      .then((res) => {
        setIsApiCalling(false);
        if (res && res.activation_form_milestone === 'L1') {
          trackEvents({
            objectName: 'L1 Form',
            actionName: 'Result',
            screen: 'home page',
            properties: {
              status: 'sucess',
            },
            toCleverTap: true,
          });

          const dedupeStatus = checkIfDedupe({ ...res, isInstantActivationEnabled });
          if (dedupeStatus === 'blocked') {
            setModalType('dedupe');
          } else if (
            isUnregisteredBusiness(res.business_type) &&
            res.poi_verification_status === 'initiated' &&
            experiments.canSkipPoiValidation &&
            !experiments.isL2AllowedForPoiInitiated
          ) {
            setModalType('poi_initiated');
          } else if (res.activated && res.activation_status === 'instantly_activated') {
            setModalType('payment_enable');
            switchMode(user.current, 'live');
          } else {
            setModalType('payment_disable');
          }
          setIsModalOpen(true);
        }
      })
      .catch((e) => {
        setIsApiCalling(false);
        trackEvents({
          objectName: 'L1 Form',
          actionName: 'Result',
          screen: 'home page',
          properties: {
            status: 'failure',
            errorMessage: e,
          },
          toCleverTap: true,
        });
      });
  };

  const submitL2 = () => {
    setIsApiCalling(true);
    trackEvents({
      objectName: 'L2 Form',
      actionName: 'Submitted',
      screen: 'home page',
      toCleverTap: true,
    });
    const payload = isInstantActivationEnabled
      ? { activation_form_milestone: 'L2' }
      : { submit: 1 };
    postData(payload)
      .then((res) => {
        setIsApiCalling(false);
        if (res) {
          trackEvents({
            objectName: 'L2 Form',
            actionName: 'Result',
            screen: 'home page',
            properties: {
              Status: 'sucess',
            },
            toCleverTap: true,
          });

          const dedupeStatus = checkIfDedupe({ ...res, isInstantActivationEnabled });
          if (res.submitted && dedupeStatus === 'blocked') {
            setModalType('dedupe');
          } else if (!res.business_website && experiments.canGenerateTnCPage) {
            setModalType('tnc');
          } else {
            setModalType('under_review');
            if (isTestMode && res.activated) {
              switchMode(user.current, 'live');
            }
          }
          setIsModalOpen(true);
        }
      })
      .catch((e) => {
        setIsApiCalling(false);
        trackEvents({
          objectName: 'L2 Form',
          actionName: 'Result',
          screen: 'home page',
          properties: {
            status: 'failure',
            errorMessage: e?.errors,
          },
          toCleverTap: true,
        });
      });
  };
  const getNextText = (): NextTextT => {
    if (activeTabId === 'documents') {
      return 'Submit And Verify';
    }
    if (
      isCurrentTabActive &&
      isInstantActivationEnabled &&
      (!isL1Submitted(activation_form_milestone) ||
        (!experiments.isL2AllowedForPoiInitiated
          ? isUnregisteredBusiness(data.business_type) &&
            poi_verification_status === 'initiated' &&
            activation_form_milestone === 'L1' &&
            experiments.isSyncExperimentEnabled
          : false))
    ) {
      return 'Submit KYC';
    }
    return 'Next';
  };
  const handleNextClick = () => {
    trackEvents({
      objectName: 'save and next',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        tab: startCase(activeTabId),
      },
      toCleverTap: true,
    });

    switch (activeTabId) {
      case 'contact_details':
        trackEvents({
          objectName: 'SignUp',
          actionName: 'contact info',
          screen: 'home page',
          eventAction: 'initiated',
        });
        trackEvents({
          objectName: 'SignUp',
          actionName: 'save modifications',
          screen: 'home page',
          eventAction: 'initiated',
          properties: {
            clickSource: 'save-next',
            currentTabName: 'contact details',
          },
        });
        if (isEmailVerificationRequired) {
          if (!isL1Submitted(activation_form_milestone) && isInstantActivationEnabled) {
            submitL1();
          } else {
            setActiveTabId('bank_details');
          }
        } else setActiveTabId('business_overview');
        break;
      case 'business_overview':
        trackEvents({
          objectName: 'SignUp',
          actionName: 'business overview',
          screen: 'home page',
          eventAction: 'initiated',
        });
        trackEvents({
          objectName: 'SignUp',
          actionName: 'save modifications',
          screen: 'home page',
          eventAction: 'initiated',
          properties: {
            clickSource: 'save-next',
            currentTabName: 'business overview',
          },
        });
        setActiveTabId('business_details');
        break;
      case 'business_details':
        trackEvents({
          objectName: 'SignUp',
          actionName: 'business details',
          screen: 'home page',
          eventAction: 'initiated',
        });
        trackEvents({
          objectName: 'SignUp',
          actionName: 'save modifications',
          screen: 'home page',
          eventAction: 'initiated',
          properties: {
            clickSource: 'save-next',
            currentTabName: 'business details',
          },
        });
        if (!isEmailVerificationRequired) {
          if (!isL1Submitted(activation_form_milestone) && isInstantActivationEnabled) {
            submitL1();
          } else {
            setActiveTabId('bank_details');
          }
        } else setActiveTabId('contact_details');
        break;
      case 'bank_details':
        trackEvents({
          objectName: 'SignUp',
          actionName: 'bank details',
          screen: 'home page',
          eventAction: 'initiated',
        });
        trackEvents({
          objectName: 'SignUp',
          actionName: 'save modifications',
          screen: 'home page',
          eventAction: 'initiated',
          properties: {
            clickSource: 'save-next',
            currentTabName: 'bank details',
          },
        });
        setActiveTabId('documents');
        break;
      case 'documents':
        trackEvents({
          objectName: 'SignUp',
          actionName: 'documents',
          screen: 'home page',
          eventAction: 'initiated',
        });
        trackEvents({
          objectName: 'SignUp',
          actionName: 'save modifications',
          screen: 'home page',
          eventAction: 'initiated',
          properties: {
            clickSource: 'save-next',
            currentTabName: 'document',
          },
        });
        submitL2();
        break;
      default:
        break;
    }
  };
  const onBack = () => {
    history.push('/onboarding/steps');
  };

  const isL1AllTabComplete =
    isContactDetailsCompleted && isBusinessOverviewCompleted && isBusinessDetailsCompleted;

  const isAllTabCompleted =
    isL1AllTabComplete && isBankAndCompanyDetailsCompleted && isDocumentsUploadCompleted;

  if (isContactDetailsCompleted) {
    trackEvents({
      objectName: 'SignUp',
      actionName: 'tab filled',
      screen: 'home page',
      eventAction: 'success',
      properties: {
        filed_tab_details: 'Contact Details',
        tab_filled: 'yes',
      },
    });
  }
  if (isBusinessOverviewCompleted) {
    trackEvents({
      objectName: 'SignUp',
      actionName: 'tab filled',
      screen: 'home page',
      eventAction: 'success',
      properties: {
        filed_tab_details: 'Business Overview',
        tab_filled: 'yes',
      },
    });
  }
  if (isBusinessDetailsCompleted) {
    trackEvents({
      objectName: 'SignUp',
      actionName: 'tab filled',
      screen: 'home page',
      eventAction: 'success',
      properties: {
        filed_tab_details: 'Business Details',
        tab_filled: 'yes',
      },
    });
  }
  if (isBankAndCompanyDetailsCompleted) {
    trackEvents({
      objectName: 'SignUp',
      actionName: 'tab filled',
      screen: 'home page',
      eventAction: 'success',
      properties: {
        filed_tab_details: 'Bank Details',
        tab_filled: 'yes',
      },
    });
  }
  if (isDocumentsUploadCompleted) {
    trackEvents({
      objectName: 'SignUp',
      actionName: 'tab filled',
      screen: 'home page',
      eventAction: 'success',
      properties: {
        filed_tab_details: 'Document',
        tab_filled: 'yes',
      },
    });
  }

  const canSubmitActivationForm = (): boolean => {
    if (activeTabId === 'documents') {
      return (
        !can_submit ||
        !isAllTabCompleted ||
        (submitted && (locked || activation_status === 'needs_clarification'))
      );
    } else if (
      isCurrentTabActive &&
      !isL1Submitted(activation_form_milestone) &&
      isInstantActivationEnabled
    ) {
      return (
        !isL1AllTabComplete ||
        (!isL1Acknowledge && experiments.isSyncExperimentEnabled) ||
        isBlackListCategory ||
        (getPoiVerificationStatus(data?.poi_verification_status) &&
          !experiments.canSkipPoiValidation)
      );
    } else {
      return (
        false ||
        (isCurrentTabActive &&
          isInstantActivationEnabled &&
          (!experiments.isL2AllowedForPoiInitiated
            ? isUnregisteredBusiness(data.business_type) &&
              poi_verification_status === 'initiated' &&
              activation_form_milestone === 'L1' &&
              experiments.isSyncExperimentEnabled
            : false))
      );
    }
  };

  const isFormLocked = () => {
    return (
      !!locked ||
      activation_status === 'needs_clarification' ||
      (isUnregisteredBusiness(data.business_type) &&
        activation_form_milestone === 'L1' &&
        poi_verification_status === 'initiated' &&
        !experiments.isL2AllowedForPoiInitiated)
    );
  };

  const hasBankVerificationFailed =
    data?.bank_details_verification_status &&
    !['initiated', 'verified'].includes(data?.bank_details_verification_status) &&
    experiments.isSyncBankVerificationEnabled;

  const getTabs = () => {
    const tabs = [
      <Tab
        key="contact_details"
        title="Contact Details"
        tabId="contact_details"
        completed={isContactDetailsCompleted}
      >
        <ContactDetails isFormLocked={isFormLocked()} />
      </Tab>,
      <Tab
        key="business_overview"
        title="Business Overview"
        tabId="business_overview"
        completed={isBusinessOverviewCompleted}
      >
        <BusinessOverview isFormLocked={isFormLocked()} />
      </Tab>,
      <Tab
        key="business_details"
        title="Business Details"
        tabId="business_details"
        completed={isBusinessDetailsCompleted}
      >
        <BusinessDetails isFormLocked={isFormLocked()} />
      </Tab>,
    ];
    if (
      (!isDedupe &&
        (!isUnregisteredBusiness(data.business_type) ||
          poi_verification_status !== 'initiated' ||
          experiments.isL2AllowedForPoiInitiated) &&
        activation_form_milestone === 'L1') ||
      ((isDedupe || !!submitted) && activation_form_milestone === 'L2') ||
      !isInstantActivationEnabled
    ) {
      tabs.push(
        <Tab
          key="bank_details"
          title="Bank Details"
          tabId="bank_details"
          completed={isBankAndCompanyDetailsCompleted}
          hasError={hasBankVerificationFailed}
        >
          <BankDetails isFormLocked={isFormLocked()} />
        </Tab>,
        <Tab
          key="documents"
          title="Documents"
          tabId="documents"
          completed={isDocumentsUploadCompleted}
        >
          <DocumentUpload isFormLocked={isFormLocked()} />
        </Tab>,
      );
    }

    if (isEmailVerificationRequired) [tabs[0], tabs[1], tabs[2]] = [tabs[1], tabs[2], tabs[0]];

    return tabs;
  };
  return (
    <View>
      {/* Header */}
      <Space padding={[1.75, 1.5, 1.25, 0.75]}>
        <Flex justifyContent="space-between" alignItems="center">
          <StyledHeader>
            <Flex flexDirection="row" justifyContent="left">
              <View>
                <Space padding={[0, 0.5, 0, 0]}>
                  <View data-testid="backIcon" onClick={() => onBack()}>
                    <Icon name="chevronLeft" size="large" fill="shade.800" />
                  </View>
                </Space>
                <View>
                  <Heading size="large">Account Activation</Heading>
                </View>
              </View>
            </Flex>
            <Link
              onClick={() => {
                if (
                  !submitted &&
                  !isDedupe &&
                  (data.poi_verification_status !== 'initiated' ||
                    experiments.isL2AllowedForPoiInitiated)
                ) {
                  setIsSaveAndExitModalOpen(true);
                } else {
                  history.push('/dashboard');
                }

                trackEvents({
                  objectName: 'SignUp',
                  actionName: 'form fill',
                  screen: 'home page',
                  eventAction: 'dropped',
                });
              }}
              size="xsmall"
              weight="bold"
            >
              Save and Exit
            </Link>
          </StyledHeader>
        </Flex>
      </Space>
      {/* Form Tabs */}
      <Space margin={[0, 0, 8.75, 0]}>
        <StyledActivationForm>
          <Tabs
            activeTabId={activeTabId}
            onChange={(tabId) => {
              if (typeof tabId === 'string') {
                setActiveTabId(tabId);
                trackEvents({
                  objectName: 'SignUp',
                  actionName: 'nav action',
                  screen: 'home page',
                  eventAction: 'initiated',
                  properties: {
                    currentTabName: tabId,
                  },
                });

                trackEvents({
                  objectName: 'Activation Tab',
                  actionName: 'Clicked',
                  screen: 'home page',
                  properties: {
                    tab: startCase(tabId),
                  },
                  toCleverTap: true,
                });
              }
            }}
          >
            {getTabs()}
          </Tabs>

          {/* sync experiment */}
          {isCurrentTabActive &&
            !isL1Submitted(activation_form_milestone) &&
            experiments.isSyncExperimentEnabled && (
              <AcknowledgementFooter ref={autoScrollRef}>
                <CheckBoxStyle>
                  <Checkbox
                    name=""
                    title="I agree to Razorpay"
                    disabled={!isL1AllTabComplete}
                    checked={isL1Acknowledge}
                    onChange={(value) => {
                      if (value) {
                        refetch();
                      }
                      setL1Acknowledge(value);
                      trackEvents({
                        objectName: 'sync experiment',
                        actionName: 'checkbox',
                        screen: 'home page',
                        eventAction: 'clicked',
                        isLJReqiuired: false,
                        properties: {
                          checked: value,
                        },
                      });
                    }}
                  />
                </CheckBoxStyle>
                <TnCLink
                  target="_blank"
                  href="https://razorpay.com/terms/"
                  size="small"
                  weight="bold"
                >
                  Terms and Conditions
                </TnCLink>
                <Seprator />
              </AcknowledgementFooter>
            )}
        </StyledActivationForm>
      </Space>

      {/* Footer */}
      <Flex justifyContent="space-between">
        <StyledFooter>
          <Button
            onClick={() => {
              setIsOpen(true);
              trackEvents({
                objectName: 'SignUp',
                actionName: 'faq',
                screen: 'home page',
                eventAction: 'initiated',
              });
            }}
            variant="tertiary"
          >
            FAQs
          </Button>
          {isApiCalling ? (
            <Button type="button" disabled={true}>
              Submitting ...
            </Button>
          ) : (
            <Button
              onClick={() => handleNextClick()}
              disabled={canSubmitActivationForm()}
              icon="chevronRight"
              iconAlign="right"
            >
              {getNextText()}
            </Button>
          )}
        </StyledFooter>
      </Flex>
      <ActivationModal
        isOpen={isModalOpen}
        modalType={modalType}
        closeModal={() => setIsModalOpen(false)}
        dedupeStatus={merchantDedupeStatus}
        activationData={data}
      />
      <SaveAndExitModal
        isOpen={isSaveAndExitModalOpen}
        onClose={() => setIsSaveAndExitModalOpen(false)}
      />
      <FAQs />
    </View>
  );
};

export default withRouter(ActivationForm);
