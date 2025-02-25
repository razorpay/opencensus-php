
  /**
   * Triggers a Hotjar heatmap for the specified trigger.
   * 
   * @param trigger - The event or name used to trigger the heatmap.
   * 
   * @example
   * triggerHotjarHeatmap('pageView');
   */
  export const triggerHotjarHeatmap = (trigger: string): void => {
    if (window && typeof window.hj === 'function') {
      window.hj('trigger', trigger);
    }
  }