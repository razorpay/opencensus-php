import { set } from 'common/utils/immutable';
interface State {
  partnerSwitchFlag: boolean | null;
}

const SET_PARTNER_SWITCH_FLAG = 'SET_PARTNER_SWITCH_FLAG';

const initialState: State = {
  partnerSwitchFlag: null,
};

interface CreatePartnerSwitchAction {
  type: typeof SET_PARTNER_SWITCH_FLAG;
  payload: boolean;
}

type PartnerDashboardAction = CreatePartnerSwitchAction;

export const setPartnerSwitchFlag = (): CreatePartnerSwitchAction => {
  return {
    type: SET_PARTNER_SWITCH_FLAG,
    payload: true,
  };
};

export default function partnerReducer(
  state = initialState,
  action: PartnerDashboardAction,
): State {
  switch (action.type) {
    case SET_PARTNER_SWITCH_FLAG:
      return set(state, 'partnerSwitchFlag', action.payload);

    default:
      return state;
  }
}
