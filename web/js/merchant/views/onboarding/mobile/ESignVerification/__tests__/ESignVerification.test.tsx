import React from 'react';
import 'regenerator-runtime/runtime';
import '@testing-library/jest-dom/extend-expect';
import ESignVerification from '../index';
import AadharError from '../AadharError';
import * as ActivationDB from '../../services/data/ActivationDB';
import GetOTP from '../GetOTP';
import VerifyOTP from '../VerifyOTP';
import AadharSuccess from '../AadharSuccess';
import useActivation from '../../hooks/useActivation';
import { fireEvent, render, waitFor, screen, waitForElementToBeRemoved, delay } from 'test-utils';

afterEach(() => {
  ActivationDB.reset();
});

const App: React.FC = () => {
  const { status } = useActivation();
  if (status === 'loading') return <div>Loading...</div>;
  return <AadharSuccess />;
};
const aadharErrorMsg =
  'We can not support OTP based Aadhaar verification because of downtime on UIDAI servers. Please upload copies of one the address proofs listed below.';

const GetOtpApp: React.FC = () => {
  const { status } = useActivation();
  const setOPT = jest.fn();
  const setAadharNumber = jest.fn();
  const setRequestId = jest.fn();
  const goToNextScreen = jest.fn();
  const setUserEnteredCaptcha = jest.fn();
  const handleDownTimeError = jest.fn();
  if (status === 'loading') return <div>Loading...</div>;
  return (
    <GetOTP
      setOTP={setOPT}
      otp=""
      setAadharNumber={setAadharNumber}
      setRequestId={setRequestId}
      goToNextScreen={goToNextScreen}
      setUserEnteredCaptcha={setUserEnteredCaptcha}
      aadharError=""
      disabled={false}
      handleDownTimeError={handleDownTimeError}
    />
  );
};

const waitForLoadingToFinish = () => waitForElementToBeRemoved(screen.queryByText('Loading...'));

describe('ESignVerification', () => {
  it('should show the disabled form when checkbox is true', async () => {
    render(<ESignVerification disabled={false} showAddressProofDoc={() => {}} />, {});
    const disableAadharFlowContainer = screen.getByRole('checkbox', { checked: false });
    await waitFor(() => {
      fireEvent.change(disableAadharFlowContainer, { target: { checked: true } });
    });
    expect(<ESignVerification disabled={false} showAddressProofDoc={() => {}} />).toMatchSnapshot();
  });
});

describe('GetOTP', () => {
  it('should throw down time error', async () => {
    ActivationDB.update({
      business_type: '1',
    });
    render(<GetOtpApp />, {});
    await waitForLoadingToFinish();

    const [aadharNumber, captcha]: any = screen.getAllByTestId('ds-text-input');

    fireEvent.change(aadharNumber, { target: { value: '941743462460' } });
    fireEvent.change(captcha, { target: { value: 'DId2s' } });
    expect(screen.getByText('Submit & Get OTP')).toBeInTheDocument();
    await waitFor(() => fireEvent.click(screen.getByText('Submit & Get OTP')));
    delay();
    render(<AadharError />, {});
    expect(screen.getByText('Aadhaar Verification')).toBeInTheDocument();
    expect(screen.getByText(aadharErrorMsg)).toBeInTheDocument();
  });

  it("should throw an validation message if get otp pin number field doesn't have  4 digit number", async () => {
    ActivationDB.update({
      business_type: '1',
    });
    render(<GetOtpApp />, {});
    await waitForLoadingToFinish();

    expect(screen.getByText('12 Digit Aadhaar Number')).toBeInTheDocument();
    expect(screen.getByText('Enter the captcha shown above')).toBeInTheDocument();
    const [aadharNumber, captcha]: any = screen.getAllByTestId('ds-text-input');
    const checkbox = screen.getByText('My Aadhaar is not linked with any mobile number');

    fireEvent.change(aadharNumber, { target: { value: '941743462460' } });
    fireEvent.change(captcha, { target: { value: 'DId2s' } });
    expect(screen.getByText('Submit & Get OTP')).toBeInTheDocument();
    await waitFor(() => fireEvent.click(screen.getByText('Submit & Get OTP')));

    expect(checkbox).toBeInTheDocument();
    fireEvent.click(checkbox);
  });
});

describe('VerifyOTP', () => {
  it("should throw an validation message if otp field doesn't have  4 digit number", async () => {
    const handleDownTimeError = jest.fn();
    const { getByText, getAllByTestId } = render(
      <VerifyOTP
        goToNextScreen={() => {}}
        aadharNumber=""
        inputCaptcha=""
        requestId=""
        setAadharInputError={() => {}}
        handleDownTimeError={handleDownTimeError}
      />,
      {},
    );

    const otpNumberInput = getAllByTestId('ds-text-input')[1];
    fireEvent.change(otpNumberInput, { target: { value: '12' } });
    await waitFor(() => fireEvent.blur(otpNumberInput));

    const errorMessageNode = getByText(/OTP number should be of 6 digits/i);
    expect(otpNumberInput).toMatchSnapshot();
    expect(errorMessageNode).toBeInTheDocument();

    fireEvent.change(otpNumberInput, { target: { value: '123452' } });
    await waitFor(() => expect(screen.getByText('Submitting OTP ...')).toBeInTheDocument());
    delay();
    render(<AadharError />, {});
    expect(screen.getByText('Aadhaar Verification')).toBeInTheDocument();
    expect(screen.getByText(aadharErrorMsg)).toBeInTheDocument();
  });

  it('should sussfully verify otp', async () => {
    const handleDownTimeError = jest.fn();
    const { getAllByTestId } = render(
      <VerifyOTP
        goToNextScreen={() => {}}
        aadharNumber=""
        inputCaptcha=""
        requestId=""
        setAadharInputError={() => {}}
        handleDownTimeError={handleDownTimeError}
      />,
      {},
    );

    const [aadharNumber, otpNumberInput] = getAllByTestId('ds-text-input');

    await waitFor(() => fireEvent.blur(aadharNumber));
    fireEvent.change(otpNumberInput, { target: { value: '123456' } });
    await waitFor(() => expect(screen.getByText('Submitting OTP ...')).toBeInTheDocument());
    expect(screen.getByText('Start again')).toBeInTheDocument();
    await waitFor(() => fireEvent.click(screen.getByText('Start again')));
  });
});

describe('AadharSuccess', () => {
  it('should show the success screen', async () => {
    ActivationDB.update({
      stakeholder: { aadhaar_esign_status: 'verified' },
    });
    render(<App />, {});
    await waitForLoadingToFinish();
    expect(
      screen.getByText('We have Received your Aadhaar details successfully'),
    ).toBeInTheDocument();
  });
});

describe('AadharError', () => {
  it('should show the Error screen', () => {
    expect(<AadharError />).toMatchSnapshot();
  });
});
