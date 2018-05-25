export default function SuccessModal({ onModalClose, children }) {
  return (
    <div class="modal-body">
      <div class="success-tick" />
      <h4>Batch Created Succesfully</h4>
      <div class="success-text">{children}</div>
      <span class="btn btn-link" onClick={onModalClose}>
        <strong>Close</strong>
      </span>
    </div>
  );
}
