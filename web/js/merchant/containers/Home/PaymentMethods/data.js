import {getDefaultPaymentFilter} from 'rzp/utils/pokedex';

const paymentMethodsColumns = [
  'method', 'bank', 'issuer', 'network', 'wallet', 'type'
];

const getQuery = ({ startTime, endTime }) => ({
  filters: {
    default: [
      getDefaultPaymentFilter(startTime, endTime) 
    ],
  },
  aggregations: {
    agg: {
      agg_type: 'sum',
      details: {
        index: 'payments',
        column: 'base_amount',
        group_by: paymentMethodsColumns,
      },
    },
  },
});

export { getQuery, paymentMethodsColumns };
