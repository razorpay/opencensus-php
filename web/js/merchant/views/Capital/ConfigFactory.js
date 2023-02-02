import LowerGMVCashAdvanceConfigLoader from './loaders/LowerGMVCashAdvanceConfigLoader';
import GreaterGMVCashAdvanceConfigLoader from './loaders/GreateGMVCashAdvanceConfigLoader';
import LoansConfigLoader from './loaders/LoanConifgLoader';
import { CAPITAL_PRODUCT_CODES } from './Loans/constants';

export default class ConfigFactory {
  constructor(application, productName) {
    this.loanApplication = application;
    this.productName = productName;
    this.create = this.create.bind(this);
  }

  getLOCConfigLoader() {
    const { application_state_flow } = this.loanApplication;
    switch (application_state_flow) {
      case 'SKIP_UNDERWRITING':
        return new LowerGMVCashAdvanceConfigLoader(this.loanApplication);
      default:
        return new GreaterGMVCashAdvanceConfigLoader(this.loanApplication);
    }
  }

  getLoansConfigLoader() {
    const { application_state_flow } = this.loanApplication;
    switch (application_state_flow) {
      default:
        return new LoansConfigLoader(this.loanApplication);
    }
  }

  create() {
    switch (this.productName) {
      case CAPITAL_PRODUCT_CODES.LOC_EMI: // not being used for onboarding loc_emi but being used without null check so keeping same as CASH_ADVANCE
      case CAPITAL_PRODUCT_CODES.CASH_ADVANCE:
        return this.getLOCConfigLoader();
      case CAPITAL_PRODUCT_CODES.LOAN:
        return this.getLoansConfigLoader();
      default:
        return null;
    }
  }
}
