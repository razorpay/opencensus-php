export default ({ bg, content, title }) => {
  let panelClass = 'panel padder-v item';
  let textClass = 'font-thin h1';

  if (bg) {
    panelClass += ` bg-${bg}`;
    textClass += ` text-white`;
  }

  return (
    <div class="col-xxs-12 col-xs-6 col-sm-6 col-md-4 col-lg-6">
      <div class={panelClass}>
        <div class={textClass}>{content}</div>
        <span class="text-muted text-xs">{title}</span>
      </div>
    </div>
  );
};
