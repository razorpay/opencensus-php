const TopBar = ({ title, actionButtons, handleClose, isActionsActive }) => {
  return (
    <div class="PaymentButton-Create-TopBar">
      <div class="TopBar-container
      ">
        <div class="TopBar-title">{title}</div>

        {isActionsActive &&
          !!actionButtons && <div class="TopBar-actions">{actionButtons}</div>}

        {isActionsActive &&
          !!handleClose && (
            <span class="close-btn" onClick={handleClose}>
              ×
            </span>
          )}
      </div>
    </div>
  );
};

export default TopBar;
