export default ({ bg, content, title, loading, error }) => {
  let panelClass = 'panel item';
  let textClass = 'font-thin h1';

  if (bg) {
    panelClass += ` bg-${bg}`;
    textClass += ` text-white`;
  }

  if (error) {
    textClass = bg
      ? textClass.replace('text-white', 'text-danger')
      : `${textClass} text-danger`;
  }

  return (
    <div class="col-xxs-12 col-xs-6 col-sm-6 col-md-4 col-lg-6">
      <div
        class={panelClass}
        style={{
          paddingTop: '15px',
          paddingBottom: '15px',
        }}
      >
        <div>
          <div
            class={textClass}
            style={{
              margin: '0',
            }}
          >
            {
              do {
                if (loading) {
                  ('...');
                } else if (error) {
                  ('Error');
                } else if (typeof content === 'function') {
                  content();
                } else {
                  content;
                }
              }
            }
          </div>
          <span
            style={{
              fontSize: '12px',
              color: '#d6d3e6',
            }}
          >
            {title}
          </span>
        </div>
      </div>
    </div>
  );
};
