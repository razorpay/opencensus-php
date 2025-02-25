/**
 * Triggers a Hotjar recording with optional tags.
 * 
 * @param trigger - The trigger event or name used to start a recording.
 * @param tags - Optional tags array, if not provided, default tags (including user ids) will be used.
 * 
 * @example
 * triggerHotjarRecording('userSignup', ['tag1', 'tag2']);
 */
export const triggerHotjarRecording = (trigger: string, tags?: string[]): void => {
    if (window && typeof window.hj === 'function') {
      const defaultTags = [trigger];
      const uid = window.rzp_user?.user?.id as string | undefined;
      const mid = window.rzp_user?.id as string | undefined;
  
      uid && defaultTags.push(uid);
      mid && defaultTags.push(mid);
  
      window.hj('trigger', trigger);
      window.hj('tagRecording', tags || defaultTags);
    }
  }
  