import React, { useState, useEffect, useMemo } from 'react';
import { connect } from 'react-redux';
import { ModalAsideNav } from 'common/new-ui/Wizard';
import { merchantFetch } from 'merchant/utils/ajax';
import ContactDetails from './Components/ContactDetails';
import BusinessDetails from './Components/BusinessDetails';
import AddressDetails, { validateBothAddressSame } from './Components/AddressDetails';
import Footer from './Components/Footer';
import {
  FOOTER_BUTTONS,
  displayCompanyPAN,
  isUnregisteredBusiness,
  isValidIFSC,
  getPincodeDetails,
  getAddressDetailsValidity,
} from './utils/ActivationUtils';
import {
  validateCompanyPAN,
  validateCompanyAB,
  validatePersonalPAN,
} from 'common/utils/validators';
import { isValidGSTIN, checkIsObjectEmpty, classList } from 'common/utils/rzp-utils';
import mainFormTabsContent from 'merchant/components/Activation/ActivationFormMap';
import { getNeedsClarificationTabsData } from './Components/NeedsClarificationsMap';
import useActivation from './Hooks/useActivation';
import NeedsClarification from './Components/NeedsClarifications';
import { showNotification } from 'merchant_common/reducers/notifications';
import { showPartnerKYCStatusModal, hidePartnerKYCStatusModal } from 'merchant/reducers/home';
import Button from 'common/new-ui/Button';
import NoticeMessage from './Components/NoticeMessage';
import { Modal, ModalContent, ModalMask } from 'common/new-ui/Modal';
import { addDropShield, removeDropShield } from 'merchant/components/File/Upload';
import { withRouter } from 'react-router-dom';
import KYCStatusModal from './Components/KYCStatus/KYCStatusModal';
import { compose } from 'redux';
import rTracking from 'react-tracking';
import debounce from 'common/utils/debounce';

