import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render } from 'test-utils';
import { BankVerificationErrorInDetailsMap } from '../constants';
import BankDetailsError from '../BankDetailsError';
import * as utils from '../utils';

const onButtonClick = jest.fn();
const error = BankVerificationErrorInDetailsMap['KC03: Invalid Beneficiary Account Number or IFSC'];
const buttonText = 'Change Details';

beforeAll(() => {
  jest.spyOn(utils, 'trackBankAccountDetailsChange').mockImplementation(jest.fn);
});

const App = ({ error }) => {
  return <BankDetailsError error={error} onButtonClick={onButtonClick} />;
};
describe('Bank account update details error component', () => {
  test('should render title, subtitle, icon and button', () => {
    const { getByText, getByRole } = render(<App error={error} />);
    const ctaBtn = getByRole('button', {
      name: buttonText,
    });
    const title = getByText(error.title);
    const icon = getByText((_, element) => element.tagName.toLowerCase() === 'i');
    const iconClass = icon.getAttribute('class');

    expect(title).toBeInTheDocument();
    expect(getByText(error.subtitle)).toBeInTheDocument();
    expect(ctaBtn).toBeInTheDocument();
    expect(icon).toBeInTheDocument();
    expect(iconClass).toContain(`bank-details-icon--${error.icon}`);
  });

  test('should fire error track event on component mount', () => {
    render(<App error={error} />);
    expect(utils.trackBankAccountDetailsChange).toHaveBeenCalled();
    expect(utils.trackBankAccountDetailsChange).toHaveBeenCalledWith({
      objectName: 'Bank Account Input Error Screen',
      actionName: 'Viewed',
      properties: {
        errorMessage: error.title,
      },
    });
  });
});
