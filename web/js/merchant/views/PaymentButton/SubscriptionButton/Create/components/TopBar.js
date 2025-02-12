const TopBar = ({ title, actionButtons, handleClose, isActionsActive }) => {
  return (
    <div className="PaymentButton-Create-TopBar">
      <div
        className="TopBar-container
      "
      >
        <div className="TopBar-title">{title}</div>

        {isActionsActive && !!actionButtons && (
          <div className="TopBar-actions">{actionButtons}</div>
        )}

        {isActionsActive && !!handleClose && (
          <span className="close-btn" onClick={handleClose}>
            ×
          </span>
        )}
      </div>
    </div>
  );
};

export default TopBar;
