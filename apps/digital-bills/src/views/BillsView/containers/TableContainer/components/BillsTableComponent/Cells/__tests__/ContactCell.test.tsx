import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import ContactCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/Cells/ContactCell';

describe('ContactCell', () => {
  test('should render the ContactCell component', () => {
    const { getByText } = renderWithWrappers(
      <ContactCell contact="1234567890" countryCode="+91" />,
    );
    expect(getByText('+91 1234567890')).toBeInTheDocument();
  });

  test('should render the ContactCell component with default country code', () => {
    const { getByText } = renderWithWrappers(
      <ContactCell contact="1234567890" countryCode={null} />,
    );
    expect(getByText('+91 1234567890')).toBeInTheDocument();
  });

  test('should render the ContactCell component with default value', () => {
    const { getByText } = renderWithWrappers(<ContactCell contact="" countryCode="" />);
    expect(getByText('-')).toBeInTheDocument();
  });
});
