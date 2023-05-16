import React from 'react';
import { getByText, render } from '@testing-library/react';
import { InfoComponent } from 'merchant/views/Wallet/BatchActions/components/InfoComponent';

describe('BatchUpload: infoComponent tests', () => {
  test('should render infoComponent as expected', () => {
    const points = ['Random 1', 'Random 2'];
    const { container } = render(
      <InfoComponent points={points} sampleUrl="/files/sample_batch_file.xlsx" />,
    );

    expect(getByText(container, points[0])).toBeInTheDocument();
    expect(getByText(container, points[1])).toBeInTheDocument();

    expect(getByText(container, 'sample file').closest('a')).toHaveAttribute(
      'href',
      '/files/sample_batch_file.xlsx',
    );
  });
});
