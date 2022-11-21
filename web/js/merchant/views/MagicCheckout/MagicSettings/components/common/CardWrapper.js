const CardWrapper = ({ children, switchToEdit, cardTitle, extraClass }) => (
  <div className={`settings-card ${extraClass || ''}`}>
    <div className="settings-card-widget bg-white">
      <div className="display-flex settings-card-widget-wrapper">
        <div className="display-flex settings-card-widget-content">
          <div className="display-flex card-title">
            <p className="title-text">{cardTitle}</p>
            <div className="card-edit pointer" onClick={switchToEdit}>
              <i className="i i-edit_board native-settings-edit-icon" /> Edit
            </div>
          </div>
          {children}
        </div>
      </div>
    </div>
  </div>
);

CardWrapper.Item = ({ label, value }) => (
  <div className="flex flex--column gap--4 setting-card">
    <div className="setting-label">{label}</div>
    <div className="setting-value font-bold">{value}</div>
  </div>
);

export default CardWrapper;
