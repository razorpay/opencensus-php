import GenericEntity from 'merchant/models/GenericEntity';
import { merchantFetch } from 'merchant/utils/ajax';

export default class LoanOriginationEntity extends GenericEntity {
  request = (url, data, progressTracker) => {
    return merchantFetch({
      url,
      mode: 'live',
      method: 'post',
      data,
      headers: {
        'Content-Type': 'application/json',
      },
      onUploadProgress: progressTracker,
    });
  };

  resourceUrlPrefix = (domain, entity, endpoint) =>
    `loc/service/twirp/rzp.capital.loc.${domain}.v1.${entity}/${endpoint}`;

  fetchSeedData() {
    return this.request(`${this.resourceUrlPrefix('withdrawal', 'WithdrawalAPI', 'SeedData')}`, {});
  }

  fetchFunctionalWithdrawalConfigByMerchantID(data) {
    return this.request(
      this.resourceUrlPrefix('withdrawal', 'WithdrawalConfigAPI', 'GetFunctionalWithdrawalConfig'),
      data,
    ).then((res) => {
      if (
        res &&
        !res.errors &&
        res.data &&
        res.data.withdrawal_config &&
        res.data.withdrawal_config.length > 0
      ) {
        return {
          data: {
            withdrawal_config: res.data.withdrawal_config[0],
          },
        };
      }
      return res;
    });
  }

  fetchWithdrawalConfiguration(data) {
    return this.request(
      this.resourceUrlPrefix('withdrawal', 'WithdrawalConfigAPI', 'GetWithdrawalConfig'),
      data,
    );
  }

  fetchWithdrawalRepaymentSummary(data) {
    return this.request(
      this.resourceUrlPrefix('withdrawal', 'WithdrawalAPI', 'GetWithdrawalRepaymentSummary'),
      data,
    );
  }

  createWithdrawal(data) {
    return this.request(
      this.resourceUrlPrefix('withdrawal', 'WithdrawalAPI', 'CreateWithdrawal'),
      data,
    );
  }

  fetchWithdrawalDetails(data) {
    return this.request(
      this.resourceUrlPrefix('withdrawal', 'WithdrawalAPI', 'GetWithdrawalByReference'),
      data,
    );
  }

  fetchWithdrawals(data) {
    return this.request(
      `${this.resourceUrlPrefix('withdrawal', 'WithdrawalAPI', 'ListOrSearchWithdrawal')}`,
      data,
    );
  }

  fetchDestinationAccountDetails(data) {
    return this.request(
      `${this.resourceUrlPrefix('defrayment', 'DestinationAccountsAPI', 'GetDestinationAccount')}`,
      data,
    );
  }

  fetchAutomatedLOCConfig(data) {
    return this.request(
      `${this.resourceUrlPrefix('withdrawal', 'WithdrawalConfigAPI', 'GetAutomatedLOC')}`,
      data,
    );
  }

  fetchRepayments(data) {
    return this.request(
      `${this.resourceUrlPrefix('withdrawal', 'RepaymentAPI', 'GetRepayments')}`,
      data,
    );
  }

  updateAutomatedLOCConfig(data) {
    return this.request(
      `${this.resourceUrlPrefix('withdrawal', 'WithdrawalConfigAPI', 'SetAutomatedLOC')}`,
      data,
    );
  }
  fetchInstallments(data) {
    return this.request(
      `${this.resourceUrlPrefix('withdrawal', 'RepaymentAPI', 'GetRepaymentsSchedule')}`,
      data,
    );
  }
  fetchCurrentOutstanding(data) {
    return this.request(
      `${this.resourceUrlPrefix('withdrawal', 'RepaymentAPI', 'GetRepaymentsSchedule')}`,
      data,
    );
  }

  fetchCreditSummary(data) {
    return this.request(
      `${this.resourceUrlPrefix('withdrawal', 'WithdrawalConfigAPI', 'GetCreditSummary')}`,
      data,
    );
  }

  setMerchantPreferences(data) {
    return this.request(
      this.resourceUrlPrefix('withdrawal', 'WithdrawalConfigAPI', 'SetMerchantPreferences'),
      data,
    );
  }
}
