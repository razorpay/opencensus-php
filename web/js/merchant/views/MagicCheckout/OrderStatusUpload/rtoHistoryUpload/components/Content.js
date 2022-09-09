export const ValidateModalInfo = () => (
  <div className="modal-info">
    <h5 className="modal-info-heading">KEEP IN MIND</h5>
    <ol className="validate-modal-ul">
      <li>
        Please export order history from your logistics provider dashboard for the last 3 months.
      </li>
      <li>
        Currently, we support order history upload for Shiprocket, Delhivery and Pickrr. Other
        providers coming soon.
      </li>
      <li>Only one file upload per user is allowed.</li>
      <li>
        If you use multiple shipping providers, please upload the file from your most frequently
        used provider.
      </li>
    </ol>
  </div>
);

export const EmptyComponent = () => (
  <div className="magic-empty-batch-list">
    <ValidateModalInfo />
  </div>
);
