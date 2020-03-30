const SuccessModalContent = ({ onClose }) => (
  <div>
    <div class="form-group">
      Thank you for providing your website/app details. We will review the
      required details and get back to you soon. In case of any queries, we’ll
      contact you via mail.
    </div>
    <div class="form-group">
      <button class="btn btn-primary btn-block center-block" onClick={onClose}>
        Okay, Got it!
      </button>
    </div>
  </div>
);

export default SuccessModalContent;
