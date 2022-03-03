export const IngenicoPoints = () => {
  return (
    <ol>
      <li>
        Write to your TPSL account manager to create a new MID for your account and be sure to get
        the seamless option enabled for your merchant id
      </li>
      <li>
        You need to make sure you have the merchant code and the encryption keys which will be
        shared over email
      </li>
      <li>
        Encryption keys will have the following details shared via email from the TPSL team:{' '}
        <b>Merchant Code, Scheme Code, Encryption Key, Encryption IV</b>
      </li>
      <li>
        In case any bank codes are shared by the TPSL team they should be shared with razorpay team
      </li>
      <li>
        <b>Scheme Code</b> is an arrangement between merchant and TPSL. Razorpay will pass default
        scheme code “FIRST” (this is default for most merchants) unless scheme code is passed in the
        Razorpay checkout within the notes field.
      </li>
      <li>
        You need to ensure that the following features are enabled for your Ingenico MID:
        “BankReferenceID” & “other_details” (other details includes auth code and other meta
        details) in enquiry API
      </li>
      <li>
        For refunds we need feature to allow “client_ref_id” in refund enquiry API and also the
        feature for ARN to be received in refund enquiry API.
      </li>
      <li>
        TPSL will share two merchant codes, one for live and one for test. These codes look like -
        L123456 or T123456 with the first one being for production. Ensure you add the live keys in
        case you are in Razorpay live mode for production.
      </li>
    </ol>
  );
};
