import React, { Component } from 'react';
import BusinessInfoEntity from './Forms/BusinessInfoEntity';
import Banner from './components/Banner';
import { connect } from 'react-redux';
import {
  fetchLoanApplicationMeta,
  fetchBusinessDetails,
  fetchApplicantDetails,
  fetchDocumentGroups,
  fetchCreditOffers,
  getAcceptedOffer,
  getAgreementStatus,
  getNach,
  fetchD2cReport,
  changeActiveState,
  fetchProducts,
  getBusinessByMerchantId,
  getLenderDetails,
  getDisbursalDetails,
  getScheduleDetails,
  getOfferVerificationTasks,
} from 'merchant/reducers/capital';
import PromoterDetailsEntity from './Forms/PromoterDetailsEntity';
import MobileVerification from './Forms/MobileVerification';
import CreditScoreBreakdown from './Forms/CreditScoreBreakdown';
import LoanStatusBanner from './LoanStatusBanner';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import PreVerificationUpload from './Forms/PreVerificationUpload';
import CreditOfferEntity from './Forms/CreditOfferEntity';
import ContractEntity from './Forms/ContractEntity';
import NachEntity from './Forms/NachEntity';
import VerificationSlotSelection from './Forms/VerificationSlotSelection';
import LoanApproved from './Forms/LoanApproved';
import FormSectionLoadingSkeleton from './components/FormSectionLoadingSkeleton';
import { isPreceedingState } from './utils';
import { APPLICATION_STATES } from './constants';
import DocumentCollectionInformation from './Forms/DocumentCollectionInformation';
import DisbursalEntity from './Forms/DisbursalEntity';
import PendingState from './Forms/PendingState';

const StateMessageMap = {
  [APPLICATION_STATES.SCORE_GENERATION_PENDING]: (
    <span>
      The process takes around 24 hours. We will update you once all the
      parameters are verified and revert with the offer status.
    </span>
  ),
  CONTRACT_GENERATION_PENDING: (
    <span>
      The loan agreement will contain the commercials around the offer and the
      collection process. The process takes around 10-15 mins. Razorpay will
      update you once the agreement is ready to be signed through the mail.
    </span>
  ),
  [APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED]: (
    <span>
      Your loan application has been rejected due to the repeated failure of the
      document collection.
    </span>
  ),
  [APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW]: (
    <span>
      We usually confirm the document review within 1-2 working days. We’ll let
      you know once the Review is completed.
    </span>
  ),
};

const stateFormMap = {
  BUSINESS_INFO_PENDING: BusinessInfoEntity,
  PROMOTER_INFO_PENDING: PromoterDetailsEntity,
  MOBILE_VERIFICATION_PENDING: MobileVerification,
  CREDIT_PULL_COMPLETED: CreditScoreBreakdown,
  [APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING]: PreVerificationUpload,
  [APPLICATION_STATES.PREVERIFICATION_FAILED]: PreVerificationUpload,
  [APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS]: PreVerificationUpload,
  [APPLICATION_STATES.SCORE_GENERATION_PENDING]: () => (
    <PendingState
      message={StateMessageMap[APPLICATION_STATES.SCORE_GENERATION_PENDING]}
      showNavigation
      backState={APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING}
      nextState={APPLICATION_STATES.CREDIT_OFFER_GENERATED}
    />
  ),
  [APPLICATION_STATES.CREDIT_OFFER_GENERATED]: CreditOfferEntity,
  CONTRACT_GENERATION_PENDING: () => (
    <PendingState
      message={StateMessageMap['CONTRACT_GENERATION_PENDING']}
      showNavigation
      backState={APPLICATION_STATES.CREDIT_OFFER_GENERATED}
      nextState={APPLICATION_STATES.NACH_CREATION_PENDING}
    />
  ),
  [APPLICATION_STATES.CONTRACT_PENDING]: ContractEntity,
  CONTRACT_SIGNED: ContractEntity,
  [APPLICATION_STATES.NACH_UPLOAD_PENDING]: NachEntity,
  [APPLICATION_STATES.SLOT_SELECTION_PENDING]: VerificationSlotSelection,
  [APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED]: DocumentCollectionInformation,
  [APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED]: () => (
    <PendingState
      message={StateMessageMap[APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED]}
      showNavigation
      backState={APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED}
      nextState={APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW}
    />
  ),
  [APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW]: () => (
    <PendingState
      message={StateMessageMap[APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW]}
      showNavigation
      backState={APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED}
      nextState={APPLICATION_STATES.RZP_APPROVED}
    />
  ),
  [APPLICATION_STATES.RZP_APPROVED]: LoanApproved,
  [APPLICATION_STATES.CREDIT_DISBURSED]: DisbursalEntity,
};

