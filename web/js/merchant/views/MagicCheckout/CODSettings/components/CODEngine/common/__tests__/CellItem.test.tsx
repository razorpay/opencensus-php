import {
  actions,
  productCount,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/cellItem';

const mockedItem = {
  is_default: true,
  items: ['item1', 'item2'],
};
describe('testing cellItems', () => {
  test('should return proper product count', () => {
    let count = productCount.value({ ...mockedItem, is_default: false });

    expect(count).toBe(2);

    count = productCount.value(mockedItem);

    expect(count).toBeNull();
  });

  test('should not return the action icons if is_default is true', () => {
    const dummyFn = {
      onDeleteClick: jest.fn(),
      onEditClick: jest.fn(),
    };
    const element = actions(dummyFn).value(mockedItem);

    expect(element).toBeNull();
  });
});
