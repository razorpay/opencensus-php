import React from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import IntentForm from '../PromoterDetails/IntentForm';
import BusinessDetailsForm from '../PromoterDetails/BusinessDetailsForm';
import PersonalDetailsForm from '../PromoterDetails/PersonalDetailsForm';
import { isPreceedingState } from '../../../utils';
import {
  saveApplicantDetails as saveApplicantDetailsAction,
  saveBusinessDetails as saveBusinessDetailsAction,
} from 'merchant/reducers/capital';
import { APPLICATION_STATES } from '../../constants';
import { trackTabChange } from '../ga';

const PromoterDetailsEntity = ({
  user,
  loanApplicationDetails,
  navigation,
  saveBusinessDetails,
  saveApplicantDetails,
}) => {
  const [view, setView] = React.useState('personal_details');
  const [formsData, setFormsData] = React.useState({
    business: null,
    personal: null,
  });
  const [isPending, setIsPending] = React.useState(false);
  const merchantId = user.current;

  const { bureau_report_details } = loanApplicationDetails;
  const bureauReportExists =
    !bureau_report_details.loading && !!bureau_report_details.data.bureau_report;

  const canModify =
    isPreceedingState(
      loanApplicationDetails.meta.data.application.status,
      APPLICATION_STATES.CREDIT_PULL_PENDING,
    ) && !bureauReportExists;

  React.useEffect(() => {
    if (formsData && formsData.personal) {
      if (canModify) {
        updateLoanApplication();
      } else {
        navigation.next({ from: 'next' });
      }
    }
  }, [formsData]);

  async function updateLoanApplication() {
    setIsPending(true);
    let businessDetails;
    try {
      businessDetails = await saveBusinessDetailsStart();
    } catch (e) {
      // TODO:handle error
      console.log(e);
    }
    await saveApplicantDetailsStart(businessDetails);
    navigation.next({ from: 'next' });
  }

  function saveApplicantDetailsStart(businessDetails) {
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

    return saveApplicantDetails({
      business_id: applicantExists
        ? [businessDetails.data.business.id]
        : businessDetails.data.business.id,
      ...payload,
    });
  }

  function saveBusinessDetailsStart() {
    const { business_details } = loanApplicationDetails;
    if (formsData.business) {
      if (business_details.data && business_details.data.business) {
        return updateBusinessDetails(business_details.data.business);
      }
    }
    return Promise.resolve({
      data: business_details.data,
    });
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
        date_of_incorporation,
        nature,
        ownership,
      },
    };
    return saveBusinessDetails(payload);
  }

  const handleIntentSubmit = () => setView('business_details');

  const handleBusinessSubmit = (data) => {
    setFormsData({
      ...formsData,
      business: data,
    });
    setView('personal_details');
  };

  const handlePersonalSubmit = (data) => {
    if (data) {
      setFormsData({
        ...formsData,
        personal: data,
      });
    }
  };

  const getTabClassName = (val) => {
    return `${view === val ? 'active' : ''} done`;
  };

  const handleTabClick = (val) => {
    trackTabChange(merchantId, val, 'Summary');
    setView(val);
  };

  return (
    <div className="status-overview">
      <div className="loan-onboarding-content-body los-content-body promoter-content-body">
        <tabbed-container>
          <header className="los-tabs-header">
            <NavLink
              to="#"
              className={getTabClassName('get_started')}
              onClick={() => handleTabClick('get_started')}
            >
              Get Started
            </NavLink>
            <NavLink
              to="#"
              className={getTabClassName('business_details')}
              onClick={() => handleTabClick('business_details')}
            >
              Business Details
            </NavLink>
            <NavLink
              to="#"
              className={getTabClassName('personal_details')}
              onClick={() => handleTabClick('personal_details')}
            >
              Personal Details
            </NavLink>
          </header>
          <content className="promoter-details">
            {view === 'get_started' && (
              <IntentForm merchantId={merchantId} handleIntentSubmit={handleIntentSubmit} />
            )}
            {view === 'business_details' && (
              <BusinessDetailsForm
                canModify={canModify}
                merchantId={merchantId}
                updatedValues={formsData.business}
                handleBusinessSubmit={handleBusinessSubmit}
              />
            )}
            {view === 'personal_details' && (
              <PersonalDetailsForm
                canModify={canModify}
                merchantId={merchantId}
                isPending={isPending}
                handlePersonalSubmit={handlePersonalSubmit}
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
  saveBusinessDetails: saveBusinessDetailsAction,
  saveApplicantDetails: saveApplicantDetailsAction,
})(PromoterDetailsEntity);
