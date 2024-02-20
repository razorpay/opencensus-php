import approve from 'assets/circle-check.svg';
import cross from 'assets/circle-cross.svg';
import copy from 'assets/copy.svg';
import edit from 'assets/edit-pencil.svg';
import publish from 'assets/payment_pages/upload.svg';
import trashOutline from 'assets/trash-outline.svg';

export const statusActionMap = {
  created: [
    { name: 'publish', displayValue: 'Publish', icon: publish },
    { name: 'edit', displayValue: 'View and Edit', icon: edit },
    { name: 'duplicate', displayValue: 'Duplicate', icon: copy },
    { name: 'delete', displayValue: 'Delete', icon: trashOutline },
  ],
  active: [
    { name: 'inactivate', displayValue: 'Deactivate', icon: cross },
    { name: 'edit', displayValue: 'View and Edit', icon: edit },
    { name: 'duplicate', displayValue: 'Duplicate', icon: copy },
  ],
  in_active: [
    { name: 'activate', displayValue: 'Activate', icon: approve },
    { name: 'edit', displayValue: 'View and Edit', icon: edit },
    { name: 'duplicate', displayValue: 'Duplicate', icon: copy },
    { name: 'delete', displayValue: 'Delete', icon: trashOutline },
  ],
  expired: [{ name: 'duplicate', displayValue: 'Duplicate', icon: copy }],
  published: [
    { name: 'inactivate', displayValue: 'Deactivate', icon: cross },
    { name: 'edit', displayValue: 'View and Edit', icon: edit },
    { name: 'duplicate', displayValue: 'Duplicate', icon: copy },
    { name: 'delete', displayValue: 'Delete', icon: trashOutline },
  ],
};
