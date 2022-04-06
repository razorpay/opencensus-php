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
        Name, Contact, Address Line1, City, State, Country and Zipcode are mandatory fields, can’t
        be left blank.
      </li>
      <li>
        For “address_type”, recommended values are home, office and other, if labels are available.
      </li>
      <li>The number of rows should not exceed {maxRows}.</li>
    </ol>
  </div>
);
