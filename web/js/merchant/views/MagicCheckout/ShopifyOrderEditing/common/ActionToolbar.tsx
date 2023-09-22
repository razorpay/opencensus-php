import React from 'react';

import Popover, { PopoverBody } from 'common/ui/Popover';
import { ActionToolbarWrapper } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/styled';

interface ActionToolbarProps {
  disableAction: boolean;
  openOrderEditingModal: (id: string, display_id: string) => void;
  id: string;
  display_id: string;
  editable_errors: string[];
}

const ActionToolbar: React.FC<ActionToolbarProps> = ({
  disableAction,
  openOrderEditingModal,
  id,
  display_id,
  editable_errors = [],
}) => {
  const toolbarClassName = `action-toolbar${disableAction ? ' disable' : ''}`;

  return (
    <ActionToolbarWrapper className={toolbarClassName} data-testid={`edit-${id}`}>
      {disableAction && (
        <Popover>
          {/* Reason for adding a array is this is the response given to us by shopify and in some cases
          there are more reasons also, so we decided to show the latest one */}
          <PopoverBody className="align-center">{editable_errors[0] ?? ''}</PopoverBody>
        </Popover>
      )}

      <button
        type="button"
        disabled={disableAction}
        onClick={() => openOrderEditingModal(id, display_id)}
        className="action-btn approve-action"
      >
        <i className="i i-pencil-edit" />
      </button>
    </ActionToolbarWrapper>
  );
};

export default ActionToolbar;
