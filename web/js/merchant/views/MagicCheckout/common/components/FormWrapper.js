const FormWrapper = ({ children, formTitle, extraClass }) => (
  <div className={`form-wrapper ${extraClass || ''}`}>
    <p className="font-18 font-bold checkout-headings">{formTitle}</p>
    {children}
  </div>
);

export default FormWrapper;
