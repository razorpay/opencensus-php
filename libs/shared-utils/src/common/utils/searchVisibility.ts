/**
 * Props for determining search visibility
 */
export interface SearchVisibilityProps {
  /** Whether the current device is mobile */
  isMobile: boolean;
  /** Whether to show search on mobile devices */
  showOnlyMobileSearch?: boolean;
}

/**
 * Utility to determine whether search should be displayed based on device type
 * and configuration.
 */
export const shouldDisplaySearchBasedOnDeviceType = (props: SearchVisibilityProps): boolean => {
  const { isMobile, showOnlyMobileSearch } = props;

  // Always show on desktop/non-mobile devices
  if (!isMobile) {
    return true;
  }

  // On mobile devices, only show if explicitly enabled
  if (isMobile && showOnlyMobileSearch) {
    return true;
  }

  // Otherwise hide on mobile (default behavior)
  return false;
};
