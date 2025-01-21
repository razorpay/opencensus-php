import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import Avatar from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/Avatar';

describe('Avatar', () => {
  test('should render the Avatar component', async () => {
    const { getByRole } = renderWithWrappers(
      <Avatar imageSrc="test_brand_logo_url" imageAlt="Avatar Alt Text" />,
    );
    const img = getByRole('img');
    expect(img).toBeInTheDocument();
    expect(img).toHaveAttribute('src', 'test_brand_logo_url');
    expect(img).toHaveAttribute('alt', 'Avatar Alt Text');
  });
});
