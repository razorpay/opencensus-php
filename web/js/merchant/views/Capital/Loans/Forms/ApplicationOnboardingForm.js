import React from 'react';
import { NavLink } from 'react-router-dom';
import { connect } from 'react-redux';

import IntentForm from './LosOnboarding/IntentForm';
import BusinessDetailsForm from './LosOnboarding/BusinessDetailsForm';
import PersonalDetailsForm from './LosOnboarding/PersonalDetailsForm';

import { BUSINESS_TYPES } from '../constants';
import {
  saveApplicantDetails,
  saveApplicationDetails,
  saveBusinessDetails,
  getBusinessByMerchantId,
  fetchApplicantDetails,
  getApplications,
} from 'merchant/reducers/capital';
import moment from 'moment';
import { trackCheckEligibilityCta, trackTabChange } from './ga';
import { MERCHANT_OWNER_TYPE } from '../../CashAdvance/constants';

const ApplicationOnboardingForm = (props) => {
  const [view, setView] = React.useState('get_started');
  const [formsData, setFormsData] = React.useState({});
  const [isPending, setIsPending] = React.useState(false);
  const merchantId = props.user.current;

  const fetch = async () => {
    const businessDetails = await props.getBusinessByMerchantId({
      reference_id: props.user.current,
      reference_type: 'MID',
    });
    if (businessDetails && businessDetails.data && businessDetails.data.applicant_ids) {
      await props.fetchApplicantDetails({
        applicant_id: businessDetails.data.applicant_ids[0],
      });
    }
  };

  React.useEffect(() => {
    fetch();
  }, []);

  React.useEffect(() => {
    if (formsData && formsData.personal) startLoanApplication();
  }, [formsData]);

  async function startLoanApplication() {
    setIsPending(true);
    let businessDetails;
    try {
      businessDetails = await saveBusinessDetailsStart();
    } catch (e) {
      // TODO:handle error
      console.log(e);
    }
    await saveApplicantDetailsStart(businessDetails);
    if (businessDetails && businessDetails.data && businessDetails.data.business) {
      await createApplication(businessDetails.data.business.id);
    }
  }

  async function createApplication(businessId) {
    let { intent_credit_amount, credit_request_purpose } = formsData.intent;
    intent_credit_amount = intent_credit_amount.replace(/₹/g, '');
    credit_request_purpose = credit_request_purpose.toString();

    const applicationPayload = {
      owner_id: businessId,
      owner_type: 'BUSINESS',
      product_id: props.productId,
      requested_product_attributes: {
        currency: 'INR',
        interest_rate: 10,
      },
      tnc_consent_attributes: {
        consent_given: true,
        given_at: Date.now(),
      },
      intent_credit_amount,
      credit_request_purpose,
    };
    const response = await props.saveApplicationDetails(applicationPayload);
    const { majority_stakeholder } = formsData.personal;
    if (response.data) {
      const { user, productId, fetchApplications } = props;
      fetchApplications({
        owner_type: MERCHANT_OWNER_TYPE,
        owner_id: user.current,
        product_id: productId,
      });
      trackCheckEligibilityCta(
        merchantId,
        majority_stakeholder,
        'Summary',
        response.data.application.id,
      );
    }
  }

  function saveApplicantDetailsStart(businessDetails) {
    const { loanApplicationDetails } = props;

    const applicantExists = Boolean(
      loanApplicationDetails.promoter_details.data.applicant &&
        loanApplicationDetails.promoter_details.data.applicant.id,
    );
    const applicantDetails = loanApplicationDetails.promoter_details.data.applicant;

    const {
      first_name,
      second_name,
      contact_number,
      contact_email,
      date_of_birth,
      pincode,
      address,
      gender,
      city,
      state,
      pan_number,
      majority_stakeholder,
    } = formsData.personal;

    const existingApplicantData = {
      applicant: {},
      addresses: {},
      phones: {},
      emails: {},
      kyc: {},
    };

    if (applicantExists) {
      existingApplicantData.applicant.id = applicantDetails.id;
      existingApplicantData.addresses.id = applicantDetails.addresses[0].id;
      existingApplicantData.phones.id = applicantDetails.phones[0].id;
      existingApplicantData.emails.id = applicantDetails.emails[0].id;
      existingApplicantData.kyc.kyc_id = applicantDetails.kyc.kyc_id;
    }

    const payload = {
      applicant: {
        ...existingApplicantData.applicant,
        addresses: [
          {
            ...existingApplicantData.addresses,
            address_type: 'ADDRESS_TYPE_RESIDENTIAL',
            address_line1: address,
            address_line2: null,
            city,
            state,
            pincode,
            country: 'India',
            is_primary: true,
          },
        ],
        phones: [
          {
            ...existingApplicantData.phones,
            country_code: '+91',
            phone_number: contact_number,
            is_primary: true,
            // As we are giving an option to modify contact number.
            // we are unaware of its authenticity. So, record it false for now.
            // This will become true, when mobile number is verified
            // through credit pull.
            verified: false,
          },
        ],
        emails: [
          {
            ...existingApplicantData.emails,
            email_id: contact_email,
            is_primary: true,
            verified: true,
          },
        ],
        kyc: {
          ...existingApplicantData.kyc,
          first_name,
          second_name,
          gender,
          date_of_birth: moment(date_of_birth).format('YYYY-MM-DD'),
          pan_number,
          majority_stakeholder,
        },
      },
    };

    return props.saveApplicantDetails({
      business_id: applicantExists
        ? [businessDetails.data.business.id]
        : businessDetails.data.business.id,
      ...payload,
    });
  }

  function saveBusinessDetailsStart() {
    const { business_details } = props.loanApplicationDetails;

    if (business_details.data && business_details.data.business) {
      return updateBusinessDetails(business_details.data.business);
    } else {
      return createBusinessEntity();
    }
  }

  function updateBusinessDetails(data) {
    const { reference_id, id, addresses } = data;
    const {
      address,
      city,
      state,
      pincode,
      gstin,
      business_pan,
      nature,
      date_of_incorporation,
      ownership,
    } = formsData.business;

    const payload = {
      business: {
        id,
        reference_id,
        addresses: [
          {
            id: addresses[0].id,
            address_line1: address,
            address_line2: null,
            city,
            state,
            pincode,
          },
        ],
        gstin,
        business_pan,
        nature,
        date_of_incorporation,
        ownership,
      },
    };
    return props.saveBusinessDetails(payload);
  }

  function createBusinessEntity() {
    const { user } = props;
    const {
      address,
      city,
      state,
      pincode,
      date_of_incorporation,
      nature,
      ownership,
    } = formsData.business;

    const payload = {
      business: {
        reference_id: user.current,
        reference_type: 'MID',
        legal_name: user.business_name,
        date_of_incorporation,
        ownership,
        nature,
        deed_type: BUSINESS_TYPES[parseInt(user.business_type, 10)],
        business_pan: user.company_pan,
        addresses: [
          {
            address_type: 'ADDRESS_TYPE_BUSINESS',
            address_line1: address,
            address_line2: null,
            city,
            state,
            pincode,
            country: user.business_registered_country || 'IN',
            is_primary: true,
          },
        ],
        phones: [
          {
            country_code: '+91',
            phone_number: user.contact_mobile,
            is_primary: true,
            is_verified: user.user.contact_mobile_verified,
          },
        ],
        emails: [
          {
            email_id: user.contact_email,
            is_primary: true,
            verified: true,
          },
        ],
      },
    };
    return props.saveBusinessDetails(payload);
  }

  const handleIntentSubmit = (data) => {
    setFormsData({
      ...formsData,
      intent: data,
    });
    setView('business_details');
  };

  const handleBusinessDetailsSubmit = (data) => {
    setFormsData({
      ...formsData,
      business: data,
    });
    setView('personal_details');
  };

  const handlePersonalDetailsSubmit = (data) => {
    const updatedData = {
      ...formsData,
      personal: data,
    };
    setFormsData(updatedData);
  };

  const getTabClassName = (val) => {
    return `
    ${view === val ? 'active' : ''} ${
      view === 'business_details' && val === 'get_started' ? 'done' : ''
    }
      ${
        view === 'personal_details' && (val === 'get_started' || val === 'business_details')
          ? 'done'
          : ''
      } no-cursor`;
  };

  const handleTabClick = (val) => {
    trackTabChange(merchantId, val, 'Summary');
    if (view === 'business_details' && val === 'personal_details') return setView(val);
    else if ((view === 'personal_details' && val === 'business_details') || val === 'get_started')
      return setView(val);
    return null;
  };

  return (
    <div className="status-overview">
      <div className="loan-application-overview-header onboarding-header flex">
        <div className="loan-meta-wrapper">
          <h4>Let's get started</h4>
          <span className="text-faded">Help us know your current business financing needs.</span>
        </div>
      </div>
      <div className="loan-onboarding-content-body los-content-body">
        <tabbed-container>
          <header className="los-tabs-header">
            <NavLink
              to="#"
              onClick={() => handleTabClick('get_started')}
              className={getTabClassName('get_started')}
            >
              Get Started
            </NavLink>
            <NavLink
              to="#"
              onClick={() => handleTabClick('business_details')}
              className={getTabClassName('business_details')}
            >
              Business Details
            </NavLink>
            <NavLink
              to="#"
              onClick={() => handleTabClick('personal_details')}
              className={getTabClassName('personal_details')}
            >
              Personal Details
            </NavLink>
          </header>
          <content>
            {view === 'get_started' && (
              <IntentForm
                merchantId={merchantId}
                updatedValues={formsData.intent}
                handleIntentSubmit={handleIntentSubmit}
              />
            )}
            {view === 'business_details' && (
              <BusinessDetailsForm
                merchantId={merchantId}
                updatedValues={formsData.business}
                handleBusinessDetailsSubmit={handleBusinessDetailsSubmit}
              />
            )}
            {view === 'personal_details' && (
              <PersonalDetailsForm
                merchantId={merchantId}
                isPending={isPending}
                handlePersonalDetailsSubmit={handlePersonalDetailsSubmit}
              />
            )}
          </content>
        </tabbed-container>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  loanApplicationDetails: state.loanApplicationDetails,
});

export default connect(mapStateToProps, {
  saveApplicantDetails,
  saveApplicationDetails,
  saveBusinessDetails,
  getBusinessByMerchantId,
  fetchApplicantDetails,
  fetchApplications: getApplications,
})(ApplicationOnboardingForm);
