import { render, screen, userEvent } from 'test-utils';
import RiskCategoryDropdown from 'merchant/views/MagicCheckout/PartialCOD/components/RiskCategoryDropdown';

describe('RiskCategoryDropdown', () => {
  const mockOnChange = jest.fn();
  const setup = (value, validationState = 'none') =>
    render(
      <RiskCategoryDropdown
        value={value}
        onChange={mockOnChange}
        label="Risk Categories"
        validationState={validationState}
      />,
    );

  afterEach(() => {
    jest.clearAllMocks();
  });

  it('should render the dropdown with provided label', () => {
    setup([]);
    const dropdownLabel = screen.getByText('Risk Categories');
    expect(dropdownLabel).toBeInTheDocument();
  });

  it('should display the correct placeholder', () => {
    setup([]);
    const selectInput = screen.getByPlaceholderText('Select Risk Categories');
    expect(selectInput).toBeInTheDocument();
  });

  it('should call onChange when risk categories are selected', async () => {
    setup([]);
    const user = userEvent.setup();

    const selectInput = screen.getByPlaceholderText('Select Risk Categories');
    await user.click(selectInput);

    const lowRiskOption = screen.getByText('Low Risk Buyers');
    await user.click(lowRiskOption);

    expect(mockOnChange).toHaveBeenCalledWith(['low']);

    await user.click(selectInput);
    const mediumRiskOption = screen.getByText('Medium Risk Buyers');
    await user.click(mediumRiskOption);

    expect(mockOnChange).toHaveBeenCalledWith(['low', 'medium']);

    await user.click(selectInput);
    const highRiskOption = screen.getByText('High Risk Buyers');
    await user.click(highRiskOption);

    expect(mockOnChange).toHaveBeenCalledWith(['low', 'medium', 'high']);
  });

  it('should include all categories if "Include All Buyers" is selected', async () => {
    setup([]);
    const user = userEvent.setup();

    const selectInput = screen.getByPlaceholderText('Select Risk Categories');
    await user.click(selectInput);

    const allBuyersOption = screen.getByText('Include All Buyers');
    await user.click(allBuyersOption);

    expect(mockOnChange).toHaveBeenCalledWith(['low', 'medium', 'high']);
  });

  it('should clear all selections if "Include All Buyers" is deselected', async () => {
    setup(['low', 'medium', 'high']);
    const user = userEvent.setup();

    const selectInput = screen.getByText('Include All Buyers');
    await user.click(selectInput);

    const allBuyersOption = screen.getByText('Include All Buyers');
    await user.click(allBuyersOption); // Deselect

    expect(mockOnChange).toHaveBeenCalledWith([]);
  });

  // show error states
  it('should display the correct error message', () => {
    setup([], 'error');

    const errorMessage = screen.getByText('Select at least one risk category');
    expect(errorMessage).toBeInTheDocument();
  });
});
