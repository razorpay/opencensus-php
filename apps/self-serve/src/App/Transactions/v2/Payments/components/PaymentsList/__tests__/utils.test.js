import { createCustomColumnView } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsList/utils';
import { COLUMNS } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsList/constants';
import {
  customerDetail,
  generateDynamicComponent,
} from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsTable/columns';

jest.mock(
  'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsTable/columns',
  () => ({
    customerDetail: { component: 'CustomerDetailComponent' },
    generateDynamicComponent: jest.fn((name) => ({ component: `DynamicComponent-${name}` })),
  }),
);

describe('createCustomColumnView', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('should return only fixed columns when selectedColumnsList is empty', () => {
    const cols = [
      { title: { props: { children: 'FixedColumn1' } } },
      { title: { props: { children: 'FixedColumn2' } } },
    ];
    const selectedColumnsList = [];

    const result = createCustomColumnView(cols, selectedColumnsList);

    expect(result).toEqual(cols);
  });

  it('should add customerDetail as the first column if COLUMNS.CUSTOMER_DETAIL is in selectedColumnsList', () => {
    const cols = [
      { title: { props: { children: 'FixedColumn1' } } },
      { title: { props: { children: 'FixedColumn2' } } },
    ];
    const selectedColumnsList = [COLUMNS.CUSTOMER_DETAIL, 'OtherColumn'];

    const result = createCustomColumnView(cols, selectedColumnsList);

    expect(result).toEqual([
      { title: { props: { children: 'FixedColumn1' } } },
      { title: { props: { children: 'FixedColumn2' } } },
      customerDetail,
      { component: 'DynamicComponent-OtherColumn' },
    ]);
    expect(generateDynamicComponent).toHaveBeenCalledWith('OtherColumn');
  });

  it('should add dynamic components for each column in selectedColumnsList', () => {
    const cols = [{ title: { props: { children: 'FixedColumn1' } } }];
    const selectedColumnsList = ['Column1', 'Column2'];

    const result = createCustomColumnView(cols, selectedColumnsList);

    expect(result).toEqual([
      { title: { props: { children: 'FixedColumn1' } } },
      { component: 'DynamicComponent-Column1' },
      { component: 'DynamicComponent-Column2' },
    ]);
    expect(generateDynamicComponent).toHaveBeenCalledWith('Column1');
    expect(generateDynamicComponent).toHaveBeenCalledWith('Column2');
  });

  it('should place customerDetail at the beginning of the selected columns list if present', () => {
    const cols = [{ title: { props: { children: 'FixedColumn1' } } }];
    const selectedColumnsList = ['Column1', COLUMNS.CUSTOMER_DETAIL, 'Column2'];

    const result = createCustomColumnView(cols, selectedColumnsList);

    expect(result).toEqual([
      { title: { props: { children: 'FixedColumn1' } } },
      customerDetail,
      { component: 'DynamicComponent-Column1' },
      { component: 'DynamicComponent-Column2' },
    ]);
  });

  it('should return an empty array when both cols and selectedColumnsList are empty', () => {
    const result = createCustomColumnView([], []);

    expect(result).toEqual([]);
  });

  it('should exclude columns with CUSTOMER_DETAIL from fixed columns', () => {
    const cols = [
      { title: { props: { children: COLUMNS.CUSTOMER_DETAIL } } },
      { title: { props: { children: 'FixedColumn1' } } },
    ];
    const selectedColumnsList = ['Column1'];

    const result = createCustomColumnView(cols, selectedColumnsList);

    expect(result).toEqual([
      { title: { props: { children: 'FixedColumn1' } } },
      { component: 'DynamicComponent-Column1' },
    ]);
  });
});
