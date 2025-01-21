import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import BillPreviewContainer from '@apps/digital-bills/src/views/BillDetails/containers/BillPreviewContainer/BillPreviewContainer';

describe('BillPreviewContainer', () => {
  test('should render the BillPreviewContainer component', () => {
    const { getByText } = renderWithWrappers(<BillPreviewContainer id="1234" />);
    expect(getByText('Bill Preview')).toBeInTheDocument();
  });
});
