import moment from 'moment';
import GenericEntity from './GenericEntity';
import { groupCommissionListData, groupSingleDayCommissionData } from 'common/utils/pokedex';

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

  fetchSingleDayAggregateData = ({ from, queryType }) => {
    const to = Number(moment(from, 'X').endOf('day').format('X'));

    return this.fetchAggregate(from, to, queryType).then((response) => {
      if (response.success) {
        return {
          ...response,
          data: groupSingleDayCommissionData(response.data),
        };
      }
      return null;
    });
  };

  fetchDailyAggregateData = ({ from, to, queryType }) => {
    return this.fetchAggregate(from, to, queryType).then((response) => {
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

  fetchCommissionBalances = () => {
    return this.makeGenericAjaxCall({
      url: 'balances',
    });
  };
}
