import LowerGMVCashAdvanceConfigLoader from './loaders/LowerGMVCashAdvanceConfigLoader';
import GreaterGMVCashAdvanceConfigLoader from './loaders/GreateGMVCashAdvanceConfigLoader';
import LoansConfigLoader from './loaders/LoanConifgLoader';

export default class ConfigFactory {
  constructor(application) {
    this.loanApplication = application;
    this.create = this.create.bind(this);
  }

  create() {
    const { flow } = this.loanApplication;
    switch (flow) {
      case 'cash_advance_gmv_less_than_600K':
        return new LowerGMVCashAdvanceConfigLoader(this.loanApplication);
      case 'cash_advance_gmv_greater_than_600K':
        return new GreaterGMVCashAdvanceConfigLoader(this.loanApplication);
      default:
        return new LoansConfigLoader(this.loanApplication);
    }
  }
}
