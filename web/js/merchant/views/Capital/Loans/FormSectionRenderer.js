import React, { Component } from 'react';
import { connect } from 'react-redux';

import { withRouter } from 'common/deprecated/withRouter';
import {
  changeActiveState,
  fetchApplicantDetails,
  fetchBusinessDetails,
  fetchCreditOffers,
  fetchD2cReport,
  fetchDocumentGroups,
  fetchLoanApplicationMeta,
  fetchProducts,
  getAcceptedOffer,
  getAgreementStatus,
  getBusinessByMerchantId,
  getDisbursalDetails,
  getLenderDetails,
  getNach,
  getOfferVerificationTasks,
  getScheduleDetails,
  getApplications,
} from 'merchant/reducers/capital';
import { trackLandingOnCashAdvanceV1 } from 'merchant/views/Capital/CashAdvanceV2/TrackEvents';
import Banner from 'merchant/views/Capital/components/Banner';
import FormSectionLoadingSkeleton from 'merchant/views/Capital/components/FormSectionLoadingSkeleton';
import {
  isCashAdvanceProduct,
  isPreceedingState,
  getStepIndex,
} from 'merchant/views/Capital/utils';
import getApplicationProgressPercentage from 'merchant/views/Capital/utils/ProgressPercentageCalculator';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import BusinessInfoEntity from './Forms/BusinessInfoEntity';
import CashAdvanceApproved from './Forms/CashAdvanceApproved';
import ContractEntity from './Forms/ContractEntity';
import CreditOfferEntity from './Forms/CreditOfferEntity';
import CreditScoreBreakdown from './Forms/CreditScoreBreakdown';
import DisbursalEntity from './Forms/DisbursalEntity';
import LoanApproved from './Forms/LoanApproved';
import MobileVerification from './Forms/MobileVerification';
import NachEntity from './Forms/NachEntity';
import OfflineDocumentCollection from './Forms/OfflineDocumentCollection';
import PendingState from './Forms/PendingState';
import PreVerification from './Forms/PreVerification/PreVerification';
import PromoterDetailsEntity from './Forms/PromoterDetails/PromoterDetailsEntity';
import LoanStatusBanner from './LoanStatusBanner';
import {
  APPLICATION_STATES,
  APPLICATION_STATE_MESSAGE_MAP,
  APPLICATION_STATE_TITLE_MAP,
  GA_CATEGORY_BY_PRODUCT,
} from './constants';
import { LoanConfigImages } from '../loaders/constants';

const stateFormMap = {
  BUSINESS_INFO_PENDING: BusinessInfoEntity,
  PROMOTER_INFO_PENDING: PromoterDetailsEntity,
  MOBILE_VERIFICATION_PENDING: MobileVerification,
  CREDIT_PULL_COMPLETED: CreditScoreBreakdown,
  [APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING]: PreVerification,
  [APPLICATION_STATES.PREVERIFICATION_FAILED]: PreVerification,
  [APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS]: PreVerification,
  [APPLICATION_STATES.SCORE_GENERATION_PENDING]: (props) => (
    <PendingState
      message={APPLICATION_STATE_MESSAGE_MAP[APPLICATION_STATES.SCORE_GENERATION_PENDING]}
      showNavigation
      navigation={props.navigation}
    />
  ),
  [APPLICATION_STATES.CREDIT_OFFER_GENERATED]: CreditOfferEntity,
  CONTRACT_GENERATION_PENDING: (props) => (
    <PendingState
      message={APPLICATION_STATE_MESSAGE_MAP.CONTRACT_GENERATION_PENDING}
      showNavigation
      navigation={props.navigation}
    />
  ),
  [APPLICATION_STATES.CONTRACT_PENDING]: ContractEntity,
  CONTRACT_SIGNED: ContractEntity,
  [APPLICATION_STATES.NACH_UPLOAD_PENDING]: NachEntity,
  // [APPLICATION_STATES.SLOT_SELECTION_PENDING]: VerificationSlotSelection,
  // [APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED]: DocumentCollectionInformation,
  [APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING]: OfflineDocumentCollection,
  // [APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED]: (props) => (
  //   <PendingState
  //     message={APPLICATION_STATE_MESSAGE_MAP[APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED]}
  //     showNavigation
  //     navigation={props.navigation}
  //   />
  // ),
  [APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW]: (props) => (
    <PendingState
      message={APPLICATION_STATE_MESSAGE_MAP[APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW]}
      showNavigation
      navigation={props.navigation}
    />
  ),
  [`LOAN_${APPLICATION_STATES.RZP_APPROVED}`]: LoanApproved,
  [`LOC_${APPLICATION_STATES.RZP_APPROVED}`]: CashAdvanceApproved,
  [APPLICATION_STATES.CREDIT_DISBURSED]: DisbursalEntity,
};

