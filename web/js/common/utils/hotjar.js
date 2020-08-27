/**
 * Common utility to trigger Hotjar Recording
 * @param {string} trigger
 * @param {array} tags
 */
function triggerHotjarRecording(trigger, tags) {
  if (window && typeof window.hj === 'function') {
    window.hj('trigger', trigger);
    window.hj('tagRecording', tags || [trigger]);
  }
}

export { triggerHotjarRecording };
