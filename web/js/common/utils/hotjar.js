/**
 * Common utility to trigger Hotjar Recording
 * @param {string} trigger - trigger
 * @param {array} tags - tags
 */
function triggerHotjarRecording(trigger, tags) {
  if (window && typeof window.hj === 'function') {
    const defaultTags = [trigger];
    const uid = window.rzp_user?.user?.id;
    const mid = window.rzp_user?.id;

    uid && defaultTags.push(uid);
    mid && defaultTags.push(mid);

    window.hj('trigger', trigger);
    window.hj('tagRecording', tags || defaultTags);
  }
}

function triggerHotjarHeatmap(trigger) {
  if (window && typeof window.hj === 'function') {
    window.hj('trigger', trigger);
  }
}

export { triggerHotjarRecording, triggerHotjarHeatmap };
