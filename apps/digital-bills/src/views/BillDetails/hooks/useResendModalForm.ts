import { Dispatch, SetStateAction, useState } from 'react';
import { TextInputProps } from '@razorpay/blade/components';

export type FormSubmitCallback = (args: { email: string; contactNo: string }) => void;

type UseFormValidationState = {
  emailValidationState: TextInputProps['validationState'];
  setEmailValidationState: Dispatch<SetStateAction<TextInputProps['validationState']>>;
  phoneNumberValidationState: TextInputProps['validationState'];
  setPhoneNumberValidationState: Dispatch<SetStateAction<TextInputProps['validationState']>>;
  onEmailChange: ({ name, value }: { name?: string; value?: string }) => void;
  onPhoneNoChange: ({ name, value }: { name?: string; value?: string }) => void;
  onFormSubmit: (cb: FormSubmitCallback) => void;
  email: string;
  setEmail: Dispatch<SetStateAction<string>>;
  phoneNo: string;
  setPhoneNo: Dispatch<SetStateAction<string>>;
};
type FormValues = {
  email?: string;
  phoneNo?: string;
};

const useResendModalForm = (
  formValues: FormValues = { email: '', phoneNo: '' },
): UseFormValidationState => {
  const [emailValidationState, setEmailValidationState] =
    useState<TextInputProps['validationState']>('none');
  const [phoneNumberValidationState, setPhoneNumberValidationState] =
    useState<TextInputProps['validationState']>('none');

  const [email, setEmail] = useState<string>(formValues?.email ?? '');
  const [phoneNo, setPhoneNo] = useState<string>(formValues?.phoneNo ?? '');

  const onPhoneNoChange: UseFormValidationState['onPhoneNoChange'] = (e) => {
    setPhoneNo(e.value ?? '');
  };

  const onEmailChange: UseFormValidationState['onEmailChange'] = (e) => {
    const EMAIL_VALIDATION_REGEX =
      /[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})/;
    const emailValue = e.value || '';

    if (!EMAIL_VALIDATION_REGEX.test(emailValue)) {
      setEmailValidationState('error');
    } else {
      setEmailValidationState('none');
    }
    setEmail(emailValue ?? '');
  };

  const onFormSubmit = (cb: FormSubmitCallback): void => {
    if (!email && !phoneNo) {
      return;
    }
    cb({ email, contactNo: phoneNo });
  };

  return {
    onEmailChange,
    emailValidationState,
    setEmailValidationState,
    phoneNumberValidationState,
    setPhoneNumberValidationState,
    onFormSubmit,
    email,
    onPhoneNoChange,
    phoneNo,
    setPhoneNo,
    setEmail,
  };
};

export default useResendModalForm;
