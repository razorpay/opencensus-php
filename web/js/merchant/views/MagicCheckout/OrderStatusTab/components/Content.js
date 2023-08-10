export const validateModalInfo = (maxRows, sampleUrl) => (
  <div className="modal-info">
    <h5 className="modal-info-heading">KEEP IN MIND</h5>
    <ol className="validate-modal-ul">
      <li>
        Every shipping provider has a different export template. Magic follows a standard template
        for Delivery data upload.
      </li>
      <li>
        Download{' '}
        <a className="btn-link" href={sampleUrl}>
          <strong>this sample file</strong>
        </a>{' '}
        for the expected template.
      </li>
      <li>Entering shipping provider (Bluedart, DTDC etc.) and AWB number is mandatory.</li>
      <li>
        Merchant_order_id should contain your platform order id as mentioned in the order (e.g.
        #BCA1011)
      </li>
      <li>
        Status should contain shipping status against the order (e.g. RTO, Delivered, Cancelled etc)
      </li>
    </ol>
  </div>
);

export const EmptyComponent = ({ sampleUrl, maxRows }) => (
  <div className="magic-empty-batch-list">{validateModalInfo(maxRows, sampleUrl)}</div>
);
