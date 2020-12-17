import { merchantFetch } from 'merchant/utils/ajax';
import { set, merge } from 'common/utils/immutable';

export const REWARDS_FETCH = 'REWARDS_FETCH';
export const REWARD_EDIT = 'REWARD_EDIT';

export const fetchRewardsAjax = () => {
  return merchantFetch({
    url: `rewards`,
    method: 'get',
  });
};

export const fetchRewards = () => {
  return {
    type: REWARDS_FETCH,
    payload: fetchRewardsAjax(),
  };
};

export const activateReward = (data) => {
  return {
    type: REWARD_EDIT,
    payload: data,
  };
};

let initialState = {
  loading: true,
  rewards: [],
  error: null,
};

export default function (state = initialState, action) {
  switch (action.type) {
    case `${REWARDS_FETCH}::PENDING`:
      return set(state, 'loading', true);
    case `${REWARDS_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        rewards: action.payload.data,
        error: null,
      });

    case `${REWARDS_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        rewards: initialState.rewards,
      });

    case 'REWARD_EDIT':
      return set(state, 'rewards', action.payload);

    default:
      return state;
  }
}