const stateTitleMap = {
  BUSINESS_INFO_PENDING: {
    title: 'Confirm your Business Details & Needs',
    description: 'Please enter your business details here',
  },
  BUSINESS_INFO_PENDING_LOCKED: {
    title: 'Confirm your Business Details & Needs',
    description:
      'Sorry, Loan details cannot be modified after the loan' +
      ' offer is accepted',
    lockedNote: true,
  },
  PROMOTER_INFO_PENDING: {
    title: 'Confirm your Individual info',
    description:
      'We verify the details with the central PAN database. Please ensure to enter the correct Authorised Signatory’s details',
  },
  PROMOTER_INFO_PENDING_LOCKED: {
    title: 'Confirm your Individual info',
    description:
      'Sorry, You cannot edit the below information after Credit Enquiry is done',
    lockedNote: true,
  },
  MOBILE_VERIFICATION_PENDING: {
    title: 'OTP Verification for Credit Inquiry',
    description:
      'We will do a credit bureau pull based on your phone number and PAN to evaluate your credit score',
  },
  CREDIT_PULL_COMPLETED: {
    title: 'Credit Inquiry Report',
    description: 'This credit inquiry will not impact your credit score',
  },
  [APPLICATION_STATES.SCORE_GENERATION_PENDING]: {
    title: 'Evaluating Loan Offer',
    description:
      'We will now review your documents and calculate the loan offer.',
    type: 'pending',
  },
  [APPLICATION_STATES.CREDIT_OFFER_PENDING]: {
    title: 'Evaluating Loan Offer',
    description:
      'We will now review your documents and calculate the loan offer.',
    type: 'pending',
  },
  [APPLICATION_STATES.CREDIT_OFFER_GENERATED]: {
    title: 'Loan Offer',
    description:
      'Accept the following loan offer to get the loan amount disbursed to your account',
  },
  CREDIT_OFFER_ACCEPTED: {
    title: 'Accepted Loan offer',
    description:
      'Check the accepted loan offer details with repayment details here.',
    type: 'success',
  },
  CONTRACT_GENERATION_PENDING: {
    title: 'Loan Agreement is getting generated...',
    description:
      'We are generating the loan agreement with your loan offer details. Please wait for some moment.',
    type: 'pending',
  },
  [APPLICATION_STATES.CONTRACT_PENDING]: {
    title: 'E-Sign Loan Agreement',
    description:
      'Please E-Sign the loan agreement by going to the leegality page.',
  },
  CONTRACT_SIGNED: {
    title: 'Loan Agreement',
    description:
      'Your loan agreement has been signed successfully. Check the loan agreement details below.',
    type: 'success',
  },
  [APPLICATION_STATES.NACH_UPLOAD_PENDING]: {
    title: 'Download & Submit a NACH form',
    description:
      "Why NACH? In case, there is a deficit in the collections flow, Razorpay holds the right to trigger the NACH to auto-debit the pending amount from the merchant's bank account.",
  },
  [APPLICATION_STATES.SLOT_SELECTION_PENDING]: {
    title: 'Schedule an appointment for document collection',
    description:
      'Why? This is mandatory as the physical documents will be verified by the lender for processing the application and approving the final disbursal.',
  },
  [APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED]: {
    title: 'Document Collection',
    description:
      'Please be ready with the original documents along with a xerox copies. Our executive will be verifying the xerox copies with the original documents.',
  },
  [APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED]: {
    title: 'Document Collection Failed',
    description:
      'Oops! It seems like we have not been able to collect your documents.',
    type: 'error',
  },
  [APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW]: {
    title: 'Documents Under Review',
    description:
      'We’re reviewing your documents internally and with our vendor.',
    type: 'pending',
  },
  [APPLICATION_STATES.RZP_APPROVED]: {
    title: 'Congratulations, Your loan has been approved!',
    description:
      'On a successful authorization, you will receive the' +
      ' following loan amount in your bank account',
    type: 'success',
  },
  [APPLICATION_STATES.CREDIT_DISBURSED]: {
    title: 'Hurray! Disbursed Successfully',
    description:
      'The following loan amount has been successfully disbursed' +
      ' to your bank account.',
    type: 'success',
  },
};

const getTitleInformation = info => {
  return <Banner {...info} isFormHeader={true} />;
};

