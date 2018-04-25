export default function SuccessModal({ onModalClose }) {
  return (
    <div class="modal-body">
      <div class="success-tick" />
      <h4>Batch Created Succesfully</h4>
      <p class="success-text">
        You can download the output file from batch detail view to check payment
        links generated. For the links that could not be generated due to some
        issues, please upload a new batch file.
        <br />
        <span class="btn btn-link" onClick={onModalClose}>
          <strong>Close</strong>
        </span>
      </p>
    </div>
  );
}
