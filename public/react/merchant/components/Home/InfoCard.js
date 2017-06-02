import LoaderDots from 'rzp/ui/LoaderDots';

export default ({ className, content, title, loading, error }) => {
  return (
    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-6">
      <div class={`panel InfoCard ${className}`}>
        <div class="InfoCard__value">
          {
            do {
              if (loading) {
                <LoaderDots />;
              } else if (error) {
                <small class="text-danger font-sm">Error!</small>;
              } else if (typeof content === 'function') {
                <div class="h1">{content()}</div>;
              } else {
                <div class="h1">{content}</div>;
              }
            }
          }
        </div>
        <span class="InfoCard__label">{title}</span>
      </div>
    </div>
  );
};
