export interface ActionParamsInternal {
  path: string;
}

export interface ActionParamsExternal {
  url: string;
}

export interface Action {
  title: string;
  action: string;
  type: 'button' | 'reset' | 'submit' | undefined;
  icon?: string;
  icon_position?: string;
  action_params: ActionParamsInternal | ActionParamsExternal;
  properties: {
    variant: 'primary' | 'secondary' | 'tertiary' | undefined;
  };
}

export interface Analytics {
  enabled?: boolean;
  [key: string]: unknown;
}

export interface CriticalActionComponent {
  id: string;
  type: string;
  title: string;
  description: string;
  actions?: Action[];
  inputs?: unknown[];
  components?: CriticalActionComponent[];
  alias: string;
  analytics?: Analytics | null;
  styles?: unknown | null;
  error?: unknown | null;
}

export interface CriticalActions {
  id: string;
  type: string;
  title: string;
  actions: Action[];
  inputs: unknown[];
  components: CriticalActionComponent[];
  alias: string;
  analytics?: Analytics | null;
  styles?: unknown | null;
  error?: unknown | null;
}

export interface CriticalActionsContentProps {
  criticalActionsData: CriticalActions;
  error: unknown;
  isLoading: boolean;
  isMobile: boolean;
  isDrawerOpen: boolean;
  onDrawerDismiss: () => void;
}
export interface CriticalActionInDrawerProps {
  criticalActioncomponent: CriticalActionComponent;
}

export interface NoActionMessageProps {
  user: {
    name?: string;
  };
}
