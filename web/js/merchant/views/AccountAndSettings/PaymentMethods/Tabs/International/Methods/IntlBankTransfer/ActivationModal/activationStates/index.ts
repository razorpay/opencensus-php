import create from 'zustand';

import { STEPS_INITIAL_STATE } from './constants';
import { validateSteps, validateIecCode } from './helpers';
import { ActivationModalState } from './types';

export const useActivationState = create<ActivationModalState>((set) => ({
  current: 1,
  isSavingForm: false,
  steps: STEPS_INITIAL_STATE,
  hiddenSteps: new Set([3]),
  setCurrent: (current, isCompleted = true) => {
    set((state) => ({
      current,
      steps: {
        ...state.steps,
        [state.current]: {
          ...state.steps[state.current],
          isCompleted,
        },
      },
    }));
  },
  validateStep: () => {
    let isValid = true;
    set((state) => {
      const validation = validateSteps(state);

      if (!validation.isValid) {
        isValid = false;
      }

      return validation.state;
    });
    return isValid;
  },
  setStepFields: (step, fields) => {
    set((state) => ({
      steps: {
        ...state.steps,
        [step]: {
          ...state.steps[step],
          fields: {
            ...state.steps[step].fields,
            ...fields,
          },
        },
      },
    }));
  },
  setStepReadOnly: (step, isReadOnly) => {
    set((state) => ({
      steps: {
        ...state.steps,
        [step]: {
          ...state.steps[step],
          isReadOnly,
        },
      },
    }));
  },
  setStepCompleted: (step, isCompleted) => {
    set((state) => ({
      steps: {
        ...state.steps,
        [step]: {
          ...state.steps[step],
          isCompleted,
        },
      },
    }));
  },
  setStepValidationState: (step, key, value) => {
    set((state) => ({
      steps: {
        ...state.steps,
        [step]: {
          ...state.steps[step],
          validationState: {
            ...state.steps[step].validationState,
            [key]: value,
          },
        },
      },
    }));
  },
  setIsSavingForm: (isSavingForm) => {
    set({ isSavingForm });
  },
  setStepSubmitBtnText: (step, text) => {
    set((state) => ({
      steps: {
        ...state.steps,
        [step]: {
          ...state.steps[step],
          submitBtnText: text,
        },
      },
    }));
  },
  setHiddenSteps: (steps) => {
    set({ hiddenSteps: new Set(steps) });
  },
}));

export { validateIecCode };
