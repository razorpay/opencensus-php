import GenericEntity from './GenericEntity';
import {
  groupCommissionListData,
  groupSingleDayCommissionData,
} from 'rzp/utils/pokedex';

export default class Commission extends GenericEntity {
  resourceUrl = 'commissions';

  fetchAggregate = (from, to, queryType) => {
    const params = { from, to, query_type: queryType };
    return this.makeGenericAjaxCall({
      url: 'commissions_analytics',
      params,
      data: params,
    });
  };

  fetchSingleDayAggregateData = ({ from }) => {
    const to = Number(
      moment(from, 'X')
        .endOf('day')
        .format('X')
    );

    return this.fetchAggregate(from, to, 'aggregate_detail').then(response => {
      if (response.success) {
        return {
          ...response,
          data: groupSingleDayCommissionData(response.data),
        };
      }
    });
  };

  fetchDailyAggregateData = ({ from, to }) => {
    return this.fetchAggregate(from, to, 'aggregate_daily').then(response => {
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
