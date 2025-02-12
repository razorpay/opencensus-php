import React from 'react';

import store from 'merchant/store';

import BaseConfigLoader from './BaseConfigLoader';
import {
  //TODO: Eliminate this by extending from base config loader
  APPLICATION_STATE_DESCRIPTIONS,
  APPLICATION_STATES,
  CONSOLIDATED_STATES,
} from '../Loans/constants';
import { isPreceedingState } from '../utils';

export default class LowerGMVCashAdvanceConfigLoader extends BaseConfigLoader {
  constructor(loanApplication) {
    super(loanApplication);
    this.loanApplication = loanApplication;

    this.ui = {
      product: {
        title: 'Cash Advance',
        heroImageSource: 'loc_onboarding_hero',
        secondaryHeroImageSource: 'loc_secondary_hero',
        pros: [
          <div className="flex" key="1">
            <img src={require('assets/capital/internal_credit.svg')} alt="landing-image" />
            <div className="p-l m-l m-t">
              <strong>
                <p>Withdraw Cash Instantly</p>
              </strong>
              <p className="privilege-description">
                Once enabled, transfer additional money to your account under 10 seconds
              </p>
            </div>
          </div>,
          <div className="flex m-t" key="2">
            <img src={require('assets/capital/auto_repayment.svg')} alt="landing-image" />
            <div className="p-l m-l m-t">
              <strong>
                <p>Auto-repay from settlements</p>
              </strong>
              <p className="privilege-description">
                Withdraw expected customer payments now and repay from future settlements
              </p>
            </div>
          </div>,
          <div className="flex m-t" key="3">
            <img src={require('assets/capital/flexible_interest.svg')} alt="landing-image" />
            <div className="p-l m-l m-t">
              <strong>
                <p>Pay Interest only when you withdraw</p>
              </strong>
              <p className="privilege-description">
                Pay interest on the amount you actually use and for time before repayment
              </p>
            </div>
          </div>,
        ],
        summary: (
          <div className="Details-desc privileges">
            Get additional money whenever required, repay and borrow again up to your limit any
            number of times.
          </div>
        ),
        applicationFinalCTA: {
          text: 'Start Withdrawing Now',
          destination: '/capital/cash-advance/',
        },
        get allowPerfios() {
          return store.getState().session.user.isNetBankingEnabled;
        },
      },
    };
  }

  getConsolidatedStateSequence() {
    return [
      CONSOLIDATED_STATES.CHECK_LOAN_ELIGIBILITY,
      CONSOLIDATED_STATES.LOAN_APPLICATION,
      CONSOLIDATED_STATES.FINAL_REVIEW,
    ];
  }

  getSideNavigationStateGroups() {
    return {
      [CONSOLIDATED_STATES.CHECK_LOAN_ELIGIBILITY]: {
        steps: {
          PROMOTER_INFO_PENDING: ['PROMOTER_INFO_PENDING'],
          [APPLICATION_STATES.CREDIT_PULL_PENDING]: [
            APPLICATION_STATES.CREDIT_PULL_PENDING,
            APPLICATION_STATES.CREDIT_PULL_FAILED,
          ],
        },
        description: 'Check Eligibility',
        index: 0,
      },
      [CONSOLIDATED_STATES.LOAN_APPLICATION]: {
        steps: {
          [APPLICATION_STATES.CREDIT_OFFER_GENERATED]: [APPLICATION_STATES.CREDIT_OFFER_GENERATED],
          [APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING]: [
            APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING,
          ],
        },
        description: 'Complete application',
        index: 1,
      },
      [CONSOLIDATED_STATES.FINAL_REVIEW]: {
        steps: {
          // [APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW]: [APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW],
          [APPLICATION_STATES.RZP_APPROVED]: [
            APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW,
            APPLICATION_STATES.RZP_APPROVED,
          ],
        },
        description: 'Cash Advance Approval',
        index: 2,
      },
    };
  }

