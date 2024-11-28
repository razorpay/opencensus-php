import React from 'react';
import { render, screen } from 'test-utils';

import { IciciNetbankingPoints } from 'merchant/views/Navigator/components/Provider/SeamlessComponents/IciciNetbankingPoints';

describe('GetSimplPoints component', () => {
  test('should render GetSimplPoints without any errors', () => {
    expect(() => render(<IciciNetbankingPoints />)).not.toThrowError();
  });

  test('should render the points for Simpl gateway', () => {
    render(<IciciNetbankingPoints />);

    expect(
      screen.getByText(
        'Reach out to your relationship or account manager to get your integration kit with credentials for a direct settlement with refunds type terminal',
      ),
    ).toBeInTheDocument();
  });
});
