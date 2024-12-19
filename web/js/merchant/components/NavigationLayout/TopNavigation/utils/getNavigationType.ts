interface ActionParams {
  url?: string;
  path?: string;
}

interface Component {
  type: string;
  actions?: {
    action_params: ActionParams;
  }[];
}

interface NavigationType {
  type: string;
  path: string;
}

function determineNavigationType(components: Component[]): NavigationType[] {
  const result: NavigationType[] = [];

  function getNavigationType(actionParams: ActionParams): NavigationType {
    if (actionParams.url) {
      return { type: 'external', path: actionParams.url };
    } else if (actionParams.path) {
      return { type: 'internal', path: actionParams.path };
    }
    return { type: 'unknown', path: '' };
  }

  components.forEach((component: Component) => {
    if (component.type === 'navigate') {
      const action = component.actions?.[0];
      if (action && action.action_params) {
        const type = getNavigationType(action.action_params);
        result.push(type);
      } else {
        result.push({ type: 'unknown', path: '' });
      }
    } else {
      result.push({ type: 'unknown', path: '' });
    }
  });

  return result;
}

export default determineNavigationType;