@connect(
  state => ({
    user: state.session.user,
  }),
  {
    fetchLoanApplicationMeta,
    fetchBusinessDetails,
    fetchApplicantDetails,
    fetchD2cReport,
    ...NotificationsActions,
    fetchDocumentGroups,
    fetchCreditOffers,
    getAcceptedOffer,
    getAgreementStatus,
    getNach,
    changeActiveState,
    fetchProducts,
    getBusinessByMerchantId,
    getLenderDetails,
    getDisbursalDetails,
    getScheduleDetails,
    getOfferVerificationTasks,
  }
)
class FormSectionRenderer extends Component {
  componentDidUpdate(prevProps, prevState, snapshot) {
    const { meta, context } = this.props.loanApplicationDetails;

    if (
      this.props.loanApplicationDetails.meta.data.application &&
      prevProps.loanApplicationDetails.context.activeState !==
        context.activeState &&
      !meta.loading
    ) {
      this.props.changeActiveState(context.activeState);
      this.fetchStateDetails(this.getToBeRenderedState());
    }
    if (
      this.props.loanApplicationDetails.meta.data.application &&
      prevProps.loanApplicationDetails.meta.data.application.status !==
        meta.data.application.status &&
      !meta.loading
    ) {
      this.fetchStateDetails(this.getToBeRenderedState());
      this.props.changeActiveState(meta.data.application.status);
    }

    if (this.formContainer) {
      //TODO:Find a better way to set this. As top status banner is far in dom,
      // forwarding ref is tedious task
      const messageBanner = document.querySelector(
        '.application-status-banner'
      );
      if (messageBanner && this.formContainer.style) {
        this.formContainer.style.height = `calc(100% - ${messageBanner.clientHeight +
          12}px)`;
      } else {
        if (this.formContainer.style) {
          this.formContainer.style.height = '100%';
        }
      }
    }
  }

  constructor(props) {
    super(props);
    this.state = {
      loading: true,
    };
    this.formContainer = React.createRef();
    this.bannerMessageContainer = React.createRef();
  }

  componentDidMount() {
    const { meta } = this.props.loanApplicationDetails;
    if (meta.data.application) {
      this.fetchStateDetails(this.getToBeRenderedState());
    } else {
      this.setState({
        loading: false,
      });
    }
    this.props.fetchDocumentGroups();
    this.props.fetchProducts();
  }

