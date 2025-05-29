import GenericEntity from './GenericEntity';
import { getFixedINRAmount } from 'common/utils/rzp-utils';
import { getIsOdsMigrationEnabled } from 'merchant/views/Settlements/InstantSettlements/utils/common';
import { getUser } from 'merchant/store';
export default class InstantSettlement extends GenericEntity {
  constructor(...args) {
    super(...args);
    const user = getUser();
    this.isOdsMigrationEnabled = getIsOdsMigrationEnabled(user);

    if (this.isOdsMigrationEnabled) {
      this.resourceUrl = 'capital_es/service/instant_settlements/ondemand/multiple';
    } else {
      this.resourceUrl = 'settlements/ondemand';
    }
  }

  deserializeProperty(prop, value) {
    if (prop === 'amount') {
      this.amountInINR = getFixedINRAmount(value);
    }
    return super.deserializeProperty(prop, value);
  }

  fetch(id, data = {}, queryParams = {}) {
    const Klass = this.constructor;

    if (this.isOdsMigrationEnabled) {
      const url = `capital_es/service/instant_settlements/ondemand/${id}`;
      const mergedData = { ...data, ...queryParams };

      return this.makeGenericAjaxCall({ url, data: mergedData })
        .then((response) => {
          return new Klass(response.data).deserialize();
        })
        .catch((error) => {
          console.error('Error fetching instant settlement:', error);
        });
    }

    return super.fetch(id, data, queryParams);
  }
}
