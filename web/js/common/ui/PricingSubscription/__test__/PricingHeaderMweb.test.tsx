import React from 'react';
import PricingHeaderMweb, {
  ToggleSwitch,
} from 'common/ui/PricingSubscription/Mobile/PricingHeaderMweb';
import { render, screen, userEvent } from 'test-utils';

const pricingHeaderProps = {
  togglePlan: 'Monthly',
  isChecked: false,
  pillText: '\ud83d\udd25 16% OFF',
  title: 'Pay for just 10 Months, use for a Year by switching to Annual Plan!',
  handlePlanSwitch: () => {},
};
const toggleSwitchProps = {
  isChecked: false,
  togglePlan: 'Monthly',
  handlePlanSwitch: () => {},
};
describe('Tests for `PricingHeaderMweb` components', () => {
  const renderPricingHeaderApp = () => render(<PricingHeaderMweb {...pricingHeaderProps} />);
  const renderToggleSwitch = () => render(<ToggleSwitch {...toggleSwitchProps} />);

  test('`Data from props`: Should show the correct information in PricingHeaderMweb', () => {
    renderPricingHeaderApp();
    const title = screen.getByTestId('title');
    const staticBadge = screen.getByTestId('staticBadge');
    const switchContainer = screen.getByTestId('switchContainer');
    const dynamicBadge = screen.getByTestId('dynamicBadge');

    expect(title).toBeInTheDocument();
    expect(staticBadge).toBeInTheDocument();
    expect(switchContainer).toBeInTheDocument();
    expect(dynamicBadge).toBeInTheDocument();
    expect(screen.getByText(pricingHeaderProps.title)).toBeInTheDocument();
    expect(screen.getByText(pricingHeaderProps.pillText)).toBeInTheDocument();
    expect(screen.getByText('NEW PRICING PLANS')).toBeInTheDocument();
  });

  test('`Data from props`: Should show the correct information in Toggle Switch', () => {
    renderToggleSwitch();
    const toggleInput = screen.getByTestId('toggleInput') as HTMLInputElement;
    userEvent.click(toggleInput);
    expect(toggleInput.checked).toEqual(false);

    const switchInput = screen.getByTestId('switchInput');
    expect(switchInput).toBeInTheDocument();
    expect(screen.getByText(`Switch to`)).toBeInTheDocument();
    expect(screen.getByText(`annual Plans`)).toBeInTheDocument();
  });
});
