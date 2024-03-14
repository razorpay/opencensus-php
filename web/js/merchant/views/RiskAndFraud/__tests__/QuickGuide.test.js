import * as quickGuideUtils from 'merchant/components/QuickGuide/utils';
import QuickGuide from 'merchant/views/RiskAndFraud/QuickGuide';
import { render, screen, userEvent } from 'test-utils';

jest.mock('merchant/views/RiskAndFraud/QuickGuide/QuickGuideStep', () => ({
  __esModule: true,
  default: ({ title, onCloseClick }) => (
    <div onClick={onCloseClick} data-testid="quick-step-guide">
      {title}
    </div>
  ),
}));

const renderApp = (props = {}) => {
  render(<QuickGuide {...props} />);
};

describe('Tests for QuickGuide component - RiskVisibility', () => {
  test('Should render QuickGuideStep component', () => {
    renderApp();

    expect(screen.getByText('Understanding Frauds and Disputes')).toBeInTheDocument();
  });

  test('Should call appropriate methods onCloseClick function call', async () => {
    const setQuickGuideIsClosedInLocalStorageSpy = jest.spyOn(
      quickGuideUtils,
      'setQuickGuideIsClosedInLocalStorage',
    );
    renderApp();

    const closeButton = screen.getByTestId('quick-step-guide');
    await userEvent.click(closeButton);

    expect(setQuickGuideIsClosedInLocalStorageSpy).toHaveBeenCalledWith('risk_and_fraud');
  });
});
