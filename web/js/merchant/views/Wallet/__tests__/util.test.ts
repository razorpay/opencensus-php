import { getAppliedFilters } from 'merchant/views/Wallet/utils';

const filter1 = {
  filters: {
    id: 'LkivjZlD2eIq7Q',
    account_id: 'ajjss',
    reference_id: 'hshshs',
    contact: 'ODA3NjQwNjQ1MA==',
    from: 0,
    to: 0,
  },
  skip: 0,
  count: 25,
};

const filter2 = {
  filters: {
    id: 'LkivjZlD2eIq7Q',
    account_id: 'ajjss',
    reference_id: 'hshshs',
    contact: 'ODA3NjQwNjQ1MA==',
    from: 1691519400,
    to: 1692210599,
  },
  skip: 0,
  count: 25,
};

describe('utils', () => {
  describe('getAppliedFilter', () => {
    test('should provide a modified object of filters for filter1', () => {
      const filters = getAppliedFilters({
        filters: filter1.filters,
        skip: filter1.skip,
        count: filter1.count,
      });

      expect(filters).toStrictEqual({
        filters: [
          {
            key: 'id',
            op: 'eq',
            value: 'LkivjZlD2eIq7Q',
          },
          {
            key: 'account_id',
            op: 'eq',
            value: 'ajjss',
          },
          {
            key: 'reference_id',
            op: 'eq',
            value: 'hshshs',
          },
          {
            key: 'contact',
            op: 'eq',
            value: 'ODA3NjQwNjQ1MA==',
          },
        ],
        pagination: {
          limit: 25,
          skip: 0,
        },
      });
    });

    test('should provide a modified object of filters for filter2', () => {
      const filters = getAppliedFilters({
        filters: filter2.filters,
        skip: filter2.skip,
        count: filter2.count,
      });

      expect(filters).toStrictEqual({
        filters: [
          {
            key: 'id',
            op: 'eq',
            value: 'LkivjZlD2eIq7Q',
          },
          {
            key: 'account_id',
            op: 'eq',
            value: 'ajjss',
          },
          {
            key: 'reference_id',
            op: 'eq',
            value: 'hshshs',
          },
          {
            key: 'contact',
            op: 'eq',
            value: 'ODA3NjQwNjQ1MA==',
          },
        ],
        time_range: {
          from: 1691519400,
          to: 1692210599,
        },
        pagination: {
          limit: 25,
          skip: 0,
        },
      });
    });
  });
});
