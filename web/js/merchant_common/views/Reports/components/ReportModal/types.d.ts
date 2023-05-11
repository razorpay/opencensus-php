export interface BaseReportModalPropsType {
  type: string;
  onCloseCallback?: () => void;
  params?:
    | {
        /**
         * `id` of the selected config.
         */
        selectedConfig?: string;
      }
    | Record<string, unknown>;
  dashboardType: string;
  ariaLabelBy?: string;
}

export interface ModalProps extends BaseReportModalPropsType {
  closeModal: () => void;
}

interface OtherModalConfig {
  scrollable?: boolean;
  initialWidth?: number;
}

export type RenderModalFnType = (
  children: JSX.Element,
  otherConfig?: OtherModalConfig,
) => JSX.Element;
