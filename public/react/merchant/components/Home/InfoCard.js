export default ({ bg, content, title }) => {
  let panelClass = "panel padder-v item";
  let textClass = "font-thin h1";

  if (bg) {
    panelClass += ` bg-${bg}`;
    textClass += ` text-white`;
  }

  return (
    <div className="col-xxs-12 col-xs-6 col-sm-6 col-md-4 col-lg-6">
      <div className={panelClass}>
        <div className={textClass}>{content}</div>
        <span className="text-muted text-xs">{title}</span>
      </div>
    </div>
  );
};
