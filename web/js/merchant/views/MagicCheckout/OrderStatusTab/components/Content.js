export const validateModalInfo = (maxRows, sampleUrl) => (
  <div className="modal-info">
    <h5 className="modal-info-heading">KEEP IN MIND</h5>
    <ol className="validate-modal-ul">
      <li>
        File should follow the template format. Download{' '}
        <a className="btn-link" href={sampleUrl}>
          <strong>sample file</strong>
        </a>{' '}
        for the template.
      </li>
      <li>
        The &ldquo;updated_at&ldquo; column values should be in the dd/mm/yyyy format. (e.g.
        01/01/2022)
      </li>
      <li>The number of rows in the file should not exceed {maxRows}.</li>
    </ol>
  </div>
);

export const EmptyComponent = ({ sampleUrl }) => (
  <div className="magic-empty-batch-list">
    <h4>Bulk Upload Delivery Statuses</h4>
    {validateModalInfo('1M', sampleUrl)}
  </div>
);
