import SelectConfig from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/SelectConfig';
import { fireEvent, render, screen, delay } from 'test-utils';

describe('SelectConfig', () => {
  const defaultProps = {
    onConfigChange: jest.fn(),
    configs: [],
    selectedConfig: undefined,
  };

  // option with normal description
  const reportsOption = {
    id: 'reports',
    name: 'Reports',
    description: 'This report provides a list of the settlement(s) in selected time range',
  };

  // option with short description
  const settlementOption = {
    id: 'settlements',
    name: 'Settlements',
    description:
      'This report provides a list of the settlement(s) in selected time range. It does not include details of the transactions that were settled. Details include settlement ID, date, UTR, and others.',
  };

  // option with no description
  const monthlyInvoiceOption = {
    id: 'invoice',
    name: 'Monthly Invoice',
  };

  const App = (props = {}) => <SelectConfig {...defaultProps} {...props} />;

  const triggerSelectConfigDropdown = async () => {
    // Show Report Type dropdown
    fireEvent.click(screen.getByText('Select Config'));
    // Delay is added to prevent onFocus error of react-power-select library - react-power-select needs to be upgraded
    await delay(10);
  };

  test('should render SelectConfig', () => {
    render(<App />);
    expect(screen.getByText('Select Report Type')).toBeInTheDocument();
    expect(screen.getByText('Select Config')).toBeInTheDocument();
  });

  test('should show all options and call onConfigChange prop on config selection', async () => {
    render(<App configs={[settlementOption, monthlyInvoiceOption, reportsOption]} />);
    await triggerSelectConfigDropdown();

    const settlementElement = screen.getByText(settlementOption.name);
    expect(settlementElement).toBeInTheDocument();
    expect(screen.getByText(monthlyInvoiceOption.name)).toBeInTheDocument();
    expect(screen.getByText(reportsOption.name)).toBeInTheDocument();
    // On selecting settlement option
    fireEvent.click(settlementElement);
    expect(defaultProps.onConfigChange).toHaveBeenCalledWith(settlementOption);
  });

  test('should show selected config name and full description', () => {
    render(<App configs={[settlementOption]} selectedConfig={settlementOption} />);
    expect(screen.getByText(settlementOption.name)).toBeInTheDocument();
    expect(screen.getByText(settlementOption.description)).toBeInTheDocument();
  });

  test('should check for null in onChange', async () => {
    render(<App />);
    await triggerSelectConfigDropdown();
    const searchTextElement = screen.getByPlaceholderText('Search...');
    fireEvent.change(searchTextElement, { target: { value: 'test' } });
    fireEvent.focus(searchTextElement);
    fireEvent.keyDown(searchTextElement, {
      key: 'Enter',
      code: 'Enter',
      keyCode: 13,
      charCode: 13,
    });
    expect(defaultProps.onConfigChange).toHaveBeenCalledWith(null);
  });

  test('should show message when selected config is invoice or monthly invoice report', () => {
    render(<App configs={[monthlyInvoiceOption]} selectedConfig={monthlyInvoiceOption} />);
    expect(
      screen.getByText(
        'The December invoice is for the billing cycle starting on Dec 01, 2020 to Dec 30, 2020 and is generated on Dec-31, 2020 due to new GST guidelines effective from 1st Jan 2020. The charges for Dec 31, 2020 will be added to the next billing cycle.',
      ),
    ).toBeInTheDocument();
  });

  test('should use empty array as default value in getConfigOptions when configs is undefined', async () => {
    const { container } = render(<App configs={undefined} />);
    await triggerSelectConfigDropdown();
    expect(container.querySelector('.PowerSelect__Option')).not.toBeInTheDocument();
  });
});