  fetchStateDetails = async state => {
    this.setState({
      loading: true,
    });

    const { meta, business_details } = this.props.loanApplicationDetails;
    if (state === 'BUSINESS_INFO_PENDING') {
      if (meta.data.application.id !== 'new') {
        await this.props.fetchBusinessDetails({
          business_id: meta.data.application.owner_id,
        });
      } else {
        if (!business_details.data.business) {
          try {
            await this.props.getBusinessByMerchantId({
              reference_id: this.props.user.current,
              reference_type: 'MID',
            });
          } catch (e) {
            //Supress error if business does not exist
          }
        }
      }
    }

    if (state === 'PROMOTER_INFO_PENDING') {
      if (meta.data.application.id !== 'new') {
        if (!business_details.data.business) {
          const businessDetails = await this.props.fetchBusinessDetails({
            business_id: meta.data.application.owner_id,
          });
          if (businessDetails.data.applicant_ids) {
            await this.props.fetchApplicantDetails({
              applicant_id: businessDetails.data.applicant_ids[0],
            });
          }
        } else {
          await this.props.fetchApplicantDetails({
            applicant_id: business_details.data.applicant_ids[0],
          });
        }
      } else {
        if (business_details.data.applicant_ids) {
          await this.props.fetchApplicantDetails({
            applicant_id: business_details.data.applicant_ids[0],
          });
        }
      }
    }

    if (state === APPLICATION_STATES.CREDIT_PULL_PENDING) {
      const { meta } = this.props.loanApplicationDetails;
      const businessDetails = await this.props.fetchBusinessDetails({
        business_id: meta.data.application.owner_id,
      });

      await this.props.fetchApplicantDetails({
        applicant_id: businessDetails.data.applicant_ids[0],
      });

      let bureauReportDetails;
      try {
        await this.props.fetchD2cReport({
          application_id: meta.data.application.id,
          applicant_id: businessDetails.data.applicant_ids[0],
          merchant_id: businessDetails.data.business.reference_id,
        });
      } catch (e) {
        //Suppress the error
        console.error('No Bureau Report found');
      }
    }

    if (
      state === APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING ||
      state === APPLICATION_STATES.PREVERIFICATION_FAILED
    ) {
      if (!business_details.data.business) {
        const businessDetails = await this.props.fetchBusinessDetails({
          business_id: meta.data.application.owner_id,
        });
        if (businessDetails.data.applicant_ids) {
          await this.props.fetchApplicantDetails({
            applicant_id: businessDetails.data.applicant_ids[0],
          });
        }
      }
      await this.props.fetchLoanApplicationMeta(
        this.props.loanApplicationDetails.meta.data.application.id
      );
    }

    if (state === APPLICATION_STATES.CREDIT_OFFER_GENERATED) {
      const { meta } = this.props.loanApplicationDetails;
      await this.props.fetchCreditOffers({
        application_id: meta.data.application.id,
      });
      try {
        await this.props.getAcceptedOffer({
          application_id: meta.data.application.id,
        });
      } catch (e) {
        //Suppress the error
        console.warn('No contract found!');
      }
    }

    if (state === APPLICATION_STATES.CONTRACT_PENDING) {
      const { meta } = this.props.loanApplicationDetails;
      try {
        await this.props.getAgreementStatus({
          application_id: meta.data.application.id,
        });
      } catch (e) {
        //Suppress the error
        console.error('Contract Not prepared yet.');
      }
    }

    if (
      state === APPLICATION_STATES.NACH_UPLOAD_PENDING ||
      state === APPLICATION_STATES.NACH_CREATION_PENDING
    ) {
      const { meta } = this.props.loanApplicationDetails;

      const businessDetails = await this.props.fetchBusinessDetails({
        business_id: meta.data.application.owner_id,
      });

      await this.props.fetchApplicantDetails({
        applicant_id: businessDetails.data.applicant_ids[0],
      });

      await this.props.getNach({
        application_id: meta.data.application.id,
      });

      await Promise.all([
        this.props.getAcceptedOffer({
          application_id: meta.data.application.id,
        }),
        this.props.fetchCreditOffers({
          application_id: meta.data.application.id,
        }),
      ]);
    }

    if (state === APPLICATION_STATES.SLOT_SELECTION_PENDING) {
      const {
        meta,
        business_details,
        promoter_details,
      } = this.props.loanApplicationDetails;

      try {
        const acceptedOfferDetails = await this.props.getAcceptedOffer({
          application_id: meta.data.application.id,
        });
        await this.props.getScheduleDetails({
          credit_offer_id: acceptedOfferDetails.data.credit_offer_id,
        });
      } catch (e) {
        //suppress the error
        console.error('Not scheduled yet');
      }

      if (!business_details.data.business) {
        const businessDetails = await this.props.fetchBusinessDetails({
          business_id: meta.data.application.owner_id,
        });
        if (!promoter_details.data.applicant) {
          await this.props.fetchApplicantDetails({
            applicant_id: businessDetails.data.applicant_ids[0],
          });
        }
      }
    }

    if (state === APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED) {
      const { meta } = this.props.loanApplicationDetails;

      const creditOffers = await this.props.fetchCreditOffers({
        application_id: meta.data.application.id,
      });
      const acceptedOfferDetails = await this.props.getAcceptedOffer({
        application_id: meta.data.application.id,
      });

      const acceptedCreditOfferId = acceptedOfferDetails.data.credit_offer_id;
      const acceptedOffer = creditOffers.data.credit_offers.find(
        credit_offer => credit_offer.id === acceptedCreditOfferId
      );

      if (acceptedOffer) {
        await Promise.all([
          this.props.getLenderDetails({
            lender_id: acceptedOffer.lender_id,
          }),
          this.props.getScheduleDetails({
            credit_offer_id: acceptedOffer.id,
          }),
          this.props.getOfferVerificationTasks({
            credit_offer_id: acceptedOffer.id,
          }),
        ]);
      }
    }

    if (state === APPLICATION_STATES.RZP_APPROVED) {
      const { meta } = this.props.loanApplicationDetails;
      await Promise.all([
        this.props.getAcceptedOffer({
          application_id: meta.data.application.id,
        }),
        this.props.fetchCreditOffers({
          application_id: meta.data.application.id,
        }),
      ]);
    }

    if (state === APPLICATION_STATES.CREDIT_DISBURSED) {
      const { meta } = this.props.loanApplicationDetails;

      await Promise.all([
        this.props.getAcceptedOffer({
          application_id: meta.data.application.id,
        }),
        this.props.fetchCreditOffers({
          application_id: meta.data.application.id,
        }),
        this.props.getDisbursalDetails({
          application_id: meta.data.application.id,
        }),
      ]).then(([acceptedOfferDetails, allOffers, _]) => {
        const acceptedCreditOfferId = acceptedOfferDetails.data.credit_offer_id;
        const creditOffer = allOffers.data.credit_offers.find(
          credit_offer => credit_offer.id === acceptedCreditOfferId
        );
        return this.props.getLenderDetails({
          lender_id: creditOffer.lender_id,
        });
      });
    }

    this.setState({
      loading: false,
    });
  };

