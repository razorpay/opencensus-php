export const validateBlocklistModalInfo = (maxRows, sampleUrl) => (
  <div className="modal-info">
    <h5 className="modal-info-heading">KEEP IN MIND</h5>
    <ol className="validate-modal-ul">
      <li>
        File should follow the template format. Download{' '}
        <a className="btn-link" href={sampleUrl}>
          <strong>sample file.</strong>
        </a>
      </li>
      <li>The number of rows in the file should not exceed {maxRows}.</li>
      <li>Please enter phone numbers with country code, e.g. +919988776655.</li>
    </ol>
  </div>
);

export const validateAllowlistModalInfo = (maxRows, sampleUrl) => (
  <div className="modal-info">
    <h5 className="modal-info-heading">KEEP IN MIND</h5>
    <ol className="validate-modal-ul">
      <li>
        File should follow the template format. Download{' '}
        <a className="btn-link" href={sampleUrl}>
          <strong>sample file.</strong>
        </a>
      </li>
      <li>The number of rows in the file should not exceed {maxRows}.</li>
      <li>Allowlist orders will not be eligible for RTO Insurance (if opted).</li>
      <li>Please enter phone numbers with country code, e.g. +919988776655.</li>
    </ol>
  </div>
);
