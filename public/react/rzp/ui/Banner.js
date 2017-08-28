export default (message, cta, canBeDismissed) => {
  canBeDismissed = !!canBeDismissed;

  const hasCta = !!cta,
    classes = ['alert', 'alert-warning'];

  if (hasCta) {
    if (typeof cta === 'function') {
      cta = cta();
    } else if (typeof cta === 'object') {
      const ctaKeys = Object.keys(cta);

      if (
        !ctaKeys.indexOf('text') >= 0 ||
        !(ctaKeys.indexOf('url') >= 0 || ctaKeys.indexOf('onClick') >= 0)
      ) {
        hasCta = false;
      } else {
        cta = <a className="btn btn-primary" />;
      }
    } else {
      hasCta = false;
    }
  }

  return (
    <div className="alert alert-warning">
      <span className="alert-text">
        {message}
      </span>
      {{ cta }}
    </div>
  );
};
