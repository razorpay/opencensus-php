import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render } from 'test-utils';
import {
  BANK_ACCOUNT_UPDATE_PENNY_TESTING_SUCCESS,
  BANK_ACCOUNT_UPDATE_PENNY_TESTING_LOADING,
} from '../constants';
import BankAccountUpdateState from '../BankAccountUpdateState';
import { classList } from 'common/utils/rzp-utils';

const App = ({ data, lottieDivClass }) => {
  return <BankAccountUpdateState data={data} lottieDivClass={lottieDivClass} />;
};

describe('Bank account update step', () => {
  test('should render title, subtitle', () => {
    const data = BANK_ACCOUNT_UPDATE_PENNY_TESTING_SUCCESS;

    const { getByText } = render(<App data={data} />);
    const title = getByText(data.title);
    const subtitle = getByText(data.subtitle);

    expect(title).toBeInTheDocument();
    expect(subtitle).toBeInTheDocument();
  });

  test('should not render title, subtitle if not passed as props', () => {
    const data = BANK_ACCOUNT_UPDATE_PENNY_TESTING_SUCCESS;

    const { queryByText } = render(<App />);
    const title = queryByText(data.title);
    const subtitle = queryByText(data.subtitle);

    expect(title).not.toBeInTheDocument();
    expect(subtitle).not.toBeInTheDocument();
  });

  test('should work with optional props', () => {
    const data = BANK_ACCOUNT_UPDATE_PENNY_TESTING_LOADING;
    const lottieDivClass = ['test-class'];
    const { getByText, getByTestId } = render(<App data={data} lottieDivClass={lottieDivClass} />);
    const info = getByText(data.info);
    expect(info).toBeInTheDocument();

    const animationDiv = getByTestId('bank-update-state-animation');
    const animationDivClasses = animationDiv.getAttribute('class');
    expect(animationDivClasses).toBe(classList(lottieDivClass));
  });

  describe('Lottie animation', () => {
    const dataWithLottieFile = BANK_ACCOUNT_UPDATE_PENNY_TESTING_SUCCESS;
    const dataWithoutLottieFile = {
      ...dataWithLottieFile,
      lottieData: null,
    };
    test('should render if lottieData is passed', () => {
      const { getByTestId } = render(<App data={dataWithLottieFile} />);
      expect(getByTestId('bank-update-state-animation')).toBeInTheDocument();
    });

    test('should not render if lottieData is not passed', () => {
      const { queryByTestId } = render(<App data={dataWithoutLottieFile} />);
      expect(queryByTestId('bank-update-state-animation')).not.toBeInTheDocument();
    });
  });
});
