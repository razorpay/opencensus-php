// eslint-disable-next-line import/no-cycle
import Non3dsCardsActivationResource from 'merchant/models/Non3dsCardsActivation';

// constants
import {
  NON_3DS_CARDS_ACTIVATION_FETCH,
  NON_3DS_CARDS_ACTIVATION_ENABLE,
  NON_3DS_CARDS_ACTIVATION_DISABLE,
  NON_3DS_CARDS_ACTIVATION_REMOVE_ERROR,
} from './constants';

const fetchNon3dsCardsStatus = () => {
  const resource = new Non3dsCardsActivationResource();
  return {
    type: NON_3DS_CARDS_ACTIVATION_FETCH,
    payload: resource.status(),
  };
};

const enableNon3dsCards = () => {
  const resource = new Non3dsCardsActivationResource();
  return {
    type: NON_3DS_CARDS_ACTIVATION_ENABLE,
    payload: resource.enable(),
  };
};

const disableNon3dsCards = () => {
  const resource = new Non3dsCardsActivationResource();
  return {
    type: NON_3DS_CARDS_ACTIVATION_DISABLE,
    payload: resource.disable(),
  };
};

const removeErrorMessage = () => (dispatch) => {
  setTimeout(() => {
    dispatch({
      type: NON_3DS_CARDS_ACTIVATION_REMOVE_ERROR,
    });
  }, 7000);
};

export { fetchNon3dsCardsStatus, enableNon3dsCards, disableNon3dsCards, removeErrorMessage };
