import {
  entitySearch,
  transformEntitySearchResults,
  makeQuery,
  getDefaultDateRangeForPayments,
} from 'merchant/components/HeaderNav/UniversalSearch/utils/EntitySearch';
import { searchableEntities } from 'merchant/components/HeaderNav/UniversalSearch/configs';
import {
  SearchableEntityType,
  SearchableEntities,
} from 'merchant/components/HeaderNav/UniversalSearch/typings';

describe('Entity search util', () => {
  test('Valid entity id as search query', () => {
    const searchResults = entitySearch('pay_');
    const validSearchResults: SearchableEntities[] = ['Payments', 'Refunds', 'Disputes'];

    expect(searchResults.success).toBe(true);
    searchResults.results.forEach((result) => {
      const isEntityFound = validSearchResults.includes(result.item.id as SearchableEntities);
      expect(isEntityFound).toBe(true);
    });
    expect(searchResults.results).toHaveLength(3);
  });

  test('Valid email as search query', () => {
    const searchResults = entitySearch('akash.raina@razorpay.com');
    const validSearchResults: SearchableEntities[] = ['Payments'];

    expect(searchResults.success).toBe(true);
    searchResults.results.forEach((result) => {
      const isEntityFound = validSearchResults.includes(result.item.id as SearchableEntities);
      expect(isEntityFound).toBe(true);
    });
    expect(searchResults.results).toHaveLength(1);
  });

  test('Valid entity status as search query', () => {
    const searchResults = entitySearch('created');
    const validSearchResults: SearchableEntities[] = ['Orders', 'Settlements'];

    expect(searchResults.success).toBe(true);
    searchResults.results.forEach((result) => {
      const isEntityFound = validSearchResults.includes(result.item.id as SearchableEntities);
      expect(isEntityFound).toBe(true);
    });
    expect(searchResults.results).toHaveLength(2);
  });

  test(`Search query can't be identified`, () => {
    const searchResults = entitySearch('testing 124');
    const validSearchResults: SearchableEntities[] = [
      'Orders',
      'Settlements',
      'Payments',
      'Refunds',
      'Disputes',
    ];

    expect(searchResults.success).toBe(false);
    searchResults.results.forEach((result) => {
      const isEntityFound = validSearchResults.includes(result.item.id as SearchableEntities);
      expect(isEntityFound).toBe(true);
    });
    expect(searchResults.results).toHaveLength(Object.keys(searchableEntities).length);
  });
});

describe('Transform search results util', () => {
  test('Valid entity id as search query', () => {
    const transformedSearchResults = transformEntitySearchResults(
      'pay_',
      ['Payments', 'Orders', 'Disputes'],
      ['payment_id'],
      'entity_id',
    );
    const validSearchResults: SearchableEntities[] = ['Payments', 'Orders', 'Disputes'];

    expect(transformedSearchResults).toHaveLength(3);
    transformedSearchResults.forEach((result) => {
      const isEntityFound = validSearchResults.includes(result.item.id as SearchableEntities);
      expect(isEntityFound).toBe(true);
    });
  });

  test('Valid email as search query', () => {
    const transformedSearchResults = transformEntitySearchResults(
      'akash.raina@razorpay.com',
      ['Payments'],
      ['email_id'],
      'entity_email',
    );
    const validSearchResults: SearchableEntities[] = ['Payments'];
    transformedSearchResults.forEach((result) => {
      const isEntityFound = validSearchResults.includes(result.item.id as SearchableEntities);
      expect(isEntityFound).toBe(true);
    });
    expect(transformedSearchResults).toHaveLength(1);
  });

  test(`Search query can't be identified`, () => {
    const transformedSearchResults = transformEntitySearchResults('xyzfsfd', [], [], '');
    const validSearchResults: SearchableEntities[] = [
      'Payments',
      'Disputes',
      'Orders',
      'Refunds',
      'Settlements',
    ];

    expect(transformedSearchResults).toHaveLength(Object.keys(searchableEntities).length);
    transformedSearchResults.forEach((result) => {
      const isEntityFound = validSearchResults.includes(result.item.id as SearchableEntities);
      expect(isEntityFound).toBe(true);
    });
  });
});

describe('Make query util', () => {
  test('Valid entity id as search query', () => {
    const searchKey = 'pay_';

    const entity: SearchableEntityType = {
      id: 'Payments',
      route: '/payments',
      icon: 'i-repeat',
      attributes: {
        payment_id: 'id',
        order_id: 'order_id',
        email_id: 'email',
        ph_number: 'contact',
        payment_status: 'status',
      },
    };
    const query = makeQuery(entity, searchKey, ['payment_id'], 'entity_id');
    const { to, from } = getDefaultDateRangeForPayments();

    expect(query).toBe(
      `${entity.route}?${entity.attributes.payment_id}=${searchKey}&from=${from}&to=${to}`,
    );
  });

  test(`Search query can't be identified`, () => {
    const searchKey = 'random text';

    const entity: SearchableEntityType = {
      id: 'Payments',
      route: '/payments',
      icon: 'i-repeat',
      attributes: {
        payment_id: 'id',
        order_id: 'order_id',
        email_id: 'email',
        ph_number: 'contact',
        payment_status: 'status',
      },
    };
    const query = makeQuery(entity, searchKey, [], '');

    expect(query).toBe(`${entity.route}?q=${searchKey}`);
  });
});
