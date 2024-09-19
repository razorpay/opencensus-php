import { merge } from 'common/utils/immutable';

export const handleOpenModal = (state, payload) => merge(state, payload);

export const getOpenModalState = (state, payload) => {
  /**
   * Sets the default value of isNew to false, as we are merging props. If someone sets isNew in a previous openModal and
   * tries to open a new modal (without calling closeModal first), the new modal inherits the previous isNew value, which causes issues.
   */
  payload = payload || {};
  return handleOpenModal(state, { ...payload, isNew: payload.isNew || false });
};

export const getCloseModalState = () => {
  document.body.classList.remove('ReactModal__Body--open');
  return {};
};
