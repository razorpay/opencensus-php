import ManageCategoriesDrawer, {
  defaultCategories,
} from 'merchant/views/PaymentPages/Products/__test__/mocks/fixtures/ManageCategoriesDrawer';

import { render, screen, userEvent } from 'test-utils';

import * as modals from 'merchant_common/reducers/modals';

describe('Products Container', () => {
  const modalsSpy = jest.spyOn(modals, 'openModal');

  beforeEach(() => {
    modalsSpy.mockClear();
  });

  test('should have the necessary content rendered in the component', () => {
    render(<ManageCategoriesDrawer />);

    expect(screen.getByText('Manage Categories')).toBeVisible();

    // checking if all the categories name and number of products is printed
    for (const category of defaultCategories) {
      expect(screen.getByText(category.name)).toBeVisible();
      expect(screen.getByText(new RegExp(`${category.catalog_count} product`))).toBeVisible();
    }

    // checking if edit and delete buttons are rendered
    expect(screen.getAllByText('Edit')).toHaveLength(defaultCategories.length);
    expect(screen.getAllByText('Delete')).toHaveLength(defaultCategories.length);
  });

  test('should show appropriate message when no categories present', () => {
    render(<ManageCategoriesDrawer categories={[]} />);

    expect(screen.getByText('No categories created yet'));
  });

  test('should open confirm modal on the click of delete', async () => {
    render(<ManageCategoriesDrawer />);

    await userEvent.click(screen.getAllByText('Delete')[0]);

    expect(modalsSpy).toBeCalledTimes(1);
  });

  test('should open edit category drawer on the click of edit', async () => {
    render(<ManageCategoriesDrawer />);

    await userEvent.click(screen.getAllByText('Edit')[0]);

    expect(modalsSpy).toBeCalledTimes(1);
  });
});
