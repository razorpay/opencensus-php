import React from 'react';
import 'regenerator-runtime/runtime';
import '@testing-library/jest-dom/extend-expect';
import ESignVerification from '../index';
import AadharInput from '../AadharInput';
import GetOTP from '../GetOTP';
import VerifyOTP from '../VerifyOTP';
import AadharSuccess from '../AadharSuccess';
import { fireEvent, render, waitFor, screen, cleanup } from 'test-utils';

afterEach(() => {
  cleanup();
});

describe('ESignVerification', () => {
  it('calls getcaptcha onClick prop when clicked', () => {
    render(<ESignVerification disabled={false} />, {});
    fireEvent.click(
      screen.getByText(/Verify with OTP/i),
      new MouseEvent('click', {
        bubbles: true,
        cancelable: true,
      }),
    );
  });

  it.skip('should show the disabled form when checkbox is true', async () => {
    render(<ESignVerification disabled={false} />, {});
    const disableAadharFlowContainer = screen.getByRole('checkbox', { checked: false });
    await waitFor(() => {
      fireEvent.change(disableAadharFlowContainer, { target: { checked: true } });
    });
    expect(<ESignVerification disabled={false} />).toMatchSnapshot();
  });
});

describe('AadharInput', () => {
  it.skip("should throw an validation message if Aadhar field doesn't have  12 digit number", async () => {
    const { getByText, getAllByTestId } = render(
      <AadharInput
        setAadharNumber={() => {}}
        goToNextScreen={() => {}}
        aadharError=""
        setAadharInputError={() => {}}
        disabled={false}
      />,
      {},
    );
    const aadharNumberInput = getAllByTestId('ds-text-input')[0];
    await waitFor(() => {
      fireEvent.change(aadharNumberInput, {
        target: {
          value: '123456',
        },
      });
    });

    await waitFor(() => {
      fireEvent.blur(aadharNumberInput);
    });

    const errorMessageNode = getByText(/Aadhar should be of 12 digits/i);
    expect(aadharNumberInput).toMatchSnapshot();
    expect(errorMessageNode).toBeInTheDocument();
  });

  it('should show the disabled form when checkbox is true', async () => {
    const { getByRole } = render(
      <AadharInput
        setAadharNumber={() => {}}
        goToNextScreen={() => {}}
        aadharError=""
        setAadharInputError={() => {}}
        disabled={false}
      />,
      {},
    );
    const disableAadharFlowContainer = getByRole('checkbox', { checked: false });
    await waitFor(() => {
      fireEvent.change(disableAadharFlowContainer, { target: { checked: true } });
    });
    expect(
      <AadharInput
        setAadharNumber={() => {}}
        goToNextScreen={() => {}}
        aadharError=""
        setAadharInputError={() => {}}
        disabled={false}
      />,
    ).toMatchSnapshot();
  });

  it.skip('should submit the form of aadharinput', async () => {
    const goToNextScreen = jest.fn();

    const { getByText, getAllByTestId } = render(
      <AadharInput
        setAadharNumber={() => {}}
        goToNextScreen={goToNextScreen}
        aadharError=""
        setAadharInputError={() => {}}
        disabled={false}
      />,
      {},
    );
    const aadharNumberInput = getAllByTestId('ds-text-input')[0];
    const formSubmitNode = getByText(/Verify with OTP/i);

    fireEvent.change(aadharNumberInput, { target: { value: 123456789012 } });
    await waitFor(() => {
      fireEvent.click(formSubmitNode);
    });

    expect(goToNextScreen).toHaveBeenCalledTimes(1);
  });
});

describe('GetOTP', () => {
  it.skip("should throw an validation message if get otp pin number field doesn't have  4 digit number", async () => {
    const { getByText, getAllByTestId } = render(
      <GetOTP
        setPin={() => {}}
        setOTP={() => {}}
        otp=""
        aadharNumber=""
        setAadharNumber={() => {}}
        goToNextScreen={() => {}}
        setUserEnteredCaptcha={() => {}}
        setAadharInputError={() => {}}
        aadharError=""
      />,
      {},
    );
    const pinNumberInput = getAllByTestId('ds-text-input')[2];

    fireEvent.change(pinNumberInput, {
      target: {
        value: '1',
      },
    });

    await waitFor(() => {
      fireEvent.blur(pinNumberInput);
    });

    const errorMessageNode = getByText(/Pin number should be of 4 digits/i);
    expect(pinNumberInput).toMatchSnapshot();
    expect(errorMessageNode).toBeInTheDocument();
  });

  it.skip('calls Get OTP onClick prop when clicked', () => {
    render(
      <GetOTP
        setPin={() => {}}
        setOTP={() => {}}
        otp=""
        aadharNumber=""
        setAadharNumber={() => {}}
        goToNextScreen={() => {}}
        setUserEnteredCaptcha={() => {}}
        setAadharInputError={() => {}}
        aadharError=""
      />,
      {},
    );
    fireEvent.click(
      screen.getByText(/Get OTP/i),
      new MouseEvent('click', {
        bubbles: true,
        cancelable: true,
      }),
    );
  });
});

describe('VerifyOTP', () => {
  it.skip("should throw an validation message if otp field doesn't have  4 digit number", async () => {
    const { getByText, getAllByTestId } = render(
      <VerifyOTP
        goToNextScreen={() => {}}
        pin=""
        aadharNumber=""
        inputCaptcha=""
        setAadharInputError={() => {}}
      />,
      {},
    );
    const otpNumberInput = getAllByTestId('ds-text-input')[1];
    fireEvent.change(otpNumberInput, {
      target: {
        value: '12',
      },
    });

    await waitFor(() => {
      fireEvent.blur(otpNumberInput);
    });

    const errorMessageNode = getByText(/OTP number should be of 6 digits/i);
    expect(otpNumberInput).toMatchSnapshot();
    expect(errorMessageNode).toBeInTheDocument();
  });

  it.skip('calls Get OTP onClick prop when clicked', () => {
    render(
      <VerifyOTP
        goToNextScreen={() => {}}
        pin=""
        aadharNumber=""
        inputCaptcha=""
        setAadharInputError={() => {}}
      />,
      {},
    );
    fireEvent.click(
      screen.getByText(/Submit OTP/i),
      new MouseEvent('click', {
        bubbles: true,
        cancelable: true,
      }),
    );
  });
});

describe('AadharSuccess', () => {
  it('should show the success screen', () => {
    expect(<AadharSuccess />).toMatchSnapshot();
  });
});