  getHeader = activeState => {
    const { meta } = this.props.loanApplicationDetails;

    switch (activeState) {
      case 'BUSINESS_INFO_PENDING':
        if (
          isPreceedingState(
            meta.data.application.status,
            APPLICATION_STATES.CONTRACT_PENDING
          )
        ) {
          return getTitleInformation(stateTitleMap['BUSINESS_INFO_PENDING']);
        } else {
          return getTitleInformation(
            stateTitleMap['BUSINESS_INFO_PENDING_LOCKED']
          );
        }
      case 'PROMOTER_INFO_PENDING':
        if (
          isPreceedingState(
            meta.data.application.status,
            APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING
          )
        ) {
          return getTitleInformation(stateTitleMap['PROMOTER_INFO_PENDING']);
        } else {
          return getTitleInformation(
            stateTitleMap['PROMOTER_INFO_PENDING_LOCKED']
          );
        }
      case APPLICATION_STATES.CREDIT_PULL_PENDING:
        const { bureau_report_details } = this.props.loanApplicationDetails;
        if (bureau_report_details.loading) {
          return null;
        }
        if (bureau_report_details.data.bureau_report) {
          return getTitleInformation(stateTitleMap['CREDIT_PULL_COMPLETED']);
        }
        return getTitleInformation(
          stateTitleMap['MOBILE_VERIFICATION_PENDING']
        );
      case APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS:
      case APPLICATION_STATES.SCORE_GENERATION_PENDING:
      case APPLICATION_STATES.CREDIT_OFFER_PENDING:
        return getTitleInformation(
          stateTitleMap[APPLICATION_STATES.SCORE_GENERATION_PENDING]
        );
      case APPLICATION_STATES.CREDIT_OFFER_GENERATED:
        const { accepted_offer_details } = this.props.loanApplicationDetails;
        if (
          !(
            accepted_offer_details.data &&
            accepted_offer_details.data.credit_offer_id
          )
        ) {
          return getTitleInformation(
            stateTitleMap[APPLICATION_STATES.CREDIT_OFFER_GENERATED]
          );
        } else {
          return getTitleInformation(stateTitleMap['CREDIT_OFFER_ACCEPTED']);
        }
      case APPLICATION_STATES.CONTRACT_PENDING:
        const { agreement_details } = this.props.loanApplicationDetails;
        if (agreement_details.data && agreement_details.data.signers) {
          if (agreement_details.data.sign_status === 'SIGNED') {
            return getTitleInformation(stateTitleMap['CONTRACT_SIGNED']);
          } else {
            return getTitleInformation(
              stateTitleMap[APPLICATION_STATES.CONTRACT_PENDING]
            );
          }
        } else {
          //invitation not yet generated
          return getTitleInformation(
            stateTitleMap['CONTRACT_GENERATION_PENDING']
          );
        }
      case APPLICATION_STATES.NACH_CREATION_PENDING:
      case APPLICATION_STATES.NACH_UPLOAD_PENDING:
        return getTitleInformation(
          stateTitleMap[APPLICATION_STATES.NACH_UPLOAD_PENDING]
        );
      case APPLICATION_STATES.SLOT_SELECTION_PENDING:
        return getTitleInformation(
          stateTitleMap[APPLICATION_STATES.SLOT_SELECTION_PENDING]
        );
      case APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED:
        return getTitleInformation(
          stateTitleMap[APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED]
        );
      case APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED:
        return getTitleInformation(
          stateTitleMap[APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED]
        );
      case APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW:
        return getTitleInformation(
          stateTitleMap[APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW]
        );
      case APPLICATION_STATES.RZP_APPROVED:
        return getTitleInformation(
          stateTitleMap[APPLICATION_STATES.RZP_APPROVED]
        );
      case APPLICATION_STATES.CREDIT_DISBURSED:
        return getTitleInformation(
          stateTitleMap[APPLICATION_STATES.CREDIT_DISBURSED]
        );
    }
  };

