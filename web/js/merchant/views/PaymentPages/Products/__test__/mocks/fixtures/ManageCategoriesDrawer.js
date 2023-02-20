import ManageCategoriesDrawer from 'merchant/views/PaymentPages/Products/ManageCategoriesDrawer';

export const defaultCategories = [
  {
    id: 'cat_LAJ293qxKHwLOA',
    name: 'fruits',
    alias: 'fruits',
    catalog_count: 1,
  },
  {
    id: 'cat_LAIx1NQgKlCz6s',
    name: 'vegetables',
    alias: 'vegetables',
    catalog_count: 2,
  },
  {
    id: 'cat_LAGhEy6fnrGlmc',
    name: 'exotic fruits',
    alias: 'exotic_fruits',
    catalog_count: 3,
  },
];

const defaultProps = {
  openModal: jest.fn(),
  closeModal: jest.fn(),
  showNotification: jest.fn(),
  onDeleteSuccess: jest.fn(),
  onEditSuccess: jest.fn(),
  categories: defaultCategories,
};

const App = (props) => {
  return <ManageCategoriesDrawer {...defaultProps} {...props} />;
};

export default App;
