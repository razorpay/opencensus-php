import React, { useState, useEffect } from 'react';
import { ModalAsideNav } from 'common/new-ui/Wizard';
import { merchantFetch } from 'merchant/utils/ajax';
import ContactDetails from './Components/ContactDetails';
import BusinessDetails from './Components/BusinessDetails';
import Footer from './Components/Footer';
import {
  FOOTER_BUTTONS,
  displayCompanyPAN,
  isUnregisteredBusiness,
  isValidIFSC,
} from './utils/ActivationUtils';
import {
  validateCompanyPAN,
  validateCompanyAB,
  validatePersonalPAN,
} from 'common/utils/validators';
import { isValidGSTIN } from 'common/utils/rzp-utils';

const Activation = () => {
  const [activeTab, setActiveTab] = useState(0);
  const [contactDetails, setContactDetails] = useState({});
  const [businessDetails, setBusinessDetails] = useState({});
  const [loading, setLoading] = useState(true);
  const [formState, setFormState] = useState({});
  const [isSaving, setIsSaving] = useState();
  const [isFormLocked, setIsFormLocked] = useState(false);
  const [isFormSubmitted, setIsFormSubmitted] = useState(false);
  const [canSubmitL1Form, setCanSubmitL1Form] = useState(false);
  const [isContactDetailsValid, setIsContactDetailsValid] = useState(false);
  const [isBusinessDetailsValid, setIsBusinessDetailsValid] = useState(false);
  const [canSubmitFormAPI, setCanSubmitFormAPI] = useState(false);

  const handleTabChange = ({ target }) => {
    const currentTab = Number(target.dataset.index);

    setActiveTab(currentTab);
  };

  const onFormChange = (e) => {
    const { name: field, value } = e.target;
    setFormState((state) => {
      return {
        ...state,
        [field]: value,
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

  const postPartnerActivation = (data) =>
    merchantFetch({ url: 'partner/activation', method: 'POST', data, mode: 'live' });

  const updateActivationState = (data) => {
    setContactDetails({
      contact_name: data.contact_name,
      contact_email: data.contact_email,
      contact_mobile: data.contact_mobile,
    });
    setBusinessDetails({
      business_type: data.business_type,
      contact_name: data.contact_name,
      business_name: data.business_name,
      company_pan: data.company_pan,
      promoter_pan: data.promoter_pan,
      promoter_pan_name: data.promoter_pan_name,
      bank_account_number: data.bank_account_number,
      bank_account_name: data.bank_account_name,
      bank_branch_ifsc: data.bank_branch_ifsc,
      // has_gstin is radio button with foll. options
      // 0th index have gstin
      // 1st index - doesn't have gstin
      has_gstin: data.gstin && data.gstin === '' ? '0' : '1',
      gstin: data.gstin,
    });
    setIsFormLocked(data.partner_activation?.locked);
    setIsFormSubmitted(data.partner_activation?.submitted);
    setCanSubmitFormAPI(data.partner_activation?.can_submit);
  };

  useEffect(() => {
    async function fetchData() {
      const data = await fetchPartnerActivationDetails();
      if (data.success) {
        updateActivationState(data.data);
      }
      setLoading(false);
    }
    fetchData();
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
    const isCompanyPANValid = displayCompanyPAN(currentBusinessType)
      ? companyPAN && !validateCompanyPAN(companyPAN)
      : true;
    const isPromoterPANValid = promoterPan && !validatePersonalPAN(promoterPan);
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
    let isGSTValid;
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
        promoterPANName &&
        isGSTValid &&
        isBankDetailsValid,
    );
  }, [businessDetails, contactDetails, formState]);

  useEffect(() => {
    setCanSubmitL1Form(isContactDetailsValid && isBusinessDetailsValid && canSubmitFormAPI);
  }, [isContactDetailsValid, isBusinessDetailsValid, canSubmitFormAPI]);

  const getFooterButtons = () => {
    const footerButtons = [];
    const isLastTab = activeTab === 1;
    // const isBusinessDetailsStep = activeTab === 1;

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

  const getRequestData = () => {
    let reqData = {};
    if (activeTab === 0) {
      reqData = {
        contact_name: formState.contact_name,
        contact_email: formState.contact_email,
        contact_mobile: formState.contact_mobile,
      };
    } else {
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
      };
    }
    return reqData;
  };

  const saveCurrentTab = async () => {
    setIsSaving(true);
    const reqData = getRequestData();
    const data = await postPartnerActivation(reqData);
    if (data.success) {
      updateActivationState(data.data);
    }
    setIsSaving(false);
  };

  const next = async () => {
    await saveCurrentTab();
    setActiveTab(1);
  };

  const submitForm = async () => {
    const reqData = getRequestData();
    reqData.submit = 1;
    await saveCurrentTab();
    setIsSaving(true);
    const data = await merchantFetch({
      url: 'partner/activation',
      method: 'POST',
      data: reqData,
      mode: 'live',
    });
    if (data.success) {
      updateActivationState(data.data);
    }
    setIsSaving(false);
  };

  return (
    <div className="Activation--wizard Wizard">
      <ModalAsideNav
        title="Partner KYC Form"
        description="Complete and submit the form to accept payments."
        tabs={['Contact Details', 'Business Details']}
        tabClickHandler={handleTabChange}
        tabsValidity={[isContactDetailsValid, isBusinessDetailsValid]}
        activeTab={activeTab}
      />
      <main>
        {loading ? (
          <span className="Loader" />
        ) : (
          <div>
            <RenderActivationFrom
              activeTab={activeTab}
              contactDetails={contactDetails}
              setFormState={setFormState}
              formState={formState}
              businessDetails={businessDetails}
              onFormChange={onFormChange}
              isFormLocked={isFormLocked}
            />
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
      />
    </div>
  );
};

const RenderActivationFrom = (props) => {
  switch (props.activeTab) {
    case 0:
      return <ContactDetails contactDetails={props.contactDetails} {...props} />;
    case 1:
      return <BusinessDetails {...props} />;
    default:
      return <ContactDetails contactDetails={props.contactDetails} {...props} />;
  }
};

export default Activation;
