import GenericEntity from './GenericEntity';
import { getDefaultFilter, groupCommissionsData } from 'rzp/utils/pokedex';

import { fetch } from 'merchant/modules/pokedex';

export default class Commission extends GenericEntity {
  resourceUrl = 'commissions';

  fetchAggregateData = ({ from = 0, to = 1554904781, mode = 'test' }) => {
    const query = {
      filters: {
        default: [getDefaultFilter(from, to)],
      },
      aggregations: {
        earnings: buildQuery('commission', 'sum'),
        activeMerchants: buildQuery('payments_merchant_id', 'count'),
        transactionVolume: buildQuery('payments_base_amount', 'sum'),
        transactions: buildQuery('id', 'count'),
      },
    };

    return fetch(query, mode).then(response => {
      if (response.success) {
        return {
          ...response,
          data: {
            items: groupCommissionsData(response.data),
          },
        };
      }
      return response;
    });
  };
}

function buildQuery(column, aggType) {
  return {
    agg_type: aggType,
    details: {
      index: 'commissions',
      column,
      group_by: ['histogram_daily'],
    },
  };
}
