import React from 'react';

import DualLineInfoCell from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/components/TerminalsTableComponent/Cells/DualLineInfoCell';
import { screen, render } from 'test-utils';

const App = ({ props }) => <DualLineInfoCell {...props} />;

describe('DualLineInfoCell', () => {
  test("should render 'line1' and 'line2' props content", async () => {
    const props = {
      line1: 'DualLineInfoCell Line 1 content',
      line2: 'DualLineInfoCell Line 2 content',
    };

    render(<App props={props} />);
    expect(screen.getByText('DualLineInfoCell Line 1 content')).toBeInTheDocument();
    const line2Element = screen.getByText('DualLineInfoCell Line 2 content');
    expect(line2Element).toBeInTheDocument();

    // 'line2' prop content text color validation
    expect(line2Element).toHaveStyle('color: hsla(211,22%,56%,1)');
  });

  test("should render 'line1' and 'line2' with default value when invalid prop values are passed", () => {
    const props = {
      line1: '',
      line2: null,
    };

    render(<App props={props} />);
    expect(screen.getAllByText('-')).toHaveLength(2);
  });
});