  getTobeRenderedForm = activeState => {
    let TobeRenderedFormComponent;
    switch (activeState) {
      case APPLICATION_STATES.CREATED:
        TobeRenderedFormComponent = () => <FormSectionLoadingSkeleton />;
        break;
      case APPLICATION_STATES.CREDIT_PULL_PENDING:
        const {
          business_details,
          promoter_details,
          bureau_report_details,
        } = this.props.loanApplicationDetails;
        if (
          promoter_details.loading ||
          business_details.loading ||
          bureau_report_details.loading
        ) {
          return <FormSectionLoadingSkeleton />;
        } else {
          if (bureau_report_details.data.bureau_report) {
            TobeRenderedFormComponent = stateFormMap['CREDIT_PULL_COMPLETED'];
          } else {
            TobeRenderedFormComponent =
              stateFormMap['MOBILE_VERIFICATION_PENDING'];
          }
        }
        break;
      case APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING:
      case APPLICATION_STATES.PREVERIFICATION_FAILED:
        TobeRenderedFormComponent =
          stateFormMap[APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING];
        break;
      case APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS:
      case APPLICATION_STATES.SCORE_GENERATION_PENDING:
      case APPLICATION_STATES.CREDIT_OFFER_PENDING:
        TobeRenderedFormComponent =
          stateFormMap[APPLICATION_STATES.SCORE_GENERATION_PENDING];
        break;
      case APPLICATION_STATES.CONTRACT_PENDING:
        const { agreement_details } = this.props.loanApplicationDetails;
        if (agreement_details.data && agreement_details.data.signers) {
          if (agreement_details.data.sign_status === 'SIGNED') {
            TobeRenderedFormComponent = stateFormMap['CONTRACT_SIGNED'];
          } else {
            TobeRenderedFormComponent = stateFormMap['CONTRACT_PENDING'];
          }
        } else {
          TobeRenderedFormComponent =
            stateFormMap['CONTRACT_GENERATION_PENDING'];
        }
        break;
      case APPLICATION_STATES.NACH_CREATION_PENDING:
      case APPLICATION_STATES.NACH_UPLOAD_PENDING:
        TobeRenderedFormComponent =
          stateFormMap[APPLICATION_STATES.NACH_UPLOAD_PENDING];
        break;

      case 'BUSINESS_INFO_PENDING':
      case 'PROMOTER_INFO_PENDING':
      case APPLICATION_STATES.CREDIT_OFFER_GENERATED:
      case APPLICATION_STATES.SLOT_SELECTION_PENDING:
      case APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED:
      case APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED:
      case APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW:
      case APPLICATION_STATES.RZP_APPROVED:
      case APPLICATION_STATES.CREDIT_DISBURSED:
        TobeRenderedFormComponent = stateFormMap[activeState];
        break;
    }
    return <TobeRenderedFormComponent />;
  };

  getToBeRenderedState = () => {
    const { meta, context } = this.props.loanApplicationDetails;
    const defaultState = 'NOT_STARTED';
    if (context) {
      return context.activeState
        ? context.activeState
        : meta.data.application
        ? meta.data.application.status
        : defaultState;
    } else {
      return meta.data.application
        ? meta.data.application.status
        : defaultState;
    }
  };

  render() {
    const { seed_data } = this.props.loanApplicationDetails;

    const tobeRenderedState = this.getToBeRenderedState();

    return (
      <div className="application-forms-wrapper">
        <div className="hero-image-wrapper">
          <img
            src={'/dist/css/assets/capital/los_onboarding_hero.svg'}
            alt="landing-image"
          />
        </div>
        {this.state.loading || seed_data.loading ? (
          <FormSectionLoadingSkeleton />
        ) : (
          <React.Fragment>
            <LoanStatusBanner
              loanApplicationDetails={this.props.loanApplicationDetails}
              ref={this.bannerMessageContainer}
              changeActiveState={this.props.changeActiveState}
            />
            <div
              className="application-form-container"
              ref={node => (this.formContainer = node)}
            >
              <div className="loan-application-form-section">
                <div className="loan-application-form-header-section">
                  {this.getHeader(tobeRenderedState) ? (
                    <React.Fragment>
                      {this.getHeader(tobeRenderedState)}
                      <hr />
                    </React.Fragment>
                  ) : null}
                </div>
                {this.getTobeRenderedForm(tobeRenderedState)}
              </div>
            </div>
          </React.Fragment>
        )}
      </div>
    );
  }
}

export default FormSectionRenderer;
