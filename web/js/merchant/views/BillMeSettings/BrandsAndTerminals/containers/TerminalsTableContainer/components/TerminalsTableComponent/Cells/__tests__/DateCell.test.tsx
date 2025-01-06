import React from 'react';

import DateCell from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/components/TerminalsTableComponent/Cells/DateCell';
import { screen, render } from 'test-utils';

const App = ({ props }) => <DateCell {...props} />;

describe('DateCell', () => {
  test("should render Date in the 'DD/MM/YYYY' format", () => {
    const props = { time: '2024-02-08T18:29:59.000Z' };

    render(<App props={props} />);
    expect(screen.getByText('08/02/2024')).toBeInTheDocument();
  });

  test('should render empty string if invalid prop value is passed', () => {
    const props = { time: '' };

    render(<App props={props} />);
    expect(screen.getByText('-')).toBeInTheDocument();
  });
});