  getApplicationStateGroups() {
    return {
      CHECK_LOAN_ELIGIBILITY: [
        // TODO: remove this and add in status as PROMOTER_INFO_PENDING in
        // registerLOCApplication
        'PROMOTER_INFO_PENDING',
        APPLICATION_STATES.CREDIT_PULL_PENDING,
        APPLICATION_STATES.CREDIT_PULL_FAILED,
      ],
      LOAN_APPLICATION: [
        APPLICATION_STATES.CREDIT_OFFER_GENERATED,
        //TODO:(name might change)This is the new state backend is going to introduce to enable offline document verification process.
        //TODO: Add this in application states and state descriptions.
        APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING,
      ],
      FINAL_REVIEW: [
        ...(isPreceedingState(this.applicationStatus, APPLICATION_STATES.RZP_APPROVED, true)
          ? [APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW]
          : []),
        APPLICATION_STATES.RZP_APPROVED,
      ],
    };
  }

  getApplicationStateDescriptions() {
    return {
      ...APPLICATION_STATE_DESCRIPTIONS,
      BUSINESS_INFO_PENDING: {
        title: 'Check Eligibility',
        description: 'Complete your Cash Advance eligibility within a few minutes.',
        ctaText: 'Start your application',
        short_description: 'Business Info',
      },
      PROMOTER_INFO_PENDING: {
        title: 'Check Eligibility',
        description: 'Complete your Cash Advance eligibility within a few minutes.',
        short_description: 'Promoter Info',
        ctaText: 'Start your application',
      },
      [APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING]: {
        title: 'Check Eligibility',
        description: 'Upload documents to check Eligibility within a few minutes.',
        ctaText: 'Continue Applying',
        short_description: 'Documents Upload',
        stages: {
          ADDRESS_PROOF: 'Address Proof',
          BUSINESS_DOCS: 'Business Docs',
          BANK_STATEMENT: 'Bank Statements',
        },
      },
      [APPLICATION_STATES.CREDIT_OFFER_PENDING]: {
        title: 'Evaluating Credit offer...',
        description: 'We are evaluating your eligibility to calculate the Credit offer.',
        ctaText: 'view application',
        short_description: 'Cash Advance Offer',
      },
      [APPLICATION_STATES.CREDIT_OFFER_GENERATED]: {
        title: 'Cash Advance Offer generated',
        description: 'Accept the Cash Advance Offer and complete the loan process.',
        ctaText: 'View Cash Advance Offer',
        short_description: 'Cash Advance Offer',
      },
      [APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW]: {
        title: 'Documents review',
        description: 'We’re reviewing your documents internally and with our vendor.',
        ctaText: 'view',
        short_description: 'Documents Review',
      },
      [APPLICATION_STATES.RZP_APPROVED]: {
        title: 'Cash Advance approved',
        description: 'Your Cash Advance Application has been successfully approved!',
        ctaText: 'View Credit offer',
        short_description: 'Approval',
      },
    };
  }

  getCompletedStateGroupDescriptions() {
    // [APPLICATION_STATES.CREDIT_OFFER_GENERATED]: {
    //   title: 'Congratulations, You have an Offer!',
    //     description: 'Accept the Cash Advance Offer and complete the loan process.',
    //     ctaText: 'View Cash Advance Offer',
    //     short_description: 'Cash Advance Offer',
    // },
    return {
      [CONSOLIDATED_STATES.CHECK_LOAN_ELIGIBILITY]: {
        title: 'Check Eligibility',
        description: 'Eligibility check is successful and Credit offer has been calculated.',
      },
      [CONSOLIDATED_STATES.LOAN_APPLICATION]: {
        title: 'Complete Application',
        description: 'Accepted the Cash Advance offer and documents has been collected.',
      },
      [CONSOLIDATED_STATES.FINAL_REVIEW]: {
        title: 'Cash Advance Approved!',
        description: 'Your Cash Advance Application has been successfully approved!',
      },
    };
  }

  getRequiredDocumentEntities() {
    return [
      // {
      //   index: 0,
      //   title: 'Address Proof',
      //   value: 'address_proof',
      // },
      // {
      //   index: 1,
      //   title: 'Business Proof',
      //   value: 'business_proof',
      // },
      {
        index: 0,
        title: 'Bank Statement',
        value: 'financial_proof',
      },
    ];
  }
}