@connect(
  (state) => ({
    user: state.session.user,
  }),
  {
    fetchLoanApplicationMeta,
    fetchBusinessDetails,
    fetchApplicantDetails,
    getApplications,
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
  },
)
class FormSectionRenderer extends Component {
  componentDidUpdate(prevProps) {
    const { meta, context } = this.props.loanApplicationDetails;

    if (
      this.props.loanApplicationDetails.meta.data.application &&
      prevProps.loanApplicationDetails.context.activeState !== context.activeState &&
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
      this.changeActiveState(meta.data.application.status);
    }

    if (this.formContainer) {
      const messageBanner = document.querySelector('.application-status-banner');
      if (messageBanner && this.formContainer.style) {
        this.formContainer.style.height = `calc(100% - ${messageBanner.clientHeight + 12}px)`;
      } else if (this.formContainer.style) {
        this.formContainer.style.height = '100%';
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
      this.trackSegmentEvent();
    } else {
      Promise.resolve().then(() => {
        this.setState({
          loading: false,
        });
      });
    }
    this.props.fetchDocumentGroups(this.props.user.business_type == 1);
    this.props.fetchProducts();
  }

  trackSegmentEvent = () => {
    const { meta = {} } = this.props.loanApplicationDetails;
    const isCashAdvance = isCashAdvanceProduct(meta?.product);
    if (isCashAdvance) {
      trackLandingOnCashAdvanceV1();
    }
  };

  getTitleInformation = (info) => {
    const { meta } = this.props.loanApplicationDetails;
    const isCashAdvance = isCashAdvanceProduct(meta.product);
    const bannerProps = info;

    if (isCashAdvance) {
      const keys = Object.keys(info);

      keys.forEach((key) => {
        if (typeof bannerProps[key] === 'string') {
          bannerProps[key] = bannerProps[key]
            .replace('loan', 'cash advance')
            .replace('Loan', 'Cash Advance');
        }
      });
    }

    return <Banner {...bannerProps} isFormHeader={true} />;
  };

  fetchStateDetails = async (state) => {
    this.setState({
      loading: true,
    });

    const { meta, business_details } = this.props.loanApplicationDetails;
    if (state === 'BUSINESS_INFO_PENDING') {
      if (meta.data.application.id !== 'new') {
        await this.props.fetchBusinessDetails({
          business_id: meta.data.application.owner_id,
        });
      } else if (!business_details?.data?.business) {
        try {
          await this.props.getBusinessByMerchantId({
            reference_id: this.props.user?.current,
            reference_type: 'MID',
          });
        } catch (e) {
          //Supress error if business does not exist
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
      } else if (business_details.data.applicant_ids) {
        await this.props.fetchApplicantDetails({
          applicant_id: business_details.data.applicant_ids[0],
        });
      }
      await this.props.getApplications({
        owner_type: 'MERCHANT',
        owner_id: this.props.user.current,
        product_id: this.props.productDetails.id,
      });
    }

    if (state === APPLICATION_STATES.CREDIT_PULL_PENDING) {
      const { meta, business_details, promoter_details, bureau_report_details } =
        this.props.loanApplicationDetails;
      if (!business_details.data || !business_details.data.applicant_ids) {
        await this.props.fetchBusinessDetails({
          business_id: meta.data.application.owner_id,
        });
      }

      if (!promoter_details.data || !promoter_details.data.applicant) {
        const { business_details } = this.props.loanApplicationDetails;
        await this.props.fetchApplicantDetails({
          applicant_id: business_details.data.applicant_ids[0],
        });
      }

      try {
        if (!bureau_report_details.error || !bureau_report_details.data) {
          const { business_details } = this.props.loanApplicationDetails;
          await this.props.fetchD2cReport({
            application_id: meta.data.application.id,
            applicant_id: business_details.data.applicant_ids[0],
            merchant_id: business_details.data.business.reference_id,
          });
        }
      } catch (e) {
        //Suppress the error
        console.error('No Bureau Report found', e);
      }
    }

    if (
      state === APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING ||
      state === APPLICATION_STATES.PREVERIFICATION_FAILED
    ) {
      await this.props.fetchLoanApplicationMeta(
        this.props.loanApplicationDetails.meta.data.application.id,
      );
      // !business_details.data.business

      // Ideally, to fix redundant call, check if the data exists in the store before fetching the data
      const businessDetails = await this.props.fetchBusinessDetails({
        business_id: meta.data.application.owner_id,
      });
      if (businessDetails.data.applicant_ids) {
        await this.props.fetchApplicantDetails({
          applicant_id: businessDetails.data.applicant_ids[0],
        });
      }
      try {
        await this.props.fetchD2cReport({
          application_id: meta.data.application.id,
          applicant_id: businessDetails.data.applicant_ids[0],
          merchant_id: businessDetails.data.business.reference_id,
        });
      } catch (err) {
        console.log('Bureau Report Failed');
      }
    }

    if (state === APPLICATION_STATES.CREDIT_OFFER_GENERATED) {
      const { meta } = this.props.loanApplicationDetails;
      await this.props.fetchCreditOffers(
        {
          application_id: meta.data.application.id,
        },
        meta.product,
      );
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
        this.props.fetchCreditOffers(
          {
            application_id: meta.data.application.id,
          },
          meta.product,
        ),
      ]);
    }

    // if (state === APPLICATION_STATES.SLOT_SELECTION_PENDING) {
    //   const { meta, business_details, promoter_details } = this.props.loanApplicationDetails;

    //   try {
    //     const acceptedOfferDetails = await this.props.getAcceptedOffer({
    //       application_id: meta.data.application.id,
    //     });
    //     await this.props.getScheduleDetails({
    //       credit_offer_id: acceptedOfferDetails.data.credit_offer_id,
    //     });
    //   } catch (e) {
    //     //suppress the error
    //     console.error('Not scheduled yet');
    //   }

    //   if (!business_details.data.business) {
    //     const businessDetails = await this.props.fetchBusinessDetails({
    //       business_id: meta.data.application.owner_id,
    //     });
    //     if (!promoter_details.data.applicant) {
    //       await this.props.fetchApplicantDetails({
    //         applicant_id: businessDetails.data.applicant_ids[0],
    //       });
    //     }
    //   }
    // }

    if (state === APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING) {
      await this.props.fetchLoanApplicationMeta(
        this.props.loanApplicationDetails.meta.data.application.id,
      );
    }
    // if (state === APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED) {
    //   const { meta } = this.props.loanApplicationDetails;

    //   const creditOffers = await this.props.fetchCreditOffers(
    //     {
    //       application_id: meta.data.application.id,
    //     },
    //     meta.product,
    //   );
    //   const acceptedOfferDetails = await this.props.getAcceptedOffer({
    //     application_id: meta.data.application.id,
    //   });

    //   const acceptedCreditOfferId = acceptedOfferDetails.data.credit_offer_id;
    //   const acceptedOffer = creditOffers.data.credit_offers.find(
    //     (credit_offer) => credit_offer.id === acceptedCreditOfferId,
    //   );

    //   if (acceptedOffer) {
    //     await Promise.all([
    //       this.props.getLenderDetails({
    //         lender_id: acceptedOffer.lender_id,
    //       }),
    //       this.props.getScheduleDetails({
    //         credit_offer_id: acceptedOffer.id,
    //       }),
    //       this.props.getOfferVerificationTasks({
    //         credit_offer_id: acceptedOffer.id,
    //       }),
    //     ]);
    //   }
    // }

    if (state === APPLICATION_STATES.RZP_APPROVED) {
      const { meta } = this.props.loanApplicationDetails;
      await Promise.all([
        this.props.getAcceptedOffer({
          application_id: meta.data.application.id,
        }),
        this.props.fetchCreditOffers(
          {
            application_id: meta.data.application.id,
          },
          meta.product,
        ),
      ]);
    }

    if (state === APPLICATION_STATES.CREDIT_DISBURSED) {
      const { meta } = this.props.loanApplicationDetails;

      await Promise.all([
        this.props.getAcceptedOffer({
          application_id: meta.data.application.id,
        }),
        this.props.fetchCreditOffers(
          {
            application_id: meta.data.application.id,
          },
          meta.product,
        ),
        this.props.getDisbursalDetails({
          application_id: meta.data.application.id,
        }),
      ]).then(([acceptedOfferDetails, allOffers, _]) => {
        const acceptedCreditOfferId = acceptedOfferDetails.data.credit_offer_id;
        const creditOffer = allOffers.data.credit_offers.find(
          (credit_offer) => credit_offer.id === acceptedCreditOfferId,
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

  getHeader = (activeState) => {
    const { meta } = this.props.loanApplicationDetails;

    switch (activeState) {
      case 'BUSINESS_INFO_PENDING':
        if (isPreceedingState(meta.data.application.status, APPLICATION_STATES.CONTRACT_PENDING)) {
          return this.getTitleInformation(APPLICATION_STATE_TITLE_MAP.BUSINESS_INFO_PENDING);
        } else {
          return this.getTitleInformation(APPLICATION_STATE_TITLE_MAP.BUSINESS_INFO_PENDING_LOCKED);
        }
      case 'PROMOTER_INFO_PENDING':
        if (
          isPreceedingState(meta.data.application.status, APPLICATION_STATES.CREDIT_PULL_PENDING)
        ) {
          const { bureau_report_details } = this.props.loanApplicationDetails;
          if (bureau_report_details.data.bureau_report) {
            return this.getTitleInformation(
              APPLICATION_STATE_TITLE_MAP.PROMOTER_INFO_PENDING_LOCKED,
            );
          }
          return this.getTitleInformation(APPLICATION_STATE_TITLE_MAP.PROMOTER_INFO_PENDING);
        } else {
          return this.getTitleInformation(APPLICATION_STATE_TITLE_MAP.PROMOTER_INFO_PENDING_LOCKED);
        }
      case APPLICATION_STATES.CREDIT_PULL_PENDING: {
        const { bureau_report_details } = this.props.loanApplicationDetails;
        if (bureau_report_details.loading) {
          return null;
        }
        if (bureau_report_details.data.bureau_report) {
          const { score, ntc_score } = bureau_report_details.data.bureau_report;
          if (!!ntc_score && !score) {
            return this.getTitleInformation(
              APPLICATION_STATE_TITLE_MAP.CREDIT_PULL_COMPLETED_WITH_NTC,
            );
          }
          return this.getTitleInformation(APPLICATION_STATE_TITLE_MAP.CREDIT_PULL_COMPLETED);
        }
        return this.getTitleInformation(APPLICATION_STATE_TITLE_MAP.MOBILE_VERIFICATION_PENDING);
      }
      case APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS:
      case APPLICATION_STATES.SCORE_GENERATION_PENDING:
      case APPLICATION_STATES.CREDIT_OFFER_PENDING:
        return this.getTitleInformation(
          APPLICATION_STATE_TITLE_MAP[APPLICATION_STATES.SCORE_GENERATION_PENDING],
        );
      case APPLICATION_STATES.CREDIT_OFFER_GENERATED: {
        const { accepted_offer_details } = this.props.loanApplicationDetails;
        if (!(accepted_offer_details.data && accepted_offer_details.data.credit_offer_id)) {
          return this.getTitleInformation(
            APPLICATION_STATE_TITLE_MAP[APPLICATION_STATES.CREDIT_OFFER_GENERATED],
          );
        } else {
          return this.getTitleInformation(APPLICATION_STATE_TITLE_MAP.CREDIT_OFFER_ACCEPTED);
        }
      }
      case APPLICATION_STATES.CONTRACT_PENDING: {
        const { agreement_details } = this.props.loanApplicationDetails;
        if (agreement_details.data && agreement_details.data.signers) {
          if (agreement_details.data.sign_status === 'SIGNED') {
            return this.getTitleInformation(APPLICATION_STATE_TITLE_MAP.CONTRACT_SIGNED);
          } else {
            return this.getTitleInformation(
              APPLICATION_STATE_TITLE_MAP[APPLICATION_STATES.CONTRACT_PENDING],
            );
          }
        } else {
          //invitation not yet generated
          return this.getTitleInformation(APPLICATION_STATE_TITLE_MAP.CONTRACT_GENERATION_PENDING);
        }
      }
      case APPLICATION_STATES.NACH_CREATION_PENDING:
      case APPLICATION_STATES.NACH_UPLOAD_PENDING:
        return this.getTitleInformation(
          APPLICATION_STATE_TITLE_MAP[APPLICATION_STATES.NACH_UPLOAD_PENDING],
        );
      // case APPLICATION_STATES.SLOT_SELECTION_PENDING:
      //   return this.getTitleInformation(
      //     APPLICATION_STATE_TITLE_MAP[APPLICATION_STATES.SLOT_SELECTION_PENDING],
      //   );
      // case APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED:
      //   return this.getTitleInformation(
      //     APPLICATION_STATE_TITLE_MAP[APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED],
      //   );
      case APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING:
        return this.getTitleInformation(
          APPLICATION_STATE_TITLE_MAP[APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING],
        );
      // case APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED:
      //   return this.getTitleInformation(
      //     APPLICATION_STATE_TITLE_MAP[APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED],
      //   );
      case APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW:
        return this.getTitleInformation(
          APPLICATION_STATE_TITLE_MAP[APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW],
        );
      case APPLICATION_STATES.RZP_APPROVED:
        return this.getTitleInformation(
          APPLICATION_STATE_TITLE_MAP[`${meta.product}_${APPLICATION_STATES.RZP_APPROVED}`],
        );
      case APPLICATION_STATES.CREDIT_DISBURSED:
        return this.getTitleInformation(
          APPLICATION_STATE_TITLE_MAP[APPLICATION_STATES.CREDIT_DISBURSED],
        );
      default:
        return null;
    }
  };

  getTobeRenderedForm = (activeState) => {
    let TobeRenderedFormComponent;
    const { meta } = this.props.loanApplicationDetails;

    switch (activeState) {
      case APPLICATION_STATES.CREATED:
        TobeRenderedFormComponent = () => <FormSectionLoadingSkeleton />;
        break;
      case APPLICATION_STATES.CREDIT_PULL_PENDING: {
        const { business_details, promoter_details, bureau_report_details } =
          this.props.loanApplicationDetails;
        if (promoter_details.loading || business_details.loading || bureau_report_details.loading) {
          return <FormSectionLoadingSkeleton />;
        } else if (bureau_report_details?.data.bureau_report) {
          TobeRenderedFormComponent = stateFormMap.CREDIT_PULL_COMPLETED;
        } else {
          TobeRenderedFormComponent = stateFormMap.MOBILE_VERIFICATION_PENDING;
        }
        break;
      }
      case APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING:
      case APPLICATION_STATES.PREVERIFICATION_FAILED:
        TobeRenderedFormComponent = stateFormMap[APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING];
        break;
      case APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS:
      case APPLICATION_STATES.SCORE_GENERATION_PENDING:
      case APPLICATION_STATES.CREDIT_OFFER_PENDING:
        TobeRenderedFormComponent = stateFormMap[APPLICATION_STATES.SCORE_GENERATION_PENDING];
        break;
      case APPLICATION_STATES.CONTRACT_PENDING: {
        const { agreement_details } = this.props.loanApplicationDetails;
        if (agreement_details.data && agreement_details.data.signers) {
          if (agreement_details.data.sign_status === 'SIGNED') {
            TobeRenderedFormComponent = stateFormMap.CONTRACT_SIGNED;
          } else {
            TobeRenderedFormComponent = stateFormMap.CONTRACT_PENDING;
          }
        } else {
          TobeRenderedFormComponent = stateFormMap.CONTRACT_GENERATION_PENDING;
        }
        break;
      }
      case APPLICATION_STATES.NACH_CREATION_PENDING:
      case APPLICATION_STATES.NACH_UPLOAD_PENDING:
        TobeRenderedFormComponent = stateFormMap[APPLICATION_STATES.NACH_UPLOAD_PENDING];
        break;
      case APPLICATION_STATES.RZP_APPROVED: {
        TobeRenderedFormComponent = stateFormMap[`${meta.product}_RZP_APPROVED`];
        break;
      }
      // case APPLICATION_STATES.SLOT_SELECTION_PENDING:
      // case APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED:
      // case APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED:
      case 'BUSINESS_INFO_PENDING':
      case 'PROMOTER_INFO_PENDING':
      case APPLICATION_STATES.CREDIT_OFFER_GENERATED:
      case APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING:
      case APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW:
      case APPLICATION_STATES.CREDIT_DISBURSED:
        TobeRenderedFormComponent = stateFormMap[activeState];
        break;
      default:
        break;
    }

    return (
      <TobeRenderedFormComponent
        _trackNavigationActions={this._trackNavigationActions}
        _trackEvent={this.gaEventDispatcher}
        navigation={this.getNavigationActions(activeState)}
        nextState={this.getNextState(activeState)}
        previousState={this.getPreviousState(activeState)}
        product={meta.product}
      />
    );
  };

  trackNavigationEvent = (from, to, product) => {
    const APPLICATION_STATE_DESCRIPTIONS =
      this.getUserFlowConfiguration().getApplicationStateDescriptions();
    const stepIndex = getStepIndex(
      from,
      this.getUserFlowConfiguration().getApplicationStateGroups(),
    );

    const toStepLabel = APPLICATION_STATE_DESCRIPTIONS[to].short_description;
    const fromStepLabel = APPLICATION_STATE_DESCRIPTIONS[from].short_description;
    this.gaEventDispatcher({
      eventAction: `Step ${stepIndex} | ${fromStepLabel} - ${toStepLabel}`,
      eventLabel: this._getProgressPercentage(),
      eventCategory: GA_CATEGORY_BY_PRODUCT[product],
    });
  };

  _getProgressPercentage = () => {
    const { meta } = this.props.loanApplicationDetails;
    if (!meta.data.application.status) return 0;
    return getApplicationProgressPercentage(
      meta.data.application.status,
      meta.configuration.getApplicationStateGroups(),
    );
  };

  changeActiveState = (nextState, data) => {
    const { changeActiveState, loanApplicationDetails } = this.props;
    const { meta, context } = loanApplicationDetails;
    this.trackNavigationEvent(context.activeState, nextState, meta.product);
    changeActiveState(nextState, data);
  };

  getNavigationActions = (activeState) => {
    const nextState = this.getNextState(activeState);
    const previousState = this.getPreviousState(activeState);
    const thisRef = this;
    return {
      next(data = null) {
        if (nextState) thisRef.changeActiveState(nextState, data);
      },
      back(data = null) {
        if (previousState) thisRef.changeActiveState(previousState, data);
      },
    };
  };

  getNextState = (activeState) => {
    const STATE_TRANSITIONS = this.getUserFlowConfiguration().getStateTransitions();
    return STATE_TRANSITIONS[activeState].next;
  };

  getPreviousState = (activeState) => {
    const STATE_TRANSITIONS = this.getUserFlowConfiguration().getStateTransitions();
    return STATE_TRANSITIONS[activeState].back;
  };

  getUserFlowConfiguration = () => {
    const { meta } = this.props.loanApplicationDetails;
    return meta.configuration;
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
      return meta.data.application ? meta.data.application.status : defaultState;
    }
  };

  gaEventDispatcher = (eventObject) => {
    eventObject.eventCategory =
      GA_CATEGORY_BY_PRODUCT[this.props.loanApplicationDetails.meta.product];
    window.rzpAnalytics(eventObject);
  };

  _getParentStepLabel = (step) => {
    const { meta } = this.props.loanApplicationDetails;

    return Object.values(meta.configuration.getSideNavigationStateGroups()).filter((meta) =>
      Object.values(meta.steps)
        .reduce((acc, curr) => [...acc, ...curr], [])
        .includes(step),
    )[0].description;
  };

  _trackNavigationActions = (actionType, to, subpage = '') => {
    this.props.sendDataToAnalytics({
      eventAction: `Application | ${actionType}`,
      status: to,
      majorStepTitle: this._getParentStepLabel(to),
      subpage,
    });
  };

  render() {
    const { seed_data, meta } = this.props.loanApplicationDetails;

    const tobeRenderedState = this.getToBeRenderedState();

    return (
      <div className="application-forms-wrapper">
        <div
          className={`hero-image-wrapper ${
            isCashAdvanceProduct(meta.product) ? 'cash-advance-hero' : ''
          }`}
        >
          <img
            src={
              LoanConfigImages[this.getUserFlowConfiguration().ui.product.secondaryHeroImageSource]
            }
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
              changeActiveState={this.changeActiveState}
              isCashAdvanceProduct={isCashAdvanceProduct(meta.product)}
            />
            <div className="application-form-container" ref={(node) => (this.formContainer = node)}>
              <div className="loan-application-form-section">
                <div className="loan-application-form-header-section">
                  {this.getHeader(tobeRenderedState) ? (
                    <React.Fragment>
                      {this.getHeader(tobeRenderedState)}
                      {this.getToBeRenderedState() === APPLICATION_STATES.PROMOTER_INFO_PENDING ||
                      this.getToBeRenderedState() === 'PROMOTER_INFO_PENDING_LOCKED' ? null : (
                        <hr />
                      )}
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

export default withRouter(FormSectionRenderer);
