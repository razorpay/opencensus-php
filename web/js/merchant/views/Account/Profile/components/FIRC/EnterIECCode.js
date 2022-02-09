import React, { useState, useCallback, useContext } from 'react';
import Button from 'common/new-ui/Button';
import * as yup from 'yup';
import { inputFormatToAlpha } from './utility';
import FIRCFormContext from './FIRCFormContext';

const validationSchema = yup.object().shape({
  is_purpose_code_special: yup.boolean(),
  iec_code: yup.string().when('is_purpose_code_special', {
    is: true,
    then: yup.string().required('This field cannot be empty').length(10, 'Code must be 10 digit'),
  }),
});

const EnterIECCode = ({ onChange }) => {
  const [error, setError] = useState(null);

  const { formState, handleNext, handlePrev } = useContext(FIRCFormContext);
  const { is_purpose_code_special, iec_code } = formState;

  const inputHandler = (e) => {
    e.target.value = inputFormatToAlpha(e.target.value);
    onChange(e);
  };

  const onSubmit = useCallback(async () => {
    try {
      await validationSchema.validate(formState);
      handleNext();
    } catch (err) {
      setError(err.message);
    }
  }, [formState, handleNext]);

  if (!is_purpose_code_special) return null;

  return (
    <>
      <div className="iec-code-container">
        <p className="label-text">
          Please enter your 10 digit IEC code issued by Directorate General of Foreign Trade (DGFT).
        </p>
        <div className="input-container">
          <input
            type="text"
            className="form-control"
            placeholder="IEC code"
            name="iec_code"
            value={iec_code}
            onChange={inputHandler}
            autoFocus={true}
          />
          {error && <p className="error-text text-danger">{error}</p>}
        </div>
      </div>
      <div className="footer-section">
        <Button.Primary className="next-btn m-0" onClick={onSubmit}>
          Next
        </Button.Primary>
        <Button.Transparent className="back-btn m-0" onClick={handlePrev}>
          Back
        </Button.Transparent>
      </div>
    </>
  );
};

export default React.memo(EnterIECCode);
