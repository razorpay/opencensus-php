import GenericEntity from './GenericEntity';
import {
  getDefaultFilter,
  groupCommissionListData,
  groupSingleDayCommissionData,
} from 'rzp/utils/pokedex';

import { fetch } from 'merchant/modules/pokedex';

export default class Commission extends GenericEntity {
  resourceUrl = 'commissions';

  fetchSingleDayAggregateData = ({ from, mode }) => {
    const to = Number(
      moment(from, 'X')
        .endOf('day')
        .format('X')
    );
    const query = {
      filters: {
        ...buildDefaultFilter(from, to),
        ...buildWithTypeFilter(from, to, 'implicit'),
        ...buildWithTypeFilter(from, to, 'explicit'),
      },
      aggregations: {
        ...buildCommonAggregations(),
        ...buildSingleDayAggregations(),
      },
    };

    return fetch(query).then(response => {
      if (response.success) {
        return {
          ...response,
          data: groupSingleDayCommissionData(response.data),
        };
      }
    });
  };

  fetchAggregateData = ({ from, to }) => {
    const query = {
      filters: {
        ...buildDefaultFilter(from, to),
      },
      aggregations: {
        ...buildCommonAggregations(),
        ...buildListAggregations(),
      },
    };

    return fetch(query).then(response => {
      if (response.success) {
        return {
          ...response,
          data: {
            items: groupCommissionListData(response.data),
          },
        };
      }
      return response;
    });
  };
}

function buildDefaultFilter(from, to) {
  return {
    default: [getDefaultFilter(from, to)],
  };
}

function buildWithTypeFilter(from, to, type) {
  return {
    [type]: [
      {
        ...getDefaultFilter(from, to),
        type,
      },
    ],
  };
}

function buildSingleDayAggregations() {
  return {
    // base earning components
    baseEarnings: buildAggregation('commission', 'sum', 'implicit'),
    baseTax: buildAggregation('tax', 'sum', 'implicit'),
    // addon Components
    addonEarnings: buildAggregation('commission', 'sum', 'explicit'),
    addonTax: buildAggregation('tax', 'sum', 'explicit'),
  };
}

function buildListAggregations() {
  return {
    earnings: buildAggregation('commission', 'sum'),
  };
}

function buildCommonAggregations() {
  return {
    activeMerchants: buildAggregation('payments_merchant_id', 'cardinality'),
    transactionVolume: buildAggregation('payments_base_amount', 'sum'),
    transactions: buildAggregation('id', 'count'),
  };
}

function buildAggregation(column, aggType, filter_key) {
  return {
    agg_type: aggType,
    filter_key,
    details: {
      index: 'commissions',
      column,
      group_by: ['histogram_daily'],
    },
  };
}
