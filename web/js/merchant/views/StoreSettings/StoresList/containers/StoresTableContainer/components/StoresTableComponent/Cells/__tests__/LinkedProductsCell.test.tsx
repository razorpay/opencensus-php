import React from 'react';

import LinkedProductsCell from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/components/StoresTableComponent/Cells/LinkedProductsCell';
import { LINKED_PRODUCTS_MAP } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/constants';
import { screen, render } from 'test-utils';

const App = ({ props }) => <LinkedProductsCell {...props} />;

describe('LinkedProductsCell', () => {
  test('should render Linked Products label as expected for the passed in value', async () => {
    const props = { products: ['DIGITAL_BILLING'] };

    render(<App props={props} />);
    props.products.forEach((product) => {
      expect(screen.getByText(LINKED_PRODUCTS_MAP[product]?.label)).toBeInTheDocument();
    });
  });
});
