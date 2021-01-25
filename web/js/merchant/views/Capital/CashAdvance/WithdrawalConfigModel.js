class WithdrawalConfig {
  constructor(withdrawalConfiguration) {
    this.withdrawalConfiguration = withdrawalConfiguration;
  }

  get withdrawableBalance() {
    const internalBalance =
      parseInt(this.withdrawalConfiguration.configuration.internal_credit_limit) -
      parseInt(this.withdrawalConfiguration.principal_outstanding_balance || 0);
    return internalBalance > 0 ? internalBalance : 0;
  }

  get repayableAmount() {
    const { interest } = this.withdrawalConfiguration.configuration;
    const startDay = moment();

    const selectedDate = moment(this.state.selectedDueDate).endOf('day');

    const diffDays = selectedDate.diff(startDay, 'days');

    const roi = parseInt(interest) / 100;

    const amount = {
      principle: parseInt(this.state.withdrawalAmount),
      interest: (diffDays * parseInt(this.state.withdrawalAmount) * roi) / 100,
      diffDays,
      roi,
    };

    return amount;
  }
}

export default WithdrawalConfig;
