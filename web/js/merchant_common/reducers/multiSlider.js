import { mergeAll } from 'common/utils/immutable';
import uuid from 'uuid';

const PUSH_SLIDER = 'PUSH_SLIDER';
const POP_SLIDER = 'POP_SLIDER';
const EMPTY_SLIDER_STACK = 'EMPTY_SLIDER_STACK';

export const pushSlider = (payload) => {
  const id = uuid();
  const {
    component = null,
    size = 'medium',
    transitionSpeed = 'medium',
    position = 'right',
    onClose = null,
    classString = null,
  } = payload;
  const sliderComponent = {
    id,
    component,
    size,
    transitionSpeed,
    position,
    onClose,
    classString,
  };

  return {
    type: PUSH_SLIDER,
    payload: {
      sliderComponent,
    },
  };
};

export const popSlider = (payload) => {
  return {
    type: POP_SLIDER,
    payload,
  };
};

export const emptySliderStack = (payload) => {
  return {
    type: EMPTY_SLIDER_STACK,
    payload,
  };
};

const initialState = {
  sliderStack: [],
};

export default (state = initialState, action) => {
  let newSliderStack = (state.sliderStack || []).slice();

  switch (action.type) {
    case PUSH_SLIDER:
      const { sliderComponent } = action.payload;

      if (sliderComponent.component) newSliderStack.push(sliderComponent);

      return mergeAll(state, {
        sliderStack: newSliderStack,
      });

    case POP_SLIDER:
      newSliderStack.pop();

      return mergeAll(state, action.payload, {
        sliderStack: newSliderStack,
      });

    case EMPTY_SLIDER_STACK:
      newSliderStack = [];

      return mergeAll(state, action.payload, {
        sliderStack: newSliderStack,
      });

    default:
      return state;
  }
};