const Activation = (props) => {
  const [activeTab, setActiveTab] = useState(0);
  const [contactDetails, setContactDetails] = useState({});
  const [businessDetails, setBusinessDetails] = useState({});
  const [addressDetails, setAddressDetails] = useState({});
  const [loading, setLoading] = useState(true);
  const [isConsentTNC, setIsConsentTNC] = useState(false);
  const [formState, setFormState] = useState({});
  const [isSaving, setIsSaving] = useState();
  const [isFormLocked, setIsFormLocked] = useState(false);
  const [isFormSubmitted, setIsFormSubmitted] = useState(false);
  const [canSubmitL1Form, setCanSubmitL1Form] = useState(false);
  const [isContactDetailsValid, setIsContactDetailsValid] = useState(false);
  const [isBusinessDetailsValid, setIsBusinessDetailsValid] = useState(false);
  const [isAddressDetailsValid, setIsAddressDetailsValid] = useState(false);
  const [canSubmitFormAPI, setCanSubmitFormAPI] = useState(false);
  const [NCFields, setNCFields] = useState();
  const { data } = useActivation();
  const [commentlist, setCommentlist] = useState([]);
  const [tabs, setTabs] = useState(['Contact Details', 'Business Details', 'Address Details']);
  const currentTabsValidity = useMemo(() => {
    return [isContactDetailsValid, isBusinessDetailsValid, isAddressDetailsValid];
  }, [isContactDetailsValid, isBusinessDetailsValid, isAddressDetailsValid]);
  const [ncFormResponse, setNCFormResponse] = useState({
    bank_proof: 'cancelled_cheque',
  });

  const handleTabChange = ({ target }) => {
    const currentTab = Number(target.dataset.index);
    setActiveTab(currentTab);
  };

  const convertToBoolean = (value) => {
    if (value && typeof value === 'string') {
      if (value.toLowerCase() === 'true') return true;
      if (value.toLowerCase() === 'false') return false;
    }
    return value;
  };

  const onFormChange = (e) => {
    const { name: field, value } = e.target;
    let updatedValue = value;

    // since the value we get from event is a string this check converts it into boolean
    if (field === 'isOpAddressSameAsRegAddress') updatedValue = convertToBoolean(value);
    setFormState((state) => {
      return {
        ...state,
        [field]: updatedValue,
      };
    });
  };

  const fetchPartnerActivationDetails = async () => {
    const response = await merchantFetch({
      url: 'partner/activation',
      mode: 'live',
      method: 'GET',
    });
    return response;
  };

  const postPartnerActivation = (postData) =>
    merchantFetch({ url: 'partner/activation', method: 'POST', data: postData, mode: 'live' });

  const updateActivationState = (activationData) => {
    setContactDetails({
      contact_name: activationData.contact_name,
      contact_email: activationData.contact_email,
      contact_mobile: activationData.contact_mobile,
    });
    setBusinessDetails({
      business_type: activationData.business_type,
      contact_name: activationData.contact_name,
      business_name: activationData.business_name,
      company_pan: activationData.company_pan,
      promoter_pan: activationData.promoter_pan,
      promoter_pan_name: activationData.promoter_pan_name,
      bank_account_number: activationData.bank_account_number,
      bank_account_name: activationData.bank_account_name,
      bank_branch_ifsc: activationData.bank_branch_ifsc,
      // has_gstin is radio button with foll. options
      // 0th index have gstin
      // 1st index - doesn't have gstin
      has_gstin: activationData.gstin && activationData.gstin !== '' ? '0' : '1',
      gstin: activationData.gstin,
    });

    const addressDetailsData = {
      business_registered_address: activationData.business_registered_address,
      business_registered_pin: activationData.business_registered_pin,
      business_registered_city: activationData.business_registered_city,
      business_registered_state: activationData.business_registered_state,
      business_operation_address: activationData.business_operation_address,
      business_operation_pin: activationData.business_operation_pin,
      business_operation_city: activationData.business_operation_city,
      business_operation_state: activationData.business_operation_state,
    };
    setAddressDetails(addressDetailsData);
    setFormState((state) => {
      return {
        ...state,
        isOpAddressSameAsRegAddress: validateBothAddressSame(addressDetailsData),
      };
    });

    setIsFormLocked(
      activationData.partner_activation?.locked ||
        ['needs_clarification', 'under_review', 'kyc_qualified_unactivated'].includes(
          activationData.partner_activation?.activation_status,
        ),
    );
    setIsFormSubmitted(activationData.partner_activation?.submitted);
    setCanSubmitFormAPI(activationData.partner_activation?.can_submit);
  };

  const autoFillFromPinCode = debounce((e) => {
    const { name: field, value } = e.target;
    getPincodeDetails(value).then((data) => {
      const cityField = `${field.slice(0, -3)}city`;
      const stateField = `${field.slice(0, -3)}state`;
      setFormState((state) => {
        return {
          ...state,
          [cityField]: data.city,
          [stateField]: data.state_code,
        };
      });
    });
  }, 1500);

  useEffect(() => {
    async function fetchData() {
      const activationData = await fetchPartnerActivationDetails();
      if (activationData.success) {
        updateActivationState(activationData.data);

        if (
          activationData.data.activation_status === 'needs_clarification' &&
          !activationData.partner_activation?.submitted
        ) {
          props.showPartnerKYCStatusModal({
            modalType: 'PARTNER_KYC_BLOCKED_MODAL',
          });
        }

        if (activationData.data.partner_activation?.activation_status === 'needs_clarification') {
          const clarification_reasons = await merchantFetch(
            'merchant/activation/clarification_reasons',
          );
          const clarificationReasons = clarification_reasons && clarification_reasons.data;
          const ndcFields =
            getNeedsClarificationTabsData(
              mainFormTabsContent,
              activationData.data.partner_activation,
              clarificationReasons,
            ) || [];
          setNCFields(ndcFields);
          setTabs((currentTabs) => [...currentTabs, 'Needs Clarification']);
          setActiveTab(3);
        }
      }
      setLoading(false);
    }
    fetchData();
    addDropShield('.Activation--wizard');
    return () => {
      removeDropShield('.Activation--wizard');
    };
  }, []);

  useEffect(() => {
    const contactName = formState.contact_name || contactDetails.contact_name;
    const contactEmail = formState.contact_email || contactDetails.contact_email;
    const contactMobile = formState.contact_mobile || contactDetails.contact_mobile;
    setIsContactDetailsValid(contactName && contactEmail && contactMobile);
  }, [contactDetails, formState]);

  useEffect(() => {
    const contactName = contactDetails.contact_name;
    const contactEmail = contactDetails.contact_email;
    const contactMobile = contactDetails.contact_email;
    setIsContactDetailsValid(contactName && contactEmail && contactMobile);
    const promoterPan = formState.promoter_pan || businessDetails.promoter_pan;
    const businessName = formState.business_name || businessDetails.business_name;
    const companyPAN = formState.company_pan || businessDetails.company_pan;
    const promoterPANName = formState.promoter_pan_name || businessDetails.promoter_pan_name;
    const currentBusinessType = formState.business_type || businessDetails.business_type;
    const isUnregistered = isUnregisteredBusiness(currentBusinessType);
    let isCompanyPANValid, isPromoterPANValid, isPromoterPANNameValid, isGSTValid;
    if (displayCompanyPAN(currentBusinessType)) {
      // company pan shown
      if (companyPAN) {
        const validationError = validateCompanyPAN(companyPAN);
        isCompanyPANValid = !validationError;
      }
      isPromoterPANValid = true; // skip validation as field hidden
      isPromoterPANNameValid = true; // skip validation as field hidden
    } else {
      // company pan hidden
      isCompanyPANValid = true; // skip validation as field hidden
      if (promoterPan) {
        const validationError = validatePersonalPAN(promoterPan);
        isPromoterPANValid = !validationError;
      }
      isPromoterPANNameValid = promoterPANName;
    }
    const isCompanyNameValid = isUnregistered
      ? true
      : !validateCompanyAB(businessName, contactName, true);
    const bankAccountNumber = formState.bank_account_number || businessDetails.bank_account_number;
    const accountNo = formState.account_no || businessDetails.bank_account_number;
    const isAccountNoMatching = bankAccountNumber == accountNo;
    const ifscNo = formState.bank_branch_ifsc || businessDetails.bank_branch_ifsc;
    const isIFSCValid = isValidIFSC(ifscNo);
    const gstin = formState.gstin || businessDetails.gstin;
    const isGSTFilled = gstin && gstin !== '';
    const benificiaryName = formState.bank_account_name || businessDetails.bank_account_name;
    const isBankDetailsValid =
      isIFSCValid && bankAccountNumber && isAccountNoMatching && benificiaryName;
    if (isUnregistered) {
      isGSTValid = true;
    } else if (isGSTFilled) {
      isGSTValid = isValidGSTIN(gstin);
    } else {
      isGSTValid = true;
    }

    setIsBusinessDetailsValid(
      isPromoterPANValid &&
        isCompanyPANValid &&
        isCompanyNameValid &&
        isPromoterPANNameValid &&
        isGSTValid &&
        isBankDetailsValid,
    );

    // update address details

    const addressDetailsValidity = getAddressDetailsValidity(addressDetails, formState);
    let isAddressDetailsValid = false;
    if (
      formState.isOpAddressSameAsRegAddress === 1 ||
      addressDetails.isOpAddressSameAsRegAddress === 1
    )
      isAddressDetailsValid = addressDetailsValidity.isRegisteredAddressValid;
    else
      isAddressDetailsValid =
        addressDetailsValidity.isRegisteredAddressValid &&
        addressDetailsValidity.isOperationalAddressValid;
    setIsAddressDetailsValid(isAddressDetailsValid);
  }, [businessDetails, contactDetails, addressDetails, formState]);

  useEffect(() => {
    setCanSubmitL1Form(
      isContactDetailsValid && isBusinessDetailsValid && isAddressDetailsValid && canSubmitFormAPI,
    );
  }, [
    isContactDetailsValid,
    isBusinessDetailsValid,
    isAddressDetailsValid,
    canSubmitFormAPI,
    isConsentTNC,
  ]);

  const getFooterButtons = () => {
    const footerButtons = [];
    const isLastTab = activeTab === 2;

    if (activeTab === 3) {
      footerButtons.push(FOOTER_BUTTONS.SUBMIT_CLARIFICATIONS);
      return footerButtons;
    }

    if (isFormLocked || isFormSubmitted) {
      return [];
    }

    footerButtons.push(FOOTER_BUTTONS.SAVE);
    if (!isLastTab) {
      footerButtons.push(FOOTER_BUTTONS.SAVE_AND_NEXT);
    } else {
      footerButtons.push(FOOTER_BUTTONS.SUBMIT_L1_FORM);
    }

    return footerButtons;
  };

  const getAddressDetailsData = (isOpAddressSameAsRegAddress) => {
    if (isOpAddressSameAsRegAddress)
      return {
        business_registered_address: formState.business_registered_address,
        business_registered_pin: formState.business_registered_pin,
        business_registered_city: formState.business_registered_city,
        business_registered_state: formState.business_registered_state,
        business_operation_address: formState.business_registered_address,
        business_operation_pin: formState.business_registered_pin,
        business_operation_city: formState.business_registered_city,
        business_operation_state: formState.business_registered_state,
      };
    else
      return {
        business_registered_address: formState.business_registered_address,
        business_registered_pin: formState.business_registered_pin,
        business_registered_city: formState.business_registered_city,
        business_registered_state: formState.business_registered_state,
        business_operation_address: formState.business_operation_address,
        business_operation_pin: formState.business_operation_pin,
        business_operation_city: formState.business_operation_city,
        business_operation_state: formState.business_operation_state,
      };
  };

  const getRequestData = () => {
    let reqData = {};
    if (activeTab === 0) {
      reqData = {
        contact_name: formState.contact_name,
        contact_email: formState.contact_email,
        contact_mobile: formState.contact_mobile,
      };
    } else if (activeTab === 1) {
      reqData = {
        business_type: formState.business_type,
        contact_name: formState.contact_name,
        business_name: formState.business_name,
        company_pan: formState.company_pan,
        promoter_pan: formState.promoter_pan,
        promoter_pan_name: formState.promoter_pan_name,
        bank_account_number: formState.bank_account_number,
        bank_branch_ifsc: formState.bank_branch_ifsc,
        bank_account_name: formState.bank_account_name,
        gstin: formState.gstin,
      };
    } else if (activeTab === 2) {
      if (String(formState.isOpAddressSameAsRegAddress) === 'true')
        reqData = getAddressDetailsData(true);
      else reqData = getAddressDetailsData(false);
    }
    return reqData;
  };

  const saveCurrentTab = async () => {
    setIsSaving(true);
    const reqData = getRequestData();
    const activationData = await postPartnerActivation(reqData);
    if (activationData.success) {
      updateActivationState(activationData.data);
    }
    setIsSaving(false);
    props.tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner_KYC.save', {
        partnerID: props.user?.merchant.id,
        section: tabs[activeTab],
      }),
    );
  };

  const next = async () => {
    await saveCurrentTab();
    setActiveTab(activeTab + 1);
  };

  const submitForm = async () => {
    const reqData = getRequestData();
    reqData.submit = '1';
    reqData.consent = isConsentTNC;
    reqData.documents_detail = [
      {
        type: 'Privacy Policy',
        url: 'https://razorpay.com/privacy/',
      },
      {
        type: 'Service Agreement',
        url: 'https://razorpay.com/agreement/',
      },
      {
        type: 'Terms & Conditions',
        url: 'https://razorpay.com/terms/',
      },
    ];
    await saveCurrentTab();
    setIsSaving(true);
    const activationData = await merchantFetch({
      url: 'partner/activation',
      method: 'POST',
      data: reqData,
      mode: 'live',
    });
    if (activationData.success) {
      updateActivationState(activationData.data);
    }
    props.tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner_KYC.submit&verify', {
        partnerID: props.user?.merchant.id,
      }),
    );
    setIsSaving(false);
  };

  useEffect(() => {
    props.tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner_KYC.form_open', {
        partnerID: props.user?.merchant.id,
        section: tabs[activeTab],
      }),
    );
  }, [activeTab]);

  const isOnKYCTab = () => {
    return activeTab === 3;
  };

  const hasFilledClarificationDetails = () => {
    if (isOnKYCTab()) {
      const dynamicFieldName = {
        bank_proof_doc: () => {
          return ncFormResponse.bank_proof;
        },
      };
      const hasFilledEverything = NCFields.every((field) => {
        let fieldName = field.name || field?._name;
        if (dynamicFieldName[fieldName]) {
          fieldName = dynamicFieldName[fieldName]();
        }

        return Boolean(
          ncFormResponse[fieldName] ||
            (commentlist.hasOwnProperty(fieldName) && commentlist[fieldName] !== ''),
        );
      });

      return hasFilledEverything;
    }
    return false;
  };

  const submitClarifications = async () => {
    const reqData = {};

    if (hasFilledClarificationDetails()) {
      const fieldNames = NCFields.map((field) => field.name);
      fieldNames.forEach((fieldName) => {
        if (ncFormResponse[fieldName]) {
          reqData[fieldName] = ncFormResponse[fieldName];
        }
      });
    }

    if (!checkIsObjectEmpty(commentlist)) {
      reqData.kyc_clarification_reasons = {
        clarification_reasons: {},
      };
    }

    for (const prop in commentlist) {
      if (commentlist.hasOwnProperty(prop) && commentlist[prop] !== '') {
        reqData.kyc_clarification_reasons.clarification_reasons[prop] = [
          {
            reason_type: 'custom',
            reason_code: commentlist[prop],
          },
        ];
      }
    }

    // State will contain file fields which have already been uploaded
    // Delete file field from request data
    Object.keys(reqData).forEach((key) => {
      if (reqData[key] === 'fakepath') {
        delete reqData[key];
      }
    });

    props.tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner_KYC.save', {
        partnerID: props.user?.merchant.id,
        section: tabs[activeTab],
      }),
    );

    try {
      setIsSaving(true);
      // save updated fields
      await postPartnerActivation(reqData);

      // submit the form
      const response = await postPartnerActivation({
        submit: '1',
      });

      if (response.success) {
        props.showPartnerKYCStatusModal({
          modalType: 'KYC_CLARIFICATION_SUBMIT_MODAL',
          activationDuration: '3 days',
        });
      }
      return response;
    } catch (err) {
      if (err.errors && err.errors.length && err.errors[0]) {
        props.showNotification({
          type: 'error',
          message: err.errors,
        });
      }
      return err;
    } finally {
      setIsSaving(false);
    }
  };

  const prevTab = async () => {
    await saveCurrentTab();
    setActiveTab((tab) => tab - 1);
  };

  return (
    <ModalMask>
      {props.showKYCStatus && (
        <KYCStatusModal
          onClose={() => {
            props.hidePartnerKYCStatusModal();
          }}
          onGoToDashboard={() => {
            props.history.push('/partners');
          }}
          activationStatus={data?.partner_activation?.activation_status}
          modalType={props.kycStatusModalType}
          activationDuration={props.kycStatusActivationDuration}
        />
      )}
      <div>
        <Modal
          class="animate-down Activation--wizard"
          onClose={() => {
            props.history.push('/partners');
          }}
          onCloseCB={() => {}}
        >
          <ModalContent>
            <div className="Activation--wizard Wizard">
              <ModalAsideNav
                title="Partner KYC Form"
                description={
                  !isFormSubmitted && (
                    <p>Complete and submit the form to start getting commissions.</p>
                  )
                }
                tabs={tabs}
                tabClickHandler={handleTabChange}
                tabsValidity={currentTabsValidity}
                activeTab={activeTab}
              />
              <main>
                {loading ? (
                  <span className="Loader" />
                ) : (
                  <div>
                    <main-title class="main-title">
                      {activeTab != 0 && (
                        <Button
                          class="device--mobile btn--back"
                          iconBefore="arrow-back"
                          onClick={prevTab}
                        />
                      )}
                      <span
                        className={classList(
                          'device--mobile main-title-icon',
                          currentTabsValidity[activeTab] && 'text-success ',
                        )}
                      >
                        <i
                          className={classList(
                            'i-check',
                            currentTabsValidity[activeTab] && 'drishy',
                          )}
                        />
                        {tabs[activeTab]}
                      </span>

                      <span className="device--desktop">
                        {tabs[activeTab]}
                        {tabs[activeTab] === 'Documents Verification' && (
                          <div className="onboarding-tab-subtitle">
                            {isUnregisteredBusiness
                              ? ''
                              : 'You can upload JPG/PNG of max. size 4MB or PDF of max. size 2 MB'}
                          </div>
                        )}
                      </span>
                    </main-title>
                    <NoticeMessage
                      isFormLocked={isFormLocked}
                      isFormSubmitted={isFormSubmitted}
                      activeTab={activeTab}
                      activationStatus={data?.partner_activation?.activation_status}
                      isOnKYCTab={isOnKYCTab}
                    />
                    {
                      <RenderActivationFrom
                        activeTab={activeTab}
                        contactDetails={contactDetails}
                        setFormState={setFormState}
                        formState={formState}
                        businessDetails={businessDetails}
                        addressDetails={addressDetails}
                        onFormChange={onFormChange}
                        autoFillFromPinCode={autoFillFromPinCode}
                        isFormLocked={isFormLocked}
                        data={data}
                        NCFields={NCFields}
                        commentlist={commentlist}
                        setCommentlist={setCommentlist}
                        ncFormResponse={ncFormResponse}
                        setNCFormResponse={setNCFormResponse}
                        commonLockedFields={data?.lock_common_fields}
                      />
                    }
                  </div>
                )}
              </main>
              <Footer
                isSaving={isSaving}
                footerButtons={getFooterButtons()}
                canSubmitL1Form={canSubmitL1Form}
                submitL1={submitForm}
                next={next}
                saveCurrentTab={saveCurrentTab}
                canSubmitNeedsClarification={hasFilledClarificationDetails}
                submitClarifications={submitClarifications}
                isConsentTNC={isConsentTNC}
                setIsConsentTNC={setIsConsentTNC}
                activeTab={activeTab}
                isFormSubmitted={isFormSubmitted}
              />
            </div>
          </ModalContent>
        </Modal>
      </div>
    </ModalMask>
  );
};

const RenderActivationFrom = (props) => {
  switch (props.activeTab) {
    case 0:
      return <ContactDetails contactDetails={props.contactDetails} {...props} />;
    case 1:
      return <BusinessDetails {...props} />;
    case 2:
      return <AddressDetails {...props} />;
    case 3:
      return <NeedsClarification {...props} />;
    default:
      return <ContactDetails contactDetails={props.contactDetails} {...props} />;
  }
};

export default compose(
  withRouter,
  connect(
    (state) => {
      return {
        showKYCStatus: state.home.partnerActivations.showKYCStatus,
        kycStatusModalType: state.home.partnerActivations.kycStatusModalType,
        kycStatusActivationDuration: state.home.partnerActivations.kycStatusActivationDuration,
        user: state.session.user,
      };
    },
    { showNotification, showPartnerKYCStatusModal, hidePartnerKYCStatusModal },
  ),
  rTracking(() => window.rzpQ.component('PartnerKYCFormDesktop')),
)(Activation);
