import { makeCollectionReducer } from 'merchant/reducers/collection';
import { merchantFetch } from 'merchant/utils/ajax';
import moment from 'moment';

const API_LIST = 'API_LIST';

export const fetchApiList = (params) => {
  return {
    type: `${API_LIST}_FETCH`,
    payload: merchantFetch({
      url: 'developer_console/incoming/fetch/apis',
      method: 'post',
      data: {
        range: {
          from: moment(Number(params.from)).unix(),
          to: moment(Number(params.to)).unix(),
        },
      },
    }).then((res) => {
      return {
        data: {
          items: res.data.body.result.map((data) => ({
            key: `${data.method.toUpperCase()} ${data.uri}`,
            value: data.route_name,
          })),
        },
      };
    }),
  };
};

const apiListReducer = makeCollectionReducer(API_LIST);

export default apiListReducer;
