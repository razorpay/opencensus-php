export default function SuccessModal({ successHeader, onModalClose, children }) {
  return (
    <div className="modal-body">
      <div className="success-tick" />
      <h4>{successHeader ?? `Batch Created Succesfully`}</h4>
      {!successHeader ? (
        <>
          <div className="success-text">{children}</div>
          <span className="btn btn-link" onClick={onModalClose}>
            <strong>Close</strong>
          </span>
        </>
      ) : null}
    </div>
  );
}
