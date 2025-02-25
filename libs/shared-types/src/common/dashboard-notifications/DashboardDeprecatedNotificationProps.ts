import { DashboardDeprecatedNotificationType } from "./DashboardDeprecatedNotificationType";

export type DashboardDeprecatedNotificationProps = {
    /**
     * @deprecated Please use ```color``` prop instead. ```type``` will be removed in future updates.
     */
    type: DashboardDeprecatedNotificationType;
    /**
     * @deprecated Please use ```content``` prop instead. ```message``` will be removed in future updates.
     */
    message: string | string[] | (() => string);
    /**
     * @deprecated Please use ```duration``` prop instead. ```closeTimeout``` will be removed in future updates.
     */
    closeTimeout?: number;
    /**
     * @deprecated Please use ```onDismissButtonClick``` prop instead. ```onCloseClick``` will be removed in future updates.
     */
    onCloseClick?: () => void;
  };