export default class BaseConfigLoader {
  constructor(loanApplication) {
    this.loanApplication = loanApplication;
  }

  get applicationStatus() {
    return this.loanApplication.status;
  }

  getStateTransitions() {
    const sideNavigationStateGroups = this.getSideNavigationStateGroups();

    const stateTransitionMap = {};

    // TODO: remove Object.entries over state groups and do not rely on order
    const stateGroups = Object.entries(sideNavigationStateGroups);
    stateGroups.forEach(([groupLabel, stateGroup]) => {
      const steps = Object.entries(stateGroup.steps);
      steps.forEach((step, stepIndex) => {
        const [_, subSteps] = step;
        subSteps.forEach((subStep) => {
          const stepNavigation = {
            back: null,
            next: null,
          };

          // registering the "next" state transition
          if (stepIndex + 1 < steps.length) {
            stepNavigation.next = steps[stepIndex + 1][0];
          } else {
            const nextStateGroup = stateGroups.find(
              ([_, stateGrp]) => stateGrp.index === stateGroup.index + 1,
            );
            if (nextStateGroup) {
              stepNavigation.next = Object.entries(nextStateGroup[1].steps)[0][0];
            } else {
              stepNavigation.next = null;
            }
          }

          // registering the "back" state transition
          if ((stateGroup.index === 0 && stepIndex > 0) || stateGroup.index > 0) {
            if (stepIndex === 0) {
              // previous step's last substep
              const previousStateGroup = stateGroups.find(
                ([_, stateGrp]) => stateGrp.index === stateGroup.index - 1,
              );
              stepNavigation.back = Object.entries(previousStateGroup[1].steps)[
                Object.entries(previousStateGroup[1].steps).length - 1
              ][0];
            } else {
              stepNavigation.back = steps[stepIndex - 1][0];
            }
          }
          stateTransitionMap[subStep] = stepNavigation;
        });
      });
    });

    return stateTransitionMap;
  }
}
