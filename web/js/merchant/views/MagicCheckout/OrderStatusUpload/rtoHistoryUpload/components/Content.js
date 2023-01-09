export const ValidateModalInfo = ({ sampleUrl }) => (
  <div className="modal-info" data-testid="order-history-validate-component">
    <h5 className="modal-info-heading">KEEP IN MIND</h5>
    <ol className="validate-modal-ul">
      <li>
        Please export order history from your logistics provider dashboard for the last 6 months.
      </li>
      <li>
        We support order export templates for Shiprocket, Delhivery and Pickrr. Incase you use any
        other provider/OMS, please use this{' '}
        <a className="btn-link" href={sampleUrl}>
          <strong>sample file.</strong>
        </a>
      </li>
      <li>
        For other providers, please ensure that you follow the format given in the sample file.
      </li>
      <li>
        Date format should be YYYY-MM-DD and shipping status should only be limited to the values
        provided in the file.
      </li>
      <li>Order upload link will be live for 30 days from date of onboarding.</li>
    </ol>
  </div>
);

export const EmptyComponent = ({ sampleUrl }) => (
  <div className="magic-empty-batch-list" data-testid="order-history-empty-component">
    <ValidateModalInfo sampleUrl={sampleUrl} />
  </div>
);
