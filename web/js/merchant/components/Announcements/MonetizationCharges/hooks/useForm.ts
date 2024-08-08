import React, { useState, useEffect, useRef } from 'react';

export interface IFormReturnType {
  handleChange: (event: any, inputType?: string) => void;
  handleSubmit: (event: React.SyntheticEvent) => void;
  values: Record<string, string>;
  errors: Record<string, string>;
  isSubmitting: boolean;
  setFormSubmitCompleted: () => void;
  submitError: string;
  isSubmitted: boolean;
  setIsSubmitted: React.Dispatch<React.SetStateAction<boolean>>;
  clearForm: () => void;
}

export interface IFormArgsType {
  callback: (arg: Record<string, string>) => Promise<void>;
  validate: (arg: Record<string, string>) => Record<string, string>;
  onSubmitError?: (message: string) => void;
  onValidationError?: (message: string) => void;
}

const useForm = ({
  callback,
  validate,
  onSubmitError,
  onValidationError,
}: IFormArgsType): IFormReturnType => {
  const [values, setValues] = useState({});
  const [errors, setErrors] = useState({});
  const [submitError, setSubmitError] = useState('');
  const [isSubmitted, setIsSubmitted] = useState(false);
  const formCheck = useRef(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isPostValidation, setIsPostValidation] = useState(false);

  const setFormSubmitCompleted = () => {
    setIsSubmitting(false);
  };

  const clearForm = () => {
    setValues({});
    setErrors({});
  };

  useEffect(() => {
    if (
      Object.keys(errors).length === 0 &&
      Object.keys(values).length > 0 &&
      isPostValidation &&
      !formCheck.current
    ) {
      setIsSubmitting(true);
      formCheck.current = true;
      callback(values)
        .then(() => {
          setSubmitError('');
          setIsSubmitted(true);
        })
        .catch((err) => {
          setSubmitError(err.message);
          onSubmitError?.(err.message);
          if (err.message !== 'Stayback') setIsSubmitted(true);
        })
        .finally(() => {
          setIsSubmitting(false);
          setIsPostValidation(false);
          formCheck.current = false;
        });
    }
  }, [callback, errors, isPostValidation, onSubmitError, values]);

  const handleSubmit = (event) => {
    if (event) event.preventDefault();
    const fieldErrors = validate(values);
    if (Object.keys(fieldErrors).length > 0) {
      onValidationError?.(Object.values(fieldErrors).join());
    }
    setErrors(fieldErrors);
    setIsPostValidation(true);
  };

  const handleChange = (event, inputType = 'text') => {
    if (inputType === 'select') {
      setValues((prevValues) => ({ ...prevValues, [event.name]: event.values[0] }));
    } else {
      setValues((prevValues) => ({ ...prevValues, [event.name]: event.value }));
    }
  };

  return {
    handleChange,
    handleSubmit,
    values,
    errors,
    isSubmitting,
    setFormSubmitCompleted,
    submitError,
    isSubmitted,
    setIsSubmitted,
    clearForm,
  };
};

export default useForm;
