import React from 'react';

import { Button, TrashIcon } from '@razorpay/blade/components';
import { DeleteToolbarProps } from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Allowlist/types';

import { DeleteAllToolbarContainer } from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Allowlist/Styled';

const DeleteAllToolbar = (props: DeleteToolbarProps) => {
  const { onDeleteClick } = props;

  return (
    <DeleteAllToolbarContainer>
      <Button
        type="button"
        variant="secondary"
        color="default"
        onClick={onDeleteClick}
        size="medium"
        iconPosition="left"
        icon={TrashIcon}
        marginRight="spacing.2"
      >
        Delete Allowlist
      </Button>
    </DeleteAllToolbarContainer>
  );
};

export default DeleteAllToolbar;
