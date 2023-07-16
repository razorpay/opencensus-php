import React from 'react';
interface ActionToolbarProps {
  disableAction: boolean;
  openOrderEditingModal: (id: string, display_id: string) => void;
  id: string;
  display_id: string;
}

const ActionToolbar: React.FC<ActionToolbarProps> = ({
  disableAction,
  openOrderEditingModal,
  id,
  display_id,
}) => {
  const toolbarClassName = `action-toolbar${disableAction ? ' disable' : ''}`;

  return (
    <div className={toolbarClassName} data-testid={`edit-${id}`}>
      <button
        type="button"
        disabled={disableAction}
        onClick={() => openOrderEditingModal(id, display_id)}
        className="action-btn approve-action"
      >
        <i className="i i-pencil-edit" />
      </button>
    </div>
  );
};

export default ActionToolbar;
