import { APPLICATION_STATES, CONSOLIDATED_STATES } from '../Loans/constants';
import LowerGMVCashAdvanceConfigLoader from './LowerGMVCashAdvanceConfigLoader';

export default class GreaterGMVCashAdvanceConfigLoader extends LowerGMVCashAdvanceConfigLoader {
  constructor(loanApplication) {
    super(loanApplication);
    this.loanApplication = loanApplication;
  }

  getApplicationStateGroups() {
    const baseApplicationStateGroups = super.getApplicationStateGroups();
    return {
      ...baseApplicationStateGroups,
      CHECK_LOAN_ELIGIBILITY: [
        ...baseApplicationStateGroups.CHECK_LOAN_ELIGIBILITY,
        APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING,
        APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS,
        APPLICATION_STATES.PREVERIFICATION_FAILED,
        APPLICATION_STATES.CREDIT_OFFER_PENDING,
      ],
    };
  }

  getSideNavigationStateGroups() {
    const baseSideNavigationStateGroups = super.getSideNavigationStateGroups();
    return {
      ...baseSideNavigationStateGroups,
      [CONSOLIDATED_STATES.CHECK_LOAN_ELIGIBILITY]: {
        ...baseSideNavigationStateGroups[CONSOLIDATED_STATES.CHECK_LOAN_ELIGIBILITY],
        steps: {
          ...baseSideNavigationStateGroups[CONSOLIDATED_STATES.CHECK_LOAN_ELIGIBILITY].steps,
          [APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING]: [
            APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING,
            APPLICATION_STATES.PREVERIFICATION_FAILED,
          ],
          [APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS]: [
            APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS,
            APPLICATION_STATES.SCORE_GENERATION_PENDING,
            APPLICATION_STATES.CREDIT_OFFER_PENDING,
          ],
        },
      },
    };
  }
}
