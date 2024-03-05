import { merge } from 'common/utils/immutable';

export const handleOpenModal = (state, payload) => merge(state, payload);

export const getOpenModalState = (state, payload) => {
  return handleOpenModal(state, payload);
};

export const getCloseModalState = () => {
  document.body.classList.remove('ReactModal__Body--open');
  return {};
};
