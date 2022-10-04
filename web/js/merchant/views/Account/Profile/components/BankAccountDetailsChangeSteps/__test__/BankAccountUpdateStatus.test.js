import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { fireEvent, render } from 'test-utils';
import BankAccountUpdateStatus from '../BankAccountUpdateStatus';
import { BankVerificationErrorInDetailsMap } from '../constants';

const onButtonClick = jest.fn();

const data = BankVerificationErrorInDetailsMap['KC03: Invalid Beneficiary Account Number or IFSC'];
const buttonText = 'CTA BTN';

const App = ({ data }) => {
  return (
    <BankAccountUpdateStatus data={data} buttonText={buttonText} onButtonClick={onButtonClick} />
  );
};
describe('Bank account update status component', () => {
  test('should render title, subtitle, icon and button', () => {
    const { getByText, getByRole } = render(<App data={data} />);
    const ctaBtn = getByRole('button', {
      name: buttonText,
    });
    const title = getByText(data.title);
    const icon = getByText((_, element) => element.tagName.toLowerCase() === 'i');
    const iconClass = icon.getAttribute('class');

    expect(title).toBeInTheDocument();
    expect(getByText(data.subtitle)).toBeInTheDocument();
    expect(ctaBtn).toBeInTheDocument();
    expect(icon).toBeInTheDocument();
    expect(iconClass).toContain(`bank-details-icon--${data.icon}`);
  });

  test('should not render title, subtitle, buton if not passed as props', () => {
    const { queryByText } = render(<App />);
    const title = queryByText(data.title);
    const icon = queryByText((_, element) => element.tagName.toLowerCase() === 'i');

    expect(title).not.toBeInTheDocument();
    expect(queryByText(data.subtitle)).not.toBeInTheDocument();
    expect(icon).not.toBeInTheDocument();
  });

  test('onButtonClick should be invoked on cta button click', () => {
    const { getByRole } = render(<App data={data} />);
    const ctaBtn = getByRole('button', {
      name: buttonText,
    });
    fireEvent.click(ctaBtn);
    expect(onButtonClick).toHaveBeenCalled();
  });
});
