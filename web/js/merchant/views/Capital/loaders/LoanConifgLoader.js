import React from 'react';

import store from 'merchant/store';

import BaseConfigLoader from './BaseConfigLoader';
import {
  APPLICATION_STATE_DESCRIPTIONS,
  CONSOLIDATED_STATES,
  APPLICATION_STATES,
} from '../Loans/constants';
import { isPreceedingState } from '../utils';

export default class LoansConfigLoader extends BaseConfigLoader {
  constructor(loanApplication) {
    super(loanApplication);
    this.ui = {
      product: {
        title: 'Business Loans for you',
        heroImageSource: 'los_onboarding_hero',
        secondaryHeroImageSource: 'los_onboarding_hero',
        pros: [
          <React.Fragment key={1}>
            <i className="i i-bullet" />
            <span>Get competitive interest rates for your risk profile</span>
          </React.Fragment>,
          <React.Fragment key={2}>
            <i class="i i-bullet" />
            <span>Apply online in 5 minutes with support when you need</span>
          </React.Fragment>,
          <React.Fragment key={3}>
            <i class="i i-bullet" />
            <span>Repay easily from daily settlements with more options</span>
          </React.Fragment>,
        ],
        summary: (
          <React.Fragment>
            <div className="Details-desc privileges">
              Achieve your goals by financing your business needs effectively. Get a collateral-free
              Working Capital Loan in as fast as two days.
            </div>
            <div className="Details-desc privileges">
              As a privileged member of Razorpay, you get the following benefits:
            </div>
          </React.Fragment>
        ),
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
      CONSOLIDATED_STATES.DOCUMENT_COLLECTION,
      CONSOLIDATED_STATES.FINAL_REVIEW,
      CONSOLIDATED_STATES.FUND_DISBURSED,
    ];
  }

  getApplicationStateDescriptions() {
    return APPLICATION_STATE_DESCRIPTIONS;
  }

  getSideNavigationStateGroups() {
    return {
      [CONSOLIDATED_STATES.CHECK_LOAN_ELIGIBILITY]: {
        steps: {
          // BUSINESS_INFO_PENDING: ['BUSINESS_INFO_PENDING'],
          PROMOTER_INFO_PENDING: ['PROMOTER_INFO_PENDING'],
          [APPLICATION_STATES.CREDIT_PULL_PENDING]: [
            APPLICATION_STATES.CREDIT_PULL_PENDING,
            APPLICATION_STATES.CREDIT_PULL_FAILED,
          ],
          [APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING]: [
            APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING,
            APPLICATION_STATES.PREVERIFICATION_FAILED,
          ],
        },
        description: 'Check loan eligibility',
        index: 0,
      },
      [CONSOLIDATED_STATES.LOAN_APPLICATION]: {
        steps: {
          [APPLICATION_STATES.CREDIT_OFFER_GENERATED]: [
            APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS,
            APPLICATION_STATES.SCORE_GENERATION_PENDING,
            APPLICATION_STATES.CREDIT_OFFER_PENDING,
            APPLICATION_STATES.CREDIT_OFFER_GENERATED,
          ],
          // [APPLICATION_STATES.CONTRACT_PENDING]: [
          //   APPLICATION_STATES.CONTRACT_PENDING,
          // ],
          // [APPLICATION_STATES.NACH_UPLOAD_PENDING]: [
          //   APPLICATION_STATES.NACH_CREATION_PENDING,
          //   APPLICATION_STATES.NACH_UPLOAD_PENDING,
          // ],
        },
        description: 'Complete application',
        index: 1,
      },
      // [CONSOLIDATED_STATES.DOCUMENT_COLLECTION]: {
      //   steps: {
      //     [APPLICATION_STATES.SLOT_SELECTION_PENDING]: [APPLICATION_STATES.SLOT_SELECTION_PENDING],
      //     [APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED]: [
      //       APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED,
      //       APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED,
      //     ],
      //   },
      //   description: 'Document Collection',
      //   index: 2,
      // },
      [CONSOLIDATED_STATES.DOCUMENT_COLLECTION]: {
        steps: {
          [APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING]: [
            APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING,
            // APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED,
          ],
        },
        description: 'Document Collection',
        index: 2,
      },
      [CONSOLIDATED_STATES.FINAL_REVIEW]: {
        steps: {
          [APPLICATION_STATES.RZP_APPROVED]: [
            APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW,
            APPLICATION_STATES.RZP_APPROVED,
          ],
        },
        description: 'Final Review',
        index: 3,
      },
      [CONSOLIDATED_STATES.FUND_DISBURSED]: {
        steps: {
          [APPLICATION_STATES.CREDIT_DISBURSED]: [APPLICATION_STATES.CREDIT_DISBURSED],
        },
        description: 'Fund Disbursement',
        index: 4,
      },
    };
  }

  getApplicationStateGroups() {
    return {
      CHECK_LOAN_ELIGIBILITY: [
        // 'BUSINESS_INFO_PENDING',
        'PROMOTER_INFO_PENDING',
        APPLICATION_STATES.CREDIT_PULL_PENDING,
        APPLICATION_STATES.CREDIT_PULL_FAILED,
        APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING,
        APPLICATION_STATES.PREVERIFICATION_FAILED,
      ],
      LOAN_APPLICATION: [
        APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS,
        APPLICATION_STATES.SCORE_GENERATION_PENDING,
        APPLICATION_STATES.CREDIT_OFFER_PENDING,
        ...(isPreceedingState(
          this.applicationStatus,
          APPLICATION_STATES.CREDIT_OFFER_GENERATED,
          true,
        )
          ? [
              APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS,
              APPLICATION_STATES.SCORE_GENERATION_PENDING,
              APPLICATION_STATES.CREDIT_OFFER_PENDING,
            ]
          : []),
        APPLICATION_STATES.CREDIT_OFFER_GENERATED,
      ],
      DOCUMENT_COLLECTION: [
        // APPLICATION_STATES.SLOT_SELECTION_PENDING,
        // APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED,
        APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING,
        // APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED,
      ],
      FINAL_REVIEW: [
        ...(isPreceedingState(this.applicationStatus, APPLICATION_STATES.RZP_APPROVED, true)
          ? [APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW]
          : []),
        APPLICATION_STATES.RZP_APPROVED,
      ],
      FUND_DISBURSED: [APPLICATION_STATES.CREDIT_DISBURSED],
    };
  }

  getCompletedStateGroupDescriptions() {
    return {
      [CONSOLIDATED_STATES.CHECK_LOAN_ELIGIBILITY]: {
        title: 'Check loan eligibility',
        description: 'Loan eligibility check is successful and loan offer has been calculated',
      },
      [CONSOLIDATED_STATES.LOAN_APPLICATION]: {
        title: 'Complete loan application',
        description: 'You have successfully completed the Loan application form',
      },
      [CONSOLIDATED_STATES.DOCUMENT_COLLECTION]: {
        title: 'Document received',
        description: 'We have received the required documents shared by you.',
      },
      [CONSOLIDATED_STATES.FINAL_REVIEW]: {
        title: 'Loan offer is Approved!',
        description: 'We’ve successfully reviewed documents and approved your loan offer',
      },
      [CONSOLIDATED_STATES.FUND_DISBURSED]: {
        title: 'Fund Disbursed!',
        description: 'The loan amount has been successfully disbursed to your bank account.',
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
