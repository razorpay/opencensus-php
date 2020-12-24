function _track() {
  let track;

  function send(event, options) {
    track(window.rzpQ.subscriptionButtons().interaction(`button.${event}`, options));
  }

  return {
    trackSearchCount() {
      send('search.count');
    },

    trackSearchTitle() {
      send('search.title');
    },

    trackSearchClear() {
      send('search.clear');
    },

    trackErrorCloseClick() {
      send('listing.search.error');
    },

    trackPaginate: (params, type) => {
      send(`browse.${type}`, {
        page: params.skip % params.count,
      });
    },

    trackOpenDocs(button_id) {
      send('listing.review.open_docs', {
        button_id,
      });
    },

    trackCodeCopy(button_id) {
      send('listing.review.copy_code', {
        button_id,
      });
    },

    trackGetCode(button_id) {
      send('listing.review.get_code', { button_id });
    },

    trackGetCodeModalClosed(button_id) {
      send('listing.review.close_code', { button_id });
    },

    trackCreateEnter() {
      send('create.enter');
    },

    init: ({ track: _track }) => {
      track = _track;
    },
  };
}

export default _track();
