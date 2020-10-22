import {
  APPLICATION_STATE_DESCRIPTIONS,
  CONSOLIDATED_STATES,
  SIDE_NAVIGATION_STATE_GROUPS,
  APPLICATION_STATE_GROUPS,
  STATE_GROUP_COMPLETION_DESCRIPTION,
} from '../Loans/constants';
import BaseConfigLoader from './BaseConfigLoader';

export default class LoansConfigLoader extends BaseConfigLoader {
  constructor(loanApplication) {
    super(loanApplication);
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
    return SIDE_NAVIGATION_STATE_GROUPS;
  }

  getApplicationStateGroups() {
    return APPLICATION_STATE_GROUPS;
  }

  getCompletedStateGroupDescriptions() {
    return STATE_GROUP_COMPLETION_DESCRIPTION;
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
